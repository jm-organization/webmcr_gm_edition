CREATE TABLE IF NOT EXISTS `mcr_l10n_phrases` (
  `id`           int(10)      NOT NULL AUTO_INCREMENT,
  `language_id`  int(10)      NOT NULL DEFAULT '1',
  `phrase_key`   varchar(128) NOT NULL,
  `phrase_value` text         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phrase_key` (`phrase_key`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

ALTER TABLE `mcr_l10n_phrases`
  ADD CONSTRAINT `mcr_l10n_phrases_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `mcr_l10n_languages` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;