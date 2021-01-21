# ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '1234';

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+01:00";

CREATE DATABASE IF NOT EXISTS `room`;
USE `room`;

CREATE TABLE IF NOT EXISTS `sessions` (
  `token` BINARY(32) NOT NULL,
  `expire` BIGINT NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`token`)
);

CREATE TABLE IF NOT EXISTS `users` (
  `uid` INT NOT NULL,
  `email` VARCHAR(254) NOT NULL, # should be 254 bytes, but MySQL
  `username` VARCHAR(32) NOT NULL,
  `id` INT NOT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE (`email`),
  UNIQUE (`username`, `id`)
);
