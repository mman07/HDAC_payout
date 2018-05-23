#!/usr/bin/php
<?php
/*
running from bash shell
ex) sh ./payout.php

Payout for pendings / HDAC Nomp Poll Software
- automatic payout from 100 confirm

known issue
- move to not over 1 coin's hashes

2018-05-24
moricpool.com
mman@entiz.com

* reqire : php-cli, php-redis
* install : sudo apt install php-cli; sudo apt install php-redis
*/

function cmd($cmd) {
	ob_start();
	system($cmd);
	$command = ob_get_contents();
	ob_end_clean();
	return $command;
}

$system = json_decode(cmd("hdac-cli hdac getmininginfo"));
$balance = json_decode(cmd("hdac-cli hdac getmultibalances"));
$wallet_server = "<Server Wallet - wallet[0]>";
$wallet_pool = "<Pool Main Wallet - pool_config/h-dac.conf>";
$balance =  ($balance->$wallet_pool)[0]->qty;
$pool_fee = 1; // 1%
$award   = 5000*(100-$pool_fee)/100;
$start_block = 10000;		// latest payout block
$redis_host = "127.0.0.1";  // redis_server_host
$redis_port = 6379;			// redis_server_port
$redis = new Redis();
$redis->connect($redis_host, $redis_port, 1000);
$arList = $redis->keys("*");
$true = 0;
$false = 0;
$pending = 0;
$confirm = 100;
$payout_limit = 1;
$worker = array();
$total_pay = 0;

foreach($arList as $k => $v) {
	if( preg_match("/round[0-9]/",$v) ) {

		$blockNum = str_replace("hdac:shares:round","",$v);
		
		if($blockNum > $start_block) {

			$blockInfo = json_decode(cmd("hdac-cli hdac getblock ".$blockNum));

			if($blockInfo->miner != $wallet_server) { 
				// Orphaned block
				$false++;
			} else {

				if( $blockInfo->confirmations >= $confirm) {
					$hashes = $redis->hgetall($v) ;
					$totalhash = array_sum($hashes);
					foreach($hashes as $miner => $hash) {
						$pay = intval($award*$hash/$totalhash*100)/100;
						@$worker[$miner] += $pay;
						$total_pay += $pay;
					}
					$true++;	
				} else {
					// immature blcock
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
		cmd("hdac-cli hdac sendfrom ".$wallet_pool." ".$miner." ".$pay);
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

";

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
