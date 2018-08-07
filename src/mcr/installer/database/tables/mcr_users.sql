CREATE TABLE IF NOT EXISTS `mcr_users` (
  `id`          int(11)                           NOT NULL AUTO_INCREMENT,
  `gid`         int(11)                           NOT NULL DEFAULT '1',
  `login`       varchar(32) CHARACTER SET latin1  NOT NULL DEFAULT '',
  `email`       binary(16)                        NOT NULL DEFAULT '',
  `password`    varchar(64) CHARACTER SET latin1  NOT NULL DEFAULT '',
  `uuid`        varchar(128) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `salt`        varchar(128)                      NOT NULL DEFAULT '',
  `tmp`         varchar(128) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `is_skin`     tinyint(1)                        NOT NULL DEFAULT '0',
  `is_cloak`    tinyint(1)                        NOT NULL DEFAULT '0',
  `ip_create`   varchar(64) CHARACTER SET latin1  NOT NULL DEFAULT '127.0.0.1',
  `ip_last`     timestamp(6)                               DEFAULT CURRENT_TIMESTAMP(6),
  `time_create` timestamp(6)                               DEFAULT CURRENT_TIMESTAMP(6),
  `time_last`   enum ('male', 'no_set', 'female') NOT NULL DEFAULT 'no_set',
  `gender`      tinyint(1)                        NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `gid` (`gid`),
  KEY `login_2` (`login`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

ALTER TABLE `mcr_users`
  ADD CONSTRAINT `mcr_users_ibfk_1` FOREIGN KEY (`gid`) REFERENCES `mcr_groups` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;