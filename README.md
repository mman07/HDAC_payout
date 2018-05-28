# HDAC_payout

Payout batch command for HDAC Nomp Pool 

running from bash shell
ex) sh ./payout.php

Payout for pendings / HDAC Nomp Poll Software
- automatic payout from 100 confirm

known issue
- move to not over 1 coin's hashes

2018-05-24
moricpool.com
mman@entiz.com

* reqire : 
  php-cli, php-redis, mariadb-server

* install : sudo apt install php-cli php-redis mariadb-server



*** SQL batch ***



CREATE TABLE `payout` (
  `seq` int(11) NOT NULL,
  `block` int(11) NOT NULL,
  `address` varchar(35) NOT NULL,
  `amount` double NOT NULL,
  `txid` varchar(64) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `payout`
--
ALTER TABLE `payout`
  ADD PRIMARY KEY (`seq`),
  ADD KEY `address` (`address`),
  ADD KEY `txid` (`txid`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `block` (`block`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `payout`
--
ALTER TABLE `payout`
  MODIFY `seq` int(11) NOT NULL AUTO_INCREMENT;
  
