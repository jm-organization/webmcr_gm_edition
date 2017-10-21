<?php

/**
 * @Created in JM Organization.
 * @Author: Magicmen
 *
 * @Date: 15.10.2017
 * @Time: 21:20
 */
class l10n {
	public $core, $configs, $db;

	public $locale;

	public function __construct($core) {
		$this->core = $core;
		$this->db = $core->db;
		$this->configs = $core->cfg;

		$this->locale = $this->configs->main['s_lang'];
	}

	/**
	 * @function: get_user_locale
	 *
	 * @documentation: Метод для определения
	 * языка пользователя.
	 *
	 * @return string
	 */
	public function get_user_locale() {
		return '';
	}

	/**
	 * @function: get_config_locale
	 *
	 * @documentation: Метод для опеределиня языка
	 * из настроек сайта.
	 *
	 * @return string
	 */
	public function get_config_locale() {
		$locale_pattern = "/([a-z]{2})-([A-Z]{2})/";
		$locale = (preg_match($locale_pattern, $this->locale))?$this->locale:'ru-RU';

		return $locale;
	}

	/**
	 * @function: get_config_locale
	 *
	 * @documentation: Метод, определяющий наличие языка
	 * в списке поддерживаемых языков на сайте и, при условии,
	 * что такой язык в списке есть, возвращает его,
	 * иначе возвращает язык, указаный в настройках, проверяя его наличие
	 * в списке поддерживаемых, в противном случае - Русский (ru-RU).
	 *
	 * @return string
	 */
	public function get_locale() {
		$config_locale = $this->get_config_locale();
		$user_locale = $this->get_user_locale();

		$languages = $this->core->db->query("
			SELECT 
                            `id`,
                            `language`,
                            `settings`
			FROM `mcr_l10n_languages`
			WHERE 
				`language`='$user_locale'
			OR 
				`language`= '$config_locale'
		");

		if ($languages && $this->core->db->num_rows($languages) == 1) {
			$language = $this->core->db->fetch_assoc($languages);

			return (object)array(
                            'title' => json_decode($language['settings'])->title,
                            'locale' => $language['language']
                        );
		}

		return 'ru-RU';
	}

	/**
	 * @function: get_languages
	 *
	 * @documentation: Отдаёт спискок всех языков,
	 * если передано значение true. Иначе отдаёт список
	 * фраз и их значений, а также настройки и индитификатор
	 * для отдельного языка.
	 *
	 * @param string $language
	 * @param bool $is_all
	 *
	 * @return null|db $results
	 */
	public function get_languages($language = 'ru_RU', $is_all = true) {
		if ($is_all) {
			$sql = "SELECT `id`, `language`, `settings` FROM `mcr_l10n_languages`";
		} else {
			$sql = "
				SELECT 
					`language`, 
					`settings`,
					`phrases`
				FROM `mcr_l10n_languages`
				WHERE `language`= '$language'
			";
		}

		$results = $this->core->db->query($sql);

		if ($results || $this->core->db->num_rows($results) > 0) {
			return $results;
		}

		return null;
	}

	public function get_phrases($phrase = '', $is_all = true) {
		if ($is_all) {
			$sql = "
				SELECT 
					`phrases`.`id`,
					`languages`.`settings` AS `language_settings`, 
					`phrase_key`,
					`phrase_value`
				FROM `mcr_l10n_phrases` AS `phrases`
				INNER JOIN `mcr_l10n_languages` AS `languages`
				ON `phrases`.`language_id` = `languages`.`id`;
			";
		} else {
			$sql = "
				SELECT 
					`phrase_key`,
					`phrase_value`
				FROM `mcr_l10n_phrases`
				WHERE `phrase_key`= '$phrase';
			";
		}

		$results = $this->core->db->query($sql);

		if ($results || $this->core->db->num_rows($results) > 0) {
			return $results;
		}

		return null;
	}

	/**
	 * @function: gettext
	 *
	 * @documentation: Функция, ищащая фразу во фразах языка,
	 * который был указан при вызове. Если такая фраза существует,
	 * отдаёт её содержимое. Иначе - саму фразу.
	 *
	 * @param $phrase
	 *
	 * @return string $value
	 */
	public function gettext($phrase) {
		$query = $this->get_languages($this->get_locale()->locale, false);

		if (isset($query)) { while ($ar = $this->core->db->fetch_assoc($query)) {
			$phrases = json_decode($ar['phrases'], true);

			foreach ($phrases as $key => $value) {
				if ($phrase == $key) return $value;

                        }
                } }

		return $phrase;
	}
}
