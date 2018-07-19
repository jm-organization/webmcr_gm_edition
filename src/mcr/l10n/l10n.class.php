<?php

/**
 * @Created in JM Organization.
 * @Author  : Magicmen
 *
 * @Date    : 15.10.2017
 * @Time    : 21:20
 */

namespace mcr\l10n;


use Locale;
use mcr\database\db;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

trait l10n
{
	public static $locales = [
		'af-ZA',
		'am-ET',
		'ar-AR',
		'ay-BO',
		'az-AZ',
		'be-BY',
		'bg-BG',
		'bn-IN',
		'bs-BA',
		'ca-ES',
		'cs-CZ',
		'cy-GB',
		'da-DK',
		'de-DE',
		'el-GR',
		'en-GB',
		'en-US',
		'eo-EO',
		'es-CL',
		'es-CO',
		'es-ES',
		'es-LA',
		'fr-CA',
		'fr-FR',
		'ga-IE',
		'gl-ES',
		'gu-IN',
		'ha-NG',
		'he-IL',
		'hi-IN',
		'hr-HR',
		'ht-HT',
		'hu-HU',
		'hy-AM',
		'id-ID',
		'ig-NG',
		'is-IS',
		'it-IT',
		'ja-JP',
		'jv-ID',
		'ka-GE',
		'kk-KZ',
		'km-KH',
		'kn-IN',
		'ko-KR',
		'ku-TR',
		'la-VA',
		'li-NL',
		'lo-LA',
		'lt-LT',
		'lv-LV',
		'mg-MG',
		'mk-MK',
		'ml-IN',
		'mn-MN',
		'mr-IN',
		'ms-MY',
		'mt-MT',
		'my-MM',
		'nb-NO',
		'ne-NP',
		'nl-NL',
		'nn-NO',
		'or-IN',
		'pa-IN',
		'pl-PL',
		'ps-AF',
		'pt-BR',
		'pt-PT',
		'qu-PE',
		'rm-CH',
		'ro-RO',
		'ru-RU',
		'sa-IN',
		'sk-SK',
		'sl-SI',
		'so-SO',
		'sq-AL',
		'sr-RS',
		'sv-SE',
		'sw-KE',
		'ta-IN',
		'te-IN',
		'tg-TJ',
		'th-TH',
		'tl-PH',
		'tl-ST',
		'tr-TR',
		'tt-RU',
		'uk-UA',
		'ur-PK',
		'uz-UZ',
		'vi-VN',
		'xh-ZA',
		'yi-DE',
		'yo-NG',
		'zh-CN',
		'zh-HK',
		'zh-TW',
		'zu-ZA'
	];

	public static $date_formats = [
		'M j, Y' => '%b %d, %Y',
		'F j, Y' => '%B %d, %Y',
		'j M Y' => '%d %b %Y',
		'j F Y' => '%d %B %Y',
		'j/n/y' => '%d/%m/%y',
		'n/j/y' => '%m/%d/%y',
		'd.m.Y' => '%d.%m.%Y'
	];

	public static $time_formats = [
		'g:i A' => '%I:%M %p',
		'H:i' => '%H:%M'
	];

	protected static $locale;

	/**
	 * @function     : init
	 *
	 * @documentation: Инициализирует систему локализации
	 *
	 *
	 * @throws \mcr\database\db_exception
	 * @throws l10n_exception
	 */
	public function init()
	{
		parent::init();

		// Берём значение из конфига, как локаль сайта.
		self::$locale = config('main::s_lang');

		if (INSTALLED) {
			dd(1);

			// если движок установлен,
			// то устанвливаем локаль сайта с конфига по умолчанию.
			$locale = $this->get_config_locale();
			Locale::setDefault($locale);

			// генерируем путь к кешу локали.
			$locale_path = MCR_CACHE_PATH.'l10n/'.$locale;

			// Если путь к кешу локали не существует.
			// Или не существует кеша фраз локали
			// или информации о локали
			if (!file_exists($locale_path) || (!file_exists($locale_path.'/.info') || !file_exists($locale_path.'/.cache'))) {
				// Проверяем на существование директории l10n
				if (!file_exists(MCR_CACHE_PATH.'l10n')) {
					mkdir(MCR_CACHE_PATH.'l10n');
				}
				// проверяем на существование папки локали
				// в директории l10n
				if (!file_exists($locale_path)) {
					mkdir($locale_path);
				}

				// Если все маршруты к локалям есть,
				// но не найден .info или .cache,
				// то генерируем их на основании информации с базы.
				$this->set_cache();
			}
		}
	}

	/**
	 * @function     : get_config_locale
	 *
	 * @documentation: Метод для опеределиня языка
	 * из настроек сайта.
	 *
	 * @return string
	 */
	public static function get_config_locale()
	{
		$locale_pattern = "/([a-z]{2})-([A-Z]{2})/";

		if (preg_match($locale_pattern, self::$locale) == 1) {
			return self::$locale;
		}

		return 'ru-RU';
	}

