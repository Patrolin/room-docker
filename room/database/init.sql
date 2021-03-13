# ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'groot';

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+01:00";


CREATE DATABASE IF NOT EXISTS `room` CHARACTER SET utf8 COLLATE utf8_czech_ci;
USE `room`;

CREATE TABLE IF NOT EXISTS `channels` ( # table=0
  `uuid` BIGINT NOT NULL, # u64
  `table` TINYINT NOT NULL, # u8
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`uuid`),
  UNIQUE (`name`)
);
REPLACE `channels` (`uuid`, `table`, `name`) VALUES (0, 0, "Doupě");

CREATE TABLE IF NOT EXISTS `users` ( # table=1
  `uuid` BIGINT NOT NULL,
  `hash` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`uuid`)
);

CREATE TABLE IF NOT EXISTS `sessions` ( # table=2
  `token` VARCHAR(255) NOT NULL,
  `expire` BIGINT NOT NULL,
  `uuid` BIGINT NOT NULL,
  PRIMARY KEY (`token`)
);

# unique pairs
CREATE TABLE IF NOT EXISTS `added` ( # table=3
  `A` BIGINT NOT NULL,
  `B` BIGINT NOT NULL,
  PRIMARY KEY (`A`, `B`),
  CHECK (`A` <= `B`)
);

CREATE TABLE IF NOT EXISTS `blocked` ( # table=4
  `A` BIGINT NOT NULL,
  `B` BIGINT NOT NULL,
  PRIMARY KEY (`A`, `B`)
);

CREATE TABLE IF NOT EXISTS `messages` ( # table=5
  `A` BIGINT NOT NULL,
  `B` BIGINT NOT NULL,
  `timestamp` BIGINT NOT NULL,
  `msg` VARCHAR(65535) NOT NULL,
  PRIMARY KEY (`A`, `B`, `timestamp`)
);
