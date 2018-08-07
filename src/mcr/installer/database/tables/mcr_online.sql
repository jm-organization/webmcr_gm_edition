CREATE TABLE IF NOT EXISTS `mcr_online` (
  `id`          int(10)          NOT NULL AUTO_INCREMENT,
  `ip`          varchar(16)      NOT NULL DEFAULT '127.0.0.1',
  `online`      tinyint(1)       NOT NULL DEFAULT '0',
  `date_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;