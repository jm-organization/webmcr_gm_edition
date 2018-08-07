CREATE TABLE IF NOT EXISTS `mcr_groups` (
  `id`          int(10)      NOT NULL AUTO_INCREMENT,
  `title`       varchar(32)  NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `color`       varchar(24)  NOT NULL DEFAULT '',
  `permissions` text         NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;