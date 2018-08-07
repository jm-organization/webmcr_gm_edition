CREATE TABLE IF NOT EXISTS `mcr_monitoring` (
  `id`          int(10)                          NOT NULL AUTO_INCREMENT,
  `title`       varchar(32)                      NOT NULL DEFAULT '',
  `text`        varchar(255)                     NOT NULL DEFAULT '',
  `ip`          varchar(64) CHARACTER SET latin1 NOT NULL DEFAULT '127.0.0.1',
  `port`        int(6)                           NOT NULL DEFAULT '25565',
  `status`      tinyint(1)                       NOT NULL DEFAULT '0',
  `version`     varchar(64)                      NOT NULL DEFAULT '',
  `online`      int(10)                          NOT NULL DEFAULT '0',
  `slots`       int(10)                          NOT NULL DEFAULT '0',
  `players`     text                             NOT NULL,
  `motd`        text                             NOT NULL,
  `plugins`     text                             NOT NULL,
  `map`         varchar(64)                      NOT NULL DEFAULT '',
  `last_error`  text                             NOT NULL,
  `last_update` int(10) UNSIGNED                 NOT NULL DEFAULT '0',
  `updater`     int(10)                          NOT NULL DEFAULT '60',
  `type`        varchar(32) CHARACTER SET latin1 NOT NULL DEFAULT 'MineToolsAPIPing',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;