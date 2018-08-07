CREATE TABLE IF NOT EXISTS `mcr_menu_adm_groups` (
  `id`       int(10)                          NOT NULL AUTO_INCREMENT,
  `page_ids` text                                      DEFAULT NULL,
  `icon`     varchar(64)                               DEFAULT 'circle',
  `title`    varchar(32)                      NOT NULL DEFAULT '',
  `text`     varchar(255) CHARACTER SET utf8
  COLLATE utf8_estonian_ci                    NOT NULL DEFAULT '',
  `access`   varchar(64) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `priority` int(10)                          NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;