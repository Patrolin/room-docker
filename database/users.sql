
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `uid` INT NOT NULL,
  `email` VARCHAR(254) NOT NULL, # should be 254 bytes, but MySQL
  `username` VARCHAR(32) NOT NULL,
  `id` INT NOT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE (`email`),
  UNIQUE (`username`, `id`)
);
