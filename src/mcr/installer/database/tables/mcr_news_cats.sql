CREATE TABLE IF NOT EXISTS `mcr_news_cats` (
  `id`          int(10)      NOT NULL AUTO_INCREMENT,
  `title`       varchar(32)  NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `data`        text         NOT NULL,
  `hidden`      tinyint(1)   NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;