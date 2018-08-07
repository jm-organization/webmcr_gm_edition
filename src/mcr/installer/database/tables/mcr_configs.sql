CREATE TABLE IF NOT EXISTS `mcr_configs` (
  `option_key`    varchar(255) NOT NULL,
  `option_value`  mediumtext DEFAULT NULL,
  `default_value` mediumtext DEFAULT NULL,
  UNIQUE KEY `option_key` (`option_key`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;