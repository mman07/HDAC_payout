# HDAC_payout

* 2018-05-24
* http://hdac.moricpool.com
* mman@entiz.com

## Payout batch command for HDAC Nomp Pool 
Payout for pendings / HDAC Nomp Poll Software
- automatic payout from 100 confirm

-------
### reqire
```
php-cli, php-redis, mariadb-server, php-mysql
```


-------
### Install
```
sudo apt install php-cli php-redis mariadb-server php-mysql
```

* add crontab ( repeat per 1hr )
```
0 * * * * /[DIR]/payout.php
```
 
-------
### Run in command

running from bash shell
```
sh ./payout.php
```



-------
### SQL batch
```
CREATE TABLE `payout` (
  `seq` int(11) NOT NULL,
  `block` int(11) NOT NULL,
  `address` varchar(35) NOT NULL,
  `amount` double NOT NULL,
  `txid` varchar(64) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `payout`
  ADD PRIMARY KEY (`seq`),
  ADD KEY `address` (`address`),
  ADD KEY `txid` (`txid`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `block` (`block`);

ALTER TABLE `payout`
  MODIFY `seq` int(11) NOT NULL AUTO_INCREMENT;

```
