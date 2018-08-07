CREATE TABLE IF NOT EXISTS `mcr_menu` (
  `id`          int(10)                          NOT NULL AUTO_INCREMENT,
  `title`       text                             NOT NULL,
  `parent`      int(10)                          NOT NULL DEFAULT '1',
  `url`         varchar(255)                     NOT NULL DEFAULT '',
  `target`      varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '_self',
  `permissions` varchar(255)                     NOT NULL DEFAULT '',
  `style`       varchar(255)                     NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;