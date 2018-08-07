CREATE TABLE IF NOT EXISTS `mcr_files` (
  `id`      int(10)                           NOT NULL AUTO_INCREMENT,
  `uniq`    varchar(64) CHARACTER SET latin1  NOT NULL DEFAULT '',
  `name`    varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `oldname` varchar(255)                      NOT NULL DEFAULT '',
  `uid`     int(10)                           NOT NULL DEFAULT '0',
  `data`    text                              NOT NULL,
  `hash`    varchar(255)                      NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`uniq`),
  UNIQUE KEY `hash` (`hash`),
  KEY `uid` (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

ALTER TABLE `mcr_files`
  ADD CONSTRAINT `mcr_files_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `mcr_users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;