	/**
	 * @function     : set_cache
	 *
	 * @documentation: Функция установки кеша.
	 *
	 * @param bool $locale
	 *
	 * @throws \mcr\database\db_exception
	 * @throws l10n_exception
	 */
	public function set_cache($locale = false)
	{
		// Если $locale принмает значение локали,
		// то создаём кеш для данной локали.
		// Иначе для той, которая установлена по умолчанию
		$locale = ($locale) ? $locale : Locale::getDefault();
		$locale_cache_path = MCR_CACHE_PATH.'l10n/'.$locale;

		if (!file_exists($locale_cache_path)) {
			mkdir(MCR_CACHE_PATH.'l10n/'.$locale);
		}

		// Получаем настройки и фразы локали из базы.
		$languages = $this->get_languages($locale, false)->fetch_assoc();

		// Создаём соответствующий кеш.
		file_put_contents($locale_cache_path.'/.info', $languages['settings']);
		file_put_contents($locale_cache_path.'/.cache', $languages['phrases']);
	}

	/**
	 * @function     : get_languages
	 *
	 * @documentation: Отдаёт спискок всех языков,
	 * если передано значение true. Иначе отдаёт список
	 * фраз и их значений, а также настройки и индитификатор
	 * для отдельного языка.
	 *
	 * @param string $language
	 * @param bool   $is_all
	 *
	 * @return \mysqli_result|null
	 * @throws \mcr\database\db_exception
	 * @throws l10n_exception
	 */
	public function get_languages($language = 'ru-RU', $is_all = true)
	{
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

		$query = db::query($sql);

		if ($query->result() && $query->num_rows > 0) {
			return $query->result();
		} else {
			throw new l10n_exception("l10n::get_languages(): Unable to get result from db. See logs.");
		}
	}

	/**
	 * @function     : get_user_locale
	 *
	 * @documentation: Метод для определения
	 * языка пользователя.
	 *
	 * @return string
	 */
	public function get_user_locale()
	{
		//TODO: Initialize user locale
		return '';
	}

	/**
	 * @function     : update_cache
	 *
	 * @documentation:
	 *
	 * @param        $locale
	 * @param string $route
	 *
	 * @throws \mcr\database\db_exception
	 * @throws l10n_exception
	 */
	public function update_cache($locale, $route = '')
	{
		$pattern = '/([a-z]{2})-([A-Z]{2})/';
		$locale = (preg_match($pattern, $locale) == 1) ? $locale : false;

		$locale_cache_path = MCR_CACHE_PATH.'l10n/'.$locale;

		if (!$locale || !file_exists($locale_cache_path)) {
			/*redirect($route);
			//$this->core->notify($this->gettext('error_message'), $this->gettext('error_locale_not_found'), 2, $route);*/
		}

		$languages = $this->get_languages($locale, false)->fetch_assoc();

		file_put_contents($locale_cache_path.'/.info', $languages['settings']);
		file_put_contents($locale_cache_path.'/.cache', $languages['phrases']);
	}

