<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 26.06.2018
 * @Time         : 21:05
 *
 * @Documentation:
 */

use mcr\html\document;
use mcr\http\redirect;
use mcr\http\response;
use mcr\http\router;

if (!function_exists('asset')) {
	/**
	 * @param $resorce
	 *
	 * @return mixed
	 */
	function asset($resorce)
	{
		$_base_url = router::base_url();

		return $_base_url . 'themes/' . config('main::s_theme') . '/' . $resorce;
	}
}

if (!function_exists('colorize')) {
	/**
	 * Возвращает окрашенную строку $str в цвете $color
	 *
	 * @param        $str	 - строка, которую необходимо окрасить
	 * @param        $color  - цвет, в который будет окрашена строка
	 * @param string $format - формат, по которому будет окрашена строка
	 *
	 * @return mixed
	 */
	function colorize($str, $color, $format = '<span style="color: {COLOR};">{STRING}</span>')
	{
		return str_replace(['{COLOR}', '{STRING}'], [$color, $str], $format);
	}
}

if (!function_exists('config')) {
	/**
	 * Возвращает значение конфига по указанному неймспейсу
	 *
	 * main::{config_param} , где
	 * main - имя файла конфигурации
	 * из которого необходимо получить значения конфига
	 *
	 * {config_param} - имя конфига. если конфиг находится
	 * во влож масиве, то необходимо использовать точку.
	 *
	 * Пример: 'search::news.sys_search_news'
	 * или: 'modules::close.MOD_TITLE'
	 *
	 *
	 * @param  string 	$namespace
	 *
	 * @return mixed
	 */
	function config($namespace)
	{
		global $configs;

		$namespace = explode('::', $namespace);

		if (count($namespace) == 2) {
			$config_root = $namespace[0];
			$config_param = $namespace[1];

			$config = @$configs->$config_root;

			if (!empty($config)) {
				$config_param_items = explode('.', $config_param);

				foreach ($config_param_items as $item) {
					if (array_key_exists($item, $config)) {
						$config = $config[$item];
					}
				}

				return $config;
			}
		} else {
			$property = $namespace[0];

			return @$configs->$property;
		}

		return null;
	}
}

if (!function_exists('configs')) {
	/**
	 * Возвращает все конфиги
	 *
	 * @return stdClass
	 */
	function configs()
	{
		global $configs;

		return $configs->all();
	}
}

if (!function_exists('ie')) {
	/**
	 * Проверяет переменную на наличие и её заполненость.
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	function ie($key)
	{
		return isset($key) && !empty($key);
	}
}

if (!function_exists('menu')) {
	/**
	 * Возвращает экзмепляр меню
	 *
	 * @return \mcr\html\menu
	 */
	function menu()
	{
		$menu = document::$menu;

		if (empty($menu)) {
			throw new RuntimeException('Can`t build menu.');
		}

		return $menu;
	}
}

if (!function_exists('passwd_hash')) {
	/**
	 * Системный генератор хэшей паролей пользователей
	 *
	 * @param string $string - исходный пароль
	 * @param string $salt - соль
	 *
	 * @return string
	 */
	function passwd_hash($string, $salt = '')
	{
		global $application;

		$hasher = $application::$hasher;

		$password = $hasher->make($string . $salt);

		return $password;
	}
}

if (!function_exists('redirect')) {
	/**
	 * @function     : response
	 *
	 * @documentation:
	 *
	 * @param string $to
	 *
	 * @return redirect
	 */
	function redirect($to = '') {
		return new redirect($to);
	}
}

if (!function_exists('response')) {
	/**
	 * @function     : response
	 *
	 * @documentation:
	 *
	 * @param        $content
	 * @param string $charset
	 * @param int    $status
	 * @param array  $headers
	 */
	function response($content, $charset = 'UTF-8', $status = 200, array $headers = array(), $only_headers = false) {
		$response =  new response($content, $charset, $status, $headers);

		if ($only_headers) {
			$response->send_headers();
		} else {
			$response->send();
		}
	}
}

