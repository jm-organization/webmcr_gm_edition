CREATE TABLE IF NOT EXISTS `mcr_news_comments` (
  `id`        int(10)          NOT NULL AUTO_INCREMENT,
  `nid`       int(10)          NOT NULL DEFAULT '0',
  `text_html` text             NOT NULL,
  `text_bb`   text             NOT NULL,
  `uid`       int(10)          NOT NULL DEFAULT '0',
  `date`      int(10) UNSIGNED NOT NULL DEFAULT '0',
  `data`      text             NOT NULL,
  PRIMARY KEY (`id`),
  KEY `nid` (`nid`),
  KEY `uid` (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;