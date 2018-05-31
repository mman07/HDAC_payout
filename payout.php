#!/usr/bin/php
<?php
/*
running from bash shell
ex) sh ./payout.php

Payout for pendings / HDAC Nomp Poll Software
- automatic payout from 100 confirm

known issue
- move to not over 1 coin's hashes


2018-05-29
moricpool.com
mman@entiz.com

* reqire : php-cli, php-redis
* install : sudo apt install php-cli; sudo apt install php-redis
*/

$wallet_server = "server wallet address";
$wallet_pool = "pool main address";
$mysql_host = "localhost";
$mysql_user = "your mysql userID";
$mysql_pass = "your mysql password";
$database = "pool";

// Connect MySQL Database
$connect = mysqli_connect($mysql_host,$mysql_user,$mysql_pass, $database);

function cmd($cmd) {
	ob_start();
	system($cmd);
	$command = ob_get_contents();
	ob_end_clean();
	return str_replace("\n","",$command);
}

$error_log = "";
function send($address,$amount) {
	// Excute Send coin and Save transaction history
	global $connect, $error_log, $wallet_pool;
	$txid = cmd("hdac-cli hdac sendfrom ".$wallet_pool." ".$address." ".$amount);
	$sql = "insert into payout (address, amount, txid)	values ('".$address."', '".$amount."', '".$txid."') ";
	$result = mysqli_query($connect,$sql);
	$error_log .= "\n".time().",".$wallet_pool.",".$address.",".$amount;
}

$system = json_decode(cmd("hdac-cli hdac getmininginfo"));
$balance = json_decode(cmd("hdac-cli hdac getmultibalances"));
$balance =  ($balance->$wallet_pool)[0]->qty;
$pool_fee = 1; // 1%
$award   = 5000*(100-$pool_fee)/100;
$start_block = 10000;		// latest payout block
$redis_host = "127.0.0.1";  	// redis_server_host
$redis_port = 6379;		// redis_server_port
$redis = new Redis();
$redis->connect($redis_host, $redis_port, 1000);
$arList = $redis->keys("*");
$true = 0;
$false = 0;
$pending = 0;
$confirm = 100;		// important option. (Coin will be coming to pool wallet after 100 confirmation)
$payout_limit = 0.01;	// set up over 0.01  (tx fee is almost 0.01 DAC)
$worker = array();
$total_pay = 0;

$block_orphan = "";
$block_payout = "";
$block_pending = "";

foreach($arList as $k => $v) {
	if( preg_match("/round[0-9]/",$v) ) {

		$blockNum = str_replace("hdac:shares:round","",$v);
		if($blockNum > $start_block) {
			$blockInfo = json_decode(cmd("hdac-cli hdac getblock ".$blockNum));
			if($blockInfo->miner != $wallet_server) { 
				// Orphaned block
				$false++;

				$block_orphan .= $blockNum.",";
	
			} else {
				if( $blockInfo->confirmations >= $confirm) {
					$hashes = $redis->hgetall($v) ;
					$totalhash = array_sum($hashes);
					foreach($hashes as $miner => $hash) {
						$pay = intval($award*$hash/$totalhash*100)/100;
						@$worker[$miner] += $pay;
						$total_pay += $pay;
					}
					// Warning! it will delete round#blocknumber tables
					// try to test payout result and log data in MySQL before use delete key
					$redis->del($v);
					
					$block_payout .= $blockNum.",";
					$true++;	
				} else {
					// immature blcock
					$block_pending .= $blockNum.",";
					$pending++;
				}
			}
		}
	}
}

$payed = 0;
$pay_log = "";
foreach($worker as $miner => $pay) {
	$miner = explode(".",$miner); // somebody use worker.rigname
	$miner = $miner[0];
	if($pay > $payout_limit) {
		send($miner, $pay);
		$pay_log .= $miner." : ". sprintf("% 11.2f",$pay)."\n";
		$payed += $pay;
		usleep(300000);  // pause 0.3sec
		echo $miner." : ". sprintf("% 11.2f",$pay)."\n";
	}
}


$date = date("YmdHis");
$log = "
Date		: ".date("Y-m-d H:i:s")."
NetHash		: ".number_format($system->networkhashps/1000000000000,2)." TH
Difficulty	: ".number_format($system->difficulty/1000,2)." GH
Last Block	: ".$system->blocks."
Confirm		: ".$confirm." over

----------------------------------------------------
    worker                         :     amount
----------------------------------------------------
".$pay_log."
----------------------------------------------------
    Total                          :  ".number_format($payed,2)."
----------------------------------------------------

              http://hdac.moricpool.com

Orphaned Block : ".$block_orphan."
Payouted Block : ".$block_payout."
Pending Block  : ".$block_pending."
";

// Warning! it will delete hdac:blocksPending key
// Please keep remark before pass paymets
$redis->del("hdac:blocksPending");

echo $log;


$saveFile = "./payout_".$date.".log";
$f = fopen($saveFile,'w');
fwrite($f,$log);
fclose($f);

// latest log
$currentFile = "./payout.log";
$f = fopen($currentFile,'w');
fwrite($f,$log);
fclose($f);

?>