if (!function_exists('script')) {
	/**
	 * При вызове добавляет в глобальный
	 * регистр скриптов документа указаный скрипт (ссылку на него).
	 *
	 * @param        $in	  - Определяет, где будет выведен скрипт
	 * @param        $script  - адрес к скрипту.
	 * @param string $type    - Указывает тип MIME для определенного языка.
	 * @param null   $defer   - Откладывает выполнение скрипта до тех пор, пока вся страница не будет загружена полностью.
	 * @param bool   $async   - Асинхронное выполнение скрипта.
	 * @param bool   $prepend - Добавляет скрипт в начало списка.
	 */
	function script($in, $script, $type = 'text/javascript', $defer = null, $async = false, $prepend = false)
	{
		$_script = '<script type="' . $type . '"';

		// Усли указао ожидание страници true, то добавляем соответсв. атрибут
		if (is_bool($defer) && $defer) {
			$_script .= ' defer';
		}

		// Если включено асинхронное выполнение -
		// добавляем соотвевующий атрибут.
		if ($async) {
			$_script .= ' async';
		}

		// Если пришёл урл
		if (preg_match("/^((https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6}))?([\/\w \.-]*)*\/?$/i", $script) == 1) {
			$_script .= ' src="' . $script . '"></script>';
		} else {
			$_script .= '>' . $script . '</script>';
		}

		if (!array_key_exists($in, document::$scripts)) {
			$in = 'body';
		}

		if ($prepend) {
			array_unshift(document::$scripts[$in], $_script);
		} else {
			array_push(document::$scripts[$in], $_script);
		}

	}
}

if (!function_exists('scripts')) {
	/**
	 * Выводит прописанные скрипты из глобального регистра скриптов
	 */
	function scripts($for)
	{
		if (array_key_exists($for, document::$scripts)) {
			foreach (document::$scripts[$for] as $script) {
				echo $script;
			}
		}
	}
}

if (!function_exists('str_random')) {
	/**
	 * Генератор случайной строки
	 *
	 * @param 	$length - длина строки (integer)
	 * @param 	$safe - По умолчанию строка будет состоять только из латинских букв и цифр (boolean)
	 *
	 * @return 	string
	 */
	function str_random($length = 10, $safe = true)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
		if (!$safe) {
			$chars .= '$()#@!';
		}

		$string = "";
		$len = strlen($chars) - 1;

		while (strlen($string) < $length) {
			$string .= $chars[mt_rand(0, $len)];
		}

		return $string;
	}
}

if (!function_exists('stylesheet')) {
	/**
	 * При вызове добавляет в глобальный
	 * регистр стилей документа указаный стиль, ссылку на него.
	 *
	 * @param      $stylesheets
	 * @param bool $prepend
	 */
	function stylesheet($stylesheets, $prepend = false)
	{
		$array_add = 'array_';
		if ($prepend) {
			$array_add .= 'unshift';
		} else {
			$array_add .= 'push';
		}

		if (is_array($stylesheets)) {
			array_map(function($stylesheet) use ($array_add) {

				$array_add(document::$stylesheets, $stylesheet);

			}, $stylesheets);
		} else {
			if (is_string($stylesheets)) $array_add(document::$stylesheets, $stylesheets);
		}
	}
}

if (!function_exists('stylesheets')) {
	/**
	 * Выводит стили, которые находятся в
	 * глобальном регистре стилей.
	 *
	 * Глобальный регистр стилей находится по аддресу document::$stylesheets
	 */
	function stylesheets()
	{
		$stylesheets = document::$stylesheets;
		$_stylesheets = '';

		foreach ($stylesheets as $stylesheet) {
			// Если пришёл урл
			if (preg_match("/^((https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6}))?([\/\w \.-]*)*\/?$/i", $stylesheet) == 1) {
				$_stylesheets .= '<link rel="stylesheet" href="' . $stylesheet . '">';
			} else {
				$_stylesheets .= '<style>' . $stylesheet . '</style>';
			}
		}

		echo $_stylesheets;
	}
}

if (!function_exists('tmpl')) {
	/**
	 * Возвращает собранные шаблон эллемента документа
	 *
	 * @param       $tmpl - путь к шаблону,
	 *                    который будет использован для
	 *                    построения элемента документа
	 * @param array $data - данные, которые
	 *                    используются в эллементе
	 *
	 * @return mixed
	 */
	function tmpl($tmpl, array $data = [])
	{
		return document::template($tmpl, $data);
	}
}

if (!function_exists('translate')) {
	/**
	 * Проверяет пришедший тип фразы, если указан формат даты.
	 * Конвертирует фразу в
	 * время в зависимости от типа фразы.
	 *
	 * Возвращает перевелённое фремя.
	 *
	 *
	 * Если формат даты не был указан, то возвращает
	 * значение фразы, в зависимости от локали
	 *
	 * @param      $phrase
	 * @param      $locale
	 *
	 * @param null $date_format
	 *
	 * @return mixed
	 */
	function translate($phrase, $locale = null, $date_format = null)
	{
		global $application;

		if (!empty($date_format)) {
			$text_type = gettype($phrase);
			$time = null;

			switch ($text_type) {
				case 'string': $time = @strtotime($phrase); break;
				case 'integer': $time = $phrase; break;
				case 'object':

					if ($phrase instanceof \DateTime){
						$time = $phrase->format('U');
					} else {
						return $phrase;
					}

					break;
				default: return $phrase; break;
			}

			return $application->parse_date($time);

		}

		return $application->gettext($phrase, $locale);
	}
}