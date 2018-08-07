CREATE TABLE IF NOT EXISTS `mcr_statics` (
  `id`          int(10)                          NOT NULL AUTO_INCREMENT,
  `uniq`        varchar(64) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `title`       varchar(64)                      NOT NULL DEFAULT '',
  `text_html`   longtext                         NOT NULL,
  `uid`         int(10)                          NOT NULL DEFAULT '0',
  `permissions` varchar(64) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `data`        text                             NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`uniq`),
  KEY `uid` (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

ALTER TABLE `mcr_statics`
  ADD CONSTRAINT `mcr_statics_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `mcr_users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;