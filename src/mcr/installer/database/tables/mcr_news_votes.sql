CREATE TABLE IF NOT EXISTS `mcr_news_votes` (
  `id`    int(10)                          NOT NULL AUTO_INCREMENT,
  `nid`   int(10)                          NOT NULL DEFAULT '0',
  `uid`   int(10)                          NOT NULL DEFAULT '-1',
  `value` tinyint(1)                       NOT NULL,
  `ip`    varchar(64) CHARACTER SET latin1 NOT NULL DEFAULT '127.0.0.1',
  `time`  int(10) UNSIGNED                 NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `nid` (`nid`),
  KEY `uid` (`uid`),
  KEY `nid_2` (`nid`),
  KEY `uid_2` (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

ALTER TABLE `mcr_news_votes`
  ADD CONSTRAINT `mcr_news_votes_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `mcr_users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `mcr_news_votes_ibfk_1` FOREIGN KEY (`nid`) REFERENCES `mcr_news` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;