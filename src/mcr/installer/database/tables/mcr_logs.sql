CREATE TABLE IF NOT EXISTS `mcr_logs` (
  `id`      int(10)          NOT NULL AUTO_INCREMENT,
  `uid`     int(10)          NOT NULL DEFAULT '0',
  `message` varchar(255)     NOT NULL DEFAULT '',
  `date`    int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;