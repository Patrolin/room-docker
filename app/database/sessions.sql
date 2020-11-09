
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `token` BINARY(32) NOT NULL,
  `expire` BIGINT NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`token`)
);
