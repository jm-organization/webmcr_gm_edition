CREATE TABLE IF NOT EXISTS `mcr_permissions` (
  `id`          int(10)                          NOT NULL AUTO_INCREMENT,
  `title`       varchar(64)                      NOT NULL DEFAULT '',
  `description` varchar(255)                     NOT NULL DEFAULT '',
  `value`       varchar(32) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `system`      tinyint(1)                       NOT NULL DEFAULT '0',
  `type`        varchar(32) CHARACTER SET latin1 NOT NULL DEFAULT 'boolean',
  `default`     varchar(32)                      NOT NULL DEFAULT 'false',
  `data`        text                             NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `value` (`value`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 110;