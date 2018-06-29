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

use mcr\document;

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
		}

		return null;
	}
}

/*if (!function_exists('translate')) {
	/**
	 * Возвращает значение фразы $phrase
	 *
	 * @param $phrase
	 * @param $locale
	 *
	 * @return mixed

	function translate($phrase, $locale = null)
	{
		global $application;

		return $application->gettext($phrase, $locale);
	}
}*/

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