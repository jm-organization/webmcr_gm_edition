CREATE TABLE IF NOT EXISTS `mcr_iconomy` (
  `id`        int(10)                          NOT NULL AUTO_INCREMENT,
  `login`     varchar(32) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `money`     decimal(10, 2)                   NOT NULL DEFAULT '0.00',
  `realmoney` decimal(10, 2)                   NOT NULL DEFAULT '0.00',
  `bank`      decimal(10, 2)                   NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

ALTER TABLE `mcr_iconomy`
  ADD CONSTRAINT `mcr_iconomy_ibfk_1` FOREIGN KEY (`login`) REFERENCES `mcr_users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;