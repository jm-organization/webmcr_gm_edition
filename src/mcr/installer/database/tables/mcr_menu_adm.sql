CREATE TABLE IF NOT EXISTS `mcr_menu_adm` (
  `id`       int(10)                          NOT NULL AUTO_INCREMENT,
  `page_id`  varchar(64)                      NOT NULL,
  `gid`      int(10)                                   DEFAULT NULL,
  `title`    varchar(24)                      NOT NULL DEFAULT 'New menu',
  `text`     varchar(255)                              DEFAULT NULL,
  `url`      varchar(255)                     NOT NULL DEFAULT '#',
  `target`   varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '_self',
  `access`   text CHARACTER SET latin1        NOT NULL,
  `priority` int(6)                                    DEFAULT '1',
  `fixed`    int(6)                                    DEFAULT NULL,
  `icon`     int(10)                          NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `gid` (`gid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

ALTER TABLE `mcr_menu_adm`
  ADD CONSTRAINT `mcr_menu_adm_ibfk_1` FOREIGN KEY (`gid`) REFERENCES `mcr_menu_adm_groups` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;