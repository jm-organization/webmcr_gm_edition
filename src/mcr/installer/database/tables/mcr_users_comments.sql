CREATE TABLE IF NOT EXISTS `mcr_users_comments` (
  `id`        int(10) NOT NULL AUTO_INCREMENT,
  `uid`       int(10) NOT NULL,
  `from`      int(10) NOT NULL,
  `text_bb`   text    NOT NULL,
  `text_html` text    NOT NULL,
  `data`      text    NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;