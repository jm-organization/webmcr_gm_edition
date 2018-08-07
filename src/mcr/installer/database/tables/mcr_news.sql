CREATE TABLE IF NOT EXISTS `mcr_news` (
  `id`        int(10)          NOT NULL AUTO_INCREMENT,
  `cid`       int(10)          NOT NULL DEFAULT '1',
  `title`     varchar(32)      NOT NULL DEFAULT '',
  `text_html` text             NOT NULL,
  `vote`      tinyint(1)       NOT NULL DEFAULT '1',
  `discus`    tinyint(1)       NOT NULL DEFAULT '1',
  `attach`    tinyint(1)       NOT NULL DEFAULT '0',
  `hidden`    tinyint(1)       NOT NULL DEFAULT '0',
  `uid`       int(10)          NOT NULL DEFAULT '1',
  `date`      int(10) UNSIGNED NOT NULL DEFAULT '0',
  `img`       varchar(255)              DEFAULT NULL,
  `data`      text                      DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `uid` (`uid`),
  KEY `cid_2` (`cid`),
  KEY `uid_2` (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

ALTER TABLE `mcr_news`
  ADD CONSTRAINT `mcr_news_ibfk_1` FOREIGN KEY (`cid`) REFERENCES `mcr_news_cats` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `mcr_news_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `mcr_users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;