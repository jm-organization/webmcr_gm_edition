<?php
/**
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

/**
 * Created in JM Organization.
 *
 * @e-mail: admin@jm-org.net
 * @Author: Magicmen
 *
 * @Date  : 29.07.2018
 * @Time  : 15:21
 */

use mcr\config;
use mcr\hashing\bcrypt_hasher;
use mcr\http\redirect_response;
use mcr\http\response;
use mcr\http\routing\router;
use function mcr\installer\installer;

if (!function_exists('bcrypt')) {
	/**
	 * Возвращает базовый путь.
	 *
	 * @return string
	 */
	function bcrypt($value)
	{
		return installer('hasher')->make($value);
	}
}

if (!function_exists('base_url')) {
	/**
	 * Возвращает базовый путь.
	 *
	 * @return string
	 */
	function base_url()
	{
		return router::base_url() . 'install/';
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
		return config::get_instance()->get($namespace);
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

if (!function_exists('ip')) {
	/**
	 * Возвращает ip-адресс подключившегося.
	 *
	 * @return string
	 */
	function ip()
	{
		$ip = $_SERVER['REMOTE_ADDR'];

		return mb_substr($ip, 0, 16, "UTF-8");
	}
}

if (!function_exists('messages')) {
	/**
	 * Отрисовывает сообщения, которые возникли на странице
	 *
	 * @return array|mixed
	 */
	function messages()
	{
		global $installer;

		$messages = $installer->render_messages();

		return $messages;
	}
}

if (!function_exists('redirect')) {
	/**
	 * Возвращает ответ в виде перенаправления на другой аддрес.
	 *
	 * Если аддрес перенаправления не был передан,
	 * то вернёт экземпляр перенаправления.
	 *
	 * @param string $to
	 *
	 * @param int    $status
	 * @param array  $headers
	 *
	 * @return redirect_response
	 */
	function redirect($to = null, $status = 301, array $headers = [])
	{
		if (empty($to)) return new redirect_response();

		$redirect = new redirect_response($status, $headers);

		$redirect->url($to);

		exit;
	}
}

if (!function_exists('response')) {
	/**
	 * Генерирует ответ.
	 *
	 * Если не были переданы параметры,
	 * то вернёт экземпляр ответа.
	 * иначе отправит его.
	 *
	 * @param string $content
	 * @param int    $status
	 * @param array  $headers
	 *
	 * @return response
	 */
	function response($content = '', $status = 200, array $headers = [])
	{
		if (func_num_args() === 0) return new response();

		$response = new response($content, 'UTF-8', $status, $headers);

		$response->send();

		exit;
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
		$file = DIR_INSTALL_LAYOUTS . str_replace('.', '/', $tmpl) . '.phtml';

		ob_start();

		// Переводим пришедший масив в переменные
		// $$key = $value.
		// Если переменная имеет схожее имя с ранее объявленной - пропускаем её.
		extract($data, EXTR_SKIP);

		include $file;

		return ob_get_clean();
	}
}

if (!function_exists('translate')) {
	/**
	 * Возвращает значение фразы.
	 *
	 * @param $phrase
	 *
	 * @return mixed
	 */
	function translate($phrase)
	{
		global $installer;

		return $installer->translate($phrase);
	}
}

if (!function_exists('old')) {
	/**
	 * @param      $key
	 * @param null $default
	 *
	 * @return mixed
	 */
	function old($key, $default = null)
	{
		return installer('request')->old($key, $default);
	}
}

if (!function_exists('array_trim_value')) {
	/**
	 * Очищает значенеия масива от пробелов
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	function array_trim_value(array $array)
	{
		return array_map(
			function($value) {
				return trim($value);
			},
			$array
		);
	}
}

