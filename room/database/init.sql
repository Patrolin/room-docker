# ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'groot';

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+01:00";


CREATE DATABASE IF NOT EXISTS `room`;
USE `room`;

CREATE TABLE IF NOT EXISTS `routing` ( # table=0
  `uuid` BIGINT NOT NULL, # u64
  `table` TINYINT NOT NULL, # u8
  PRIMARY KEY (`uuid`)
);

CREATE TABLE IF NOT EXISTS `users` ( # table=1
  `uuid` BIGINT NOT NULL,
  `username` VARCHAR(255) NOT NULL,
  `hash` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`uuid`),
  UNIQUE (`username`)
);

CREATE TABLE IF NOT EXISTS `sessions` ( # table=2
  `token` VARCHAR(255) NOT NULL,
  `expire` BIGINT NOT NULL,
  `uuid` BIGINT NOT NULL,
  PRIMARY KEY (`token`)
);

CREATE TABLE IF NOT EXISTS `channels` ( # table=3
  `uuid` BIGINT NOT NULL,
  PRIMARY KEY (`uuid`)
);
