# ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'groot';

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+01:00";


CREATE DATABASE IF NOT EXISTS `room`;
USE `room`;

CREATE TABLE IF NOT EXISTS `sessions` (
  `token` BINARY(32) NOT NULL,
  `expire` TIMESTAMP NOT NULL,
  `uuid` BINARY(16) NOT NULL,
  `json` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`token`),
);

CREATE TABLE IF NOT EXISTS `users` (
  `uuid` BINARY(16) NOT NULL,
  `username` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`uuid`),
  UNIQUE `username`,
);
