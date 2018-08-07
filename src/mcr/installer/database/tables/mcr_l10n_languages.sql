CREATE TABLE IF NOT EXISTS `mcr_l10n_languages` (
  `id`              int(10)      NOT NULL AUTO_INCREMENT,
  `parent_language` int(10)               DEFAULT NULL,
  `language`        varchar(255) NOT NULL,
  `settings`        text         NOT NULL,
  `phrases`         text         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language` (`language`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

ALTER TABLE `mcr_l10n_languages`
  ADD CONSTRAINT `mcr_l10n_languages_ibfk_1` FOREIGN KEY (`parent_language`) REFERENCES `mcr_l10n_languages` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;