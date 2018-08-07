CREATE TABLE IF NOT EXISTS `mcr_menu_adm_icons` (
  `id`    int(10)      NOT NULL AUTO_INCREMENT,
  `title` varchar(32)  NOT NULL DEFAULT '',
  `img`   varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;