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

    public $locales = array(
        'af-ZA', 'am-ET', 'ar-AR', 'ay-BO', 'az-AZ', 'be-BY', 'bg-BG', 'bn-IN',
        'bs-BA', 'ca-ES', 'cs-CZ', 'cy-GB', 'da-DK', 'de-DE', 'el-GR', 'en-GB',
        'en-US', 'eo-EO', 'es-CL', 'es-CO', 'es-ES', 'es-LA', 'fr-CA', 'fr-FR',
        'ga-IE', 'gl-ES', 'gu-IN', 'ha-NG', 'he-IL', 'hi-IN', 'hr-HR', 'ht-HT',
        'hu-HU', 'hy-AM', 'id-ID', 'ig-NG', 'is-IS', 'it-IT', 'ja-JP', 'jv-ID',
        'ka-GE', 'kk-KZ', 'km-KH', 'kn-IN', 'ko-KR', 'ku-TR', 'la-VA', 'li-NL',
        'lo-LA', 'lt-LT', 'lv-LV', 'mg-MG', 'mk-MK', 'ml-IN', 'mn-MN', 'mr-IN',
        'ms-MY', 'mt-MT', 'my-MM', 'nb-NO', 'ne-NP', 'nl-NL', 'nn-NO', 'or-IN',
        'pa-IN', 'pl-PL', 'ps-AF', 'pt-BR', 'pt-PT', 'qu-PE', 'rm-CH', 'ro-RO',
        'ru-RU', 'sa-IN', 'sk-SK', 'sl-SI', 'so-SO', 'sq-AL', 'sr-RS', 'sv-SE',
        'sw-KE', 'ta-IN', 'te-IN', 'tg-TJ', 'th-TH', 'tl-PH', 'tl-ST', 'tr-TR',
        'tt-RU', 'uk-UA', 'ur-PK', 'uz-UZ', 'vi-VN', 'xh-ZA', 'yi-DE', 'yo-NG',
        'zh-CN', 'zh-HK', 'zh-TW', 'zu-ZA'
    );

    public $date_formats = array(
        'M j, Y' => '%b %d, %Y',
        'F j, Y' => '%B %d, %Y',
        'j M Y' => '%d %b %Y',
        'j F Y' => '%d %B %Y',
        'j/n/y' => '%d/%m/%y',
        'n/j/y' => '%m/%d/%y',
        'd.m.Y' => '%d.%m.%Y'
    );

    public $time_formats = array(
        'g:i A' => '%I:%M %p',
		'H:i' => '%H:%M'
    );

	public function __construct($core) {
		$this->core = $core;
		$this->db = $core->db;
		$this->configs = $core->cfg;

		$this->locale = $this->configs->main['s_lang'];
        
        $this->core->is_install(function() {
            $locale = $this->get_config_locale();
            Locale::setDefault($locale);
        
            $locale_path = MCR_CACHE_PATH.'l10n/'.$locale;

            if (!file_exists($locale_path) || (
                !file_exists($locale_path.'/.info') || !file_exists($locale_path.'/.cache')
            )) { 
                if (!file_exists(MCR_CACHE_PATH.'l10n')) { mkdir(MCR_CACHE_PATH.'l10n'); }
                if (!file_exists($locale_path)) { mkdir($locale_path); }

                $this->set_cache();             
            }
        });
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
		//TODO: Initialize user locale
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

		if (preg_match($locale_pattern, $this->locale) == 1) {
		    return $this->locale;
        }

		return 'ru-RU';
	}

	/**
	 * @function: get_config_locale
	 *
	 * @documentation: Задаёт локаль для локализации даты и прочего.
	 *
	 * @return string
	 */
	public function get_locale() {
        $default_locale = Locale::getDefault();
        $dl_formated = str_replace('-', '_', $default_locale);
       
        $language = Locale::getDisplayLanguage($default_locale, 'en-US');

//      $locale = setlocale(LC_ALL, $dl_formated.'.UTF-8', $language);
//		$locale = setlocale(LC_ALL, $dl_formated.'.utf8', $language);
		$locale = setlocale(LC_ALL, $dl_formated.'.UTF-8', $language);
//		var_dump($locale);
		return $locale;
	}

	/**
	 * @function: get_locale_info
	 *
	 * @documentation:
	 *
	 * @param $key
	 *
	 * @return null
	 */
	public function get_locale_info($key) {
		$locale = $this->get_config_locale();
		$locale_info_path = MCR_CACHE_PATH.'l10n/'.$locale;
		$locale_info = file_get_contents($locale_info_path.'/.info');

		$locale_info = json_decode($locale_info, true);

		if (array_key_exists($key, $locale_info)) {
			return $locale_info[$key];
		}

		return null;
	}

	/**
	 * @function: get_date_format
	 *
	 * @documentation:
	 *
	 * @return mixed|string
	 */
	public function get_date_format() {
		$date_format = $this->get_locale_info('date_format');

		if (array_key_exists($date_format, $this->date_formats)) {
			return $this->date_formats[$date_format];
		}

		return '%d %b %Y';
	}

	public function get_time_format() {
		$time_format = $this->get_locale_info('time_format');

		if (array_key_exists($time_format, $this->time_formats)) {
			return $this->time_formats[$time_format];
		}

		return '%R';
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
	 * @param null $var
	 * @param null $locale
	 *
	 * @return string $value
	 */
	public function gettext($phrase, $var = null, $locale = null) {
        $locale = (empty($locale))?$this->get_config_locale():$locale;
		$locale = (preg_match('/([a-z]{2})-([A-Z]{2})/', $locale) == 1)?$locale:(function($phrase) { return $phrase; });
        $locale_cache_path = MCR_CACHE_PATH.'l10n/'.$locale;
        $phrases = file_get_contents($locale_cache_path.'/.cache');
        $unjson_phrases = json_decode($phrases, true);
        
        $closer = "_%s_";
        $phrase = (ctype_digit($phrase))?sprintf($closer, $phrase):$phrase;

		foreach ($unjson_phrases as $key => $value) {
			if ($phrase == $key) {
				return str_replace('`','"',$value);
			}
		}
        
		return $phrase;
	}

	/**
	 * @function: set_cache
	 *
	 * @documentation:
	 *
	 * @param bool $locale
	 *
	 */
	public function set_cache($locale=false) {
        $locale = ($locale)?$locale:Locale::getDefault();
        $locale_cache_path = MCR_CACHE_PATH.'l10n/'.$locale;
        if (!file_exists($locale_cache_path)) { mkdir(MCR_CACHE_PATH.'l10n/'.$locale); }
            
        $languages = $this->get_languages($locale, false);
        $languages = $this->db->fetch_assoc($languages);

        var_dump('test');
        
        file_put_contents($locale_cache_path.'/.info', $languages['settings']);
        file_put_contents($locale_cache_path.'/.cache', $languages['phrases']);
    }

	/**
	 * @function: update_cache
	 *
	 * @documentation:
	 *
	 * @param $locale
	 * @param string $route
	 *
	 */
	public function update_cache($locale, $route='') {
        $pattern = '/([a-z]{2})-([A-Z]{2})/';
        $locale = (preg_match($pattern, $locale) == 1)?$locale:false;
        $locale_cache_path = MCR_CACHE_PATH.'l10n/'.$locale;
        if (!$locale || !file_exists($locale_cache_path)) { $this->core->notify(
            $this->gettext('error_message'),
            $this->gettext('error_locale_not_found'),
            2,
            $route
        ); }

        $languages = $this->get_languages($locale, false);
        $languages = $this->db->fetch_assoc($languages);
        
        file_put_contents($locale_cache_path.'/.info', $languages['settings']);
        file_put_contents($locale_cache_path.'/.cache', $languages['phrases']);
    }

	/**
	 * @function     : delete_cache
	 *
	 * @documentation:
	 *
	 * @param $locales
	 *
	 * @return bool
	 */
	public function delete_cache($locales) {
        $locales = explode(', ', str_replace("'", '', $locales));
             
        foreach ($locales as $locale) {
            $locale_cache_path = MCR_CACHE_PATH.'l10n/'.$locale;
            if (!file_exists($locale_cache_path)) { continue; }
            
            $files = scandir($locale_cache_path);
            
            foreach ($files as $file) { if ($file != '.' && $file != '..') {
                unlink($locale_cache_path.'/'.$file);
            } }
            
            rmdir($locale_cache_path);
        }
        
        return false;
    }

	/**
	 * @function: localize
	 *
	 * @documentation:
	 *
	 * @param $text
	 * @param string $type
	 * @param string $datetime_format
	 *
	 * @return string
	 */
	public function localize($text, $type = 'string', $datetime_format = '') {
        $l_text = '';

	    switch($type) {
            case 'string': break;
            case 'date': break;
            case 'unixtime':
				$l_text = $this->localize_detetime($datetime_format, $text);
				break;
            case 'datetime': 
                $datetime = new DateTime($text);
                $dt_unix = $datetime->format("U");
                
                $l_text = $this->localize_detetime($datetime_format, $dt_unix); 
                break;
            case 'double': break;
            default: $l_text = $text; break;
        }
        
        return $l_text;
    }

	/**
	 * @function: localize_detetime
	 *
	 * @documentation:
	 *
	 * @param $format
	 * @param $timestamp
	 *
	 * @return string
	 */
	public function localize_detetime($format, $timestamp) {
        $locale = $this->get_locale();
        $date_str = strftime($format, $timestamp);
        
        if (strpos($locale, '1251') !== false) {
            return iconv('cp1251', 'utf-8', $date_str);
        } else { return $date_str; }
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
	public function get_languages($language = 'ru-RU', $is_all = true) {
		if ($is_all) {
			$sql = "SELECT `id`, `parent_language`, `language`, `settings` FROM `mcr_l10n_languages`";
		} else {
			$sql = "
				SELECT 
					`id`, 
					`settings`,
					`phrases`
				FROM `mcr_l10n_languages`
				WHERE `language`='$language' or `id`='$language'
			";
		}

        $results = $this->db->query($sql);

		if ($results || $this->db->num_rows($results) > 0) {
			return $results;
		}

		return null;
	}

	/**
	 * @function: get_phrases
	 *
	 * @documentation:
	 *
	 * @param string $phrase
	 * @param bool $is_all
	 *
	 * @return null
	 */
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
                    `language_id`,
					`phrase_key`,
					`phrase_value`
				FROM `mcr_l10n_phrases`
				WHERE `phrase_key`= '$phrase' OR `id`='$phrase';
			";
		}

		$results = $this->db->query($sql);

		if ($results || $this->db->num_rows($results) > 0) {
			return $results;
		}

		return null;
	}
}
