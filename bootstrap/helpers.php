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

use engine\http\routing\url_builder;
use mcr\html\blocks\blocks_manager;
use mcr\html\blocks\blocks_manager_exception;
use mcr\html\document;
use mcr\http\redirect;
use mcr\http\response;
use mcr\http\routing\router;
use mcr\http\routing\route_collector;

if (!function_exists('asset')) {
	/**
	 * Возворащает путь к ресурсу, если не указано,
	 * что ресурсы необходимо вставить из файла.
	 *
	 * @param        $resorce   - ссылка на ресурс или путь к файлу,
	 *                          в котором прописаны необходимые ресурсы
	 * @param bool   $from_file - указывает на то,
	 *                          что необходимо вставить ресурсы из файла
	 * @param string $extension - необходим для указания расширения
	 *                          файла, с которого будут
	 *                          загружены ресурсы
	 *
	 * @return mixed
	 */
	function asset($resorce, $from_file = false, $extension = '.phtml')
	{
		if ($from_file) {

			ob_start();

			if (preg_match('/^(\.[a-z]*)/', $extension) != 1) {
				$extension = '.' . $extension;
			}

			$resorce = MCR_THEME_PATH . str_replace('.', '/', $resorce) . $extension;
			load_if_exist($resorce);

			return ob_get_clean();

		} else {
			$_base_url = router::base_url();

			return $_base_url . 'themes/' . config('main::s_theme') . '/' . $resorce;
		}
	}
}

if (!function_exists('blocks')) {
	/**
	 * @return blocks_manager|null
	 */
	function blocks()
	{
		return document::$blocks;
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

if (!function_exists('is_filled')) {
	/**
	 * Проверяет переменную на наличие и её заполненость.
	 *
	 * @param $key
	 *
	 * @return boolean
	 */
	function is_filled($key)
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
	 * @param string $to
	 *
	 * @return redirect
	 */
	function redirect($to = '')
	{
		return new redirect($to);
	}
}

if (!function_exists('response')) {
	/**
	 * @param        $content
	 * @param string $charset
	 * @param int    $status
	 * @param array  $headers
	 * @param bool   $only_headers
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

if (!function_exists('route')) {
	/**
	 * Строит url адрес, который понимает приложение.
	 * Подставляет необходимые перменные в адресс из масива переменных, где
	 * ключ значения - имя переменной в маршруте, а значение -
	 * данные, на которые будет изменена переменная в маршруте.
	 *
	 * @param       $route
	 * @param array $variables
	 *
	 * @return string
	 * @throws \engine\http\routing\url_builder_exception
	 */
	function url($route, array $variables = [])
	{
		$url_builder = new url_builder($route);

		$url = $url_builder->build($variables);

		return $url;
	}
}

if (!function_exists('scripts')) {
	/**
	 * Выводит набор скриптов для области $for
	 *
	 * @param $for
	 */
	function scripts($for)
	{
		if (array_key_exists($for, document::$scripts)) {
			echo document::$scripts[$for];
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

if (!function_exists('stylesheets')) {
	/**
	 * Выводит стили набор стилей
	 */
	function stylesheets()
	{
		echo document::$stylesheets;
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