	/**
	 * @function     : gettext
	 *
	 * @documentation: Функция, которая ищит фразу во фразах языка,
	 * который был указан при вызове. Если такая фраза существует,
	 * отдаёт её содержимое. Иначе - саму фразу.
	 *
	 * @param      $phrase
	 *
	 * @param null $var
	 * @param null $locale
	 *
	 * @return string $value
	 */
	public function gettext($phrase, $locale = null)
	{
		// Функция может принять значение локали из которой нужно вернуть фразу.
		// По умолчанию это локаль, которая установленна в конфигурации, если $locale - пустое.
		$locale = (empty($locale)) ? $this->get_config_locale() : $locale;

		// Если переданная локаль не соответсвует необходимому формату,
		// то возвращаем непереведеную фразу.
		if (!preg_match('/([a-z]{2})-([A-Z]{2})/', $locale) == 1) {
			return $phrase;
		}

		$locale_cache_path = MCR_CACHE_PATH.'l10n/'.$locale;
		// Берём содержимое кеша фраз локали
		$phrases = file_get_contents($locale_cache_path.'/.cache');
		// Переводим из json => ассоциативный массив.
		$unjson_phrases = json_decode($phrases, true);

		// фразы численного значения в кеше хранятся в спец. обёртке.
		// Чтобы получить значение такой фразы, оборафиваем пришедчую для успешного сравненияю
		$closer = "_%s_";
		$phrase = (ctype_digit($phrase)) ? sprintf($closer, $phrase) : $phrase;

		// Перебираем все фразы. Если нашли, то возвращаем её.
		foreach ($unjson_phrases as $key => $value) {
			if ($phrase == $key) {
				return str_replace('`', '"', $value);
			}
		}

		// иначе ту, которая пришла.
		return $phrase;
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
	public function delete_cache($locales)
	{
		$locales = explode(', ', str_replace("'", '', $locales));

		foreach ($locales as $locale) {
			$locale_cache_path = MCR_CACHE_PATH.'l10n/'.$locale;
			if (!file_exists($locale_cache_path)) {
				continue;
			}

			$files = scandir($locale_cache_path);

			foreach ($files as $file) {
				if ($file != '.' && $file != '..') {
					unlink($locale_cache_path.'/'.$file);
				}
			}

			rmdir($locale_cache_path);
		}

		return false;
	}

	/**
	 * @function     : get_phrases
	 *
	 * @documentation:
	 *
	 * @param string $phrase
	 * @param bool   $is_all
	 *
	 * @return null
	 * @throws \mcr\database\db_exception
	 * @throws l10n_exception
	 */
	public function get_phrases($phrase = '', $is_all = true)
	{
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

		$query = db::query($sql);

		if ($query->result() && $query->num_rows > 0) {
			return $query->result();
		} else {
			throw new l10n_exception("l10n::get_phrases(): Unable to get result from db. See logs.");
		}
	}

	public function parse_date($datetime, array $tooltips = [])
	{
		if ($datetime instanceof \DateTime) {
			$type = 'datetime';
		} else {
			$type = 'unixtime';
		}

		$date = $this->localize($datetime, $type, $this->get_date_format());
		$time = $this->localize($datetime, $type, $this->get_time_format());

		if (trim($date) && isset($tooltips['date'])) {
			$text = $this->gettext($tooltips['date']);

			$date = '<div class="date" rel="tooltip" title="'.$text.'">'.$date.'</div>';
		}

		if (trim($time) && isset($tooltips['time'])) {
			$text = $this->gettext($tooltips['time']);

			$time = '<div class="time" rel="tooltip" title="'.$text.'">'.$time.'</div>';
		}

		return $date." {$this->gettext('in')} ".$time;
	}

	/**
	 * @function     : localize
	 *
	 * @documentation:
	 *
	 * @param        $text
	 * @param string $type
	 * @param string $datetime_format
	 *
	 * @return string
	 */
	public function localize($text, $type = 'string', $datetime_format = '')
	{
		$l_text = '';

		switch ($type) {
			case 'string':
				break;
			case 'date':
				break;
			case 'unixtime':
				$l_text = $this->localize_detetime($datetime_format, $text);
				break;
			case 'datetime':
				$datetime = new \DateTime($text);
				$dt_unix = $datetime->format("U");

				$l_text = $this->localize_detetime($datetime_format, $dt_unix);
				break;
			case 'double':
				break;
			default:
				$l_text = $text;
				break;
		}

		return $l_text;
	}

	/**
	 * @function     : localize_detetime
	 *
	 * @documentation:
	 *
	 * @param $format
	 * @param $timestamp
	 *
	 * @return string
	 */
	public function localize_detetime($format, $timestamp)
	{
		if (!empty($timestamp)) {
			$locale = $this->get_locale();
			$date_str = strftime($format, $timestamp);

			if (strpos($locale, '1251') !== false) {
				return iconv('cp1251', 'utf-8', $date_str);
			} else {
				return $date_str;
			}
		}

		return '';
	}

	/**
	 * @function     : get_config_locale
	 *
	 * @documentation: Задаёт локаль для локализации даты и прочего.
	 *               Локаль устанавливается на основании данных системы
	 *                 на которой установлен интерпретатор php.
	 *
	 *               Если язык(локаль) не найден в системе на которой стоит интерпретатор,
	 *                 то по умолчанию будет установлена локаль en_EU.
	 *
	 * @return string
	 */
	public function get_locale()
	{
		$default_locale = Locale::getDefault();
		// изменяем формат локали в тот, который понимает функция установки локали.
		$dl_formated = str_replace('-', '_', $default_locale);

		// Определяем навзание язіка из локали на английском.
		$language = Locale::getDisplayLanguage($default_locale, 'en-US');

		$locale = setlocale(LC_ALL, $dl_formated.'.UTF-8', $language);

		return $locale;
	}

	/**
	 * @function     : get_date_format
	 *
	 * @documentation: Функция для получения формата даты по умолчанию.
	 * Информация о формате берётся из настроек локали.
	 * Настройки определяются на сайте по маршруту:
	 * /?mode=admin&do=l10n_languages&op=edit&language=:locale_id:
	 *
	 * @return mixed|string
	 */
	public static function get_date_format()
	{
		$date_format = self::get_locale_info('date_format');

		if (array_key_exists($date_format, self::$date_formats)) {
			return self::$date_formats[$date_format];
		}

		return '%d %b %Y';
	}

	/**
	 * @function     : get_locale_info
	 *
	 * @documentation: Возвращает инормацию о текущем,
	 * выбранном языке в виде объекта.
	 *
	 * @param $key
	 *
	 * @return null
	 */
	public static function get_locale_info($key)
	{
		$locale = self::get_config_locale();
		$locale_info_path = MCR_CACHE_PATH.'l10n/'.$locale;
		$locale_info = file_get_contents($locale_info_path.'/.info');

		$locale_info = json_decode($locale_info, true);

		if (array_key_exists($key, $locale_info)) {
			return $locale_info[$key];
		}

		return null;
	}

	public static function get_time_format()
	{
		$time_format = self::get_locale_info('time_format');

		if (array_key_exists($time_format, self::$time_formats)) {
			return self::$time_formats[$time_format];
		}

		return '%R';
	}
}
