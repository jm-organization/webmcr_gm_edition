CREATE TABLE IF NOT EXISTS `mcr_blocks_configs` (
  `id`                 int(10)      NOT NULL AUTO_INCREMENT,
  `block_id`           varchar(255) NOT NULL,
  `configs`            mediumtext   NOT NULL,
  `name`               varchar(255) DEFAULT NULL,
  `description`        text         DEFAULT NULL,
  `author`             varchar(255) DEFAULT NULL,
  `site`               varchar(255) DEFAULT NULL,
  `email`              varchar(128) DEFAULT NULL,
  `version`            varchar(255) NOT NULL,
  `updation_url`       varchar(255) DEFAULT NULL,
  `checking_on_update` tinyint      DEFAULT NULL,
  UNIQUE KEY `block_id` (`block_id`),
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;