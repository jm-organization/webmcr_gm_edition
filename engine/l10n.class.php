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
        
        $this->core->is_install(function() {
            $locale = $this->get_config_locale();
            Locale::setDefault($locale);
        
            $locale_path = MCR_CACHE_PATH.'l10n/'.$locale;

            if (!file_exists($locale_path) || (
                !file_exists($locale_path.'/.info') && !file_exists($locale_path.'/.cache')
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
		$locale = (preg_match($locale_pattern, $this->locale) == 1)?$this->locale:'ru-RU';

		return $locale;
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
        
        $locale = setlocale(LC_ALL, $dl_formated.'.UTF-8', $language);

		return $locale;
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
	public function gettext($phrase, $text = null) {
        $locale = $this->get_config_locale();
        $locale_cache_path = MCR_CACHE_PATH.'l10n/'.$locale;
        $phrases = file_get_contents($locale_cache_path.'/.cache');
        
        $unjson_phrases = json_decode($phrases, true);
        
        $closer = "_%s_";
        $phrase = (ctype_digit($phrase))?sprintf($closer, $phrase):$phrase;
        
        foreach ($unjson_phrases as $key => $value) {
            if ($phrase == $key) { return sprintf($value, $text); }
        }
        
		return $phrase;
	}
    
    public function set_cache($locale=false) {
        $locale = ($locale)?$locale:Locale::getDefault();
        $locale_cache_path = MCR_CACHE_PATH.'l10n/'.$locale;
        if (!file_exists($locale_cache_path)) { mkdir(MCR_CACHE_PATH.'l10n/'.$locale); }
            
        $languages = $this->get_languages($locale, false);
        $languages = $this->db->fetch_assoc($languages);
        
        file_put_contents($locale_cache_path.'/.info', $languages['settings']);
        file_put_contents($locale_cache_path.'/.cache', $languages['phrases']);
    }
    
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
    
    public function delete_cache($locales, $route='') {
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
     * @param $text
     * @param string $type
     * @param string $datetime_format
     * @return string
     */
    public function localize($text, $type = 'string', $datetime_format = '') {
        $l_text = '';

	    switch($type) {
            case 'string': break;
            case 'date': break;
            case 'time': break;
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
