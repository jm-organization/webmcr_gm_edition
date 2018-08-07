CREATE TABLE IF NOT EXISTS `mcr_logs_of_edit` (
  `id`     int(10)          NOT NULL AUTO_INCREMENT,
  `editor` int(10)          NOT NULL,
  `things` int(10)          NOT NULL,
  `table`  varchar(96)      NOT NULL,
  `date`   int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

ALTER TABLE `mcr_logs_of_edit`
  ADD CONSTRAINT `mcr_logs_of_edit_ibfk_1` FOREIGN KEY (`editor`) REFERENCES `mcr_users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;