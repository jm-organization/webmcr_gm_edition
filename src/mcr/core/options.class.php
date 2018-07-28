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
 * @Date  : 28.07.2018
 * @Time  : 17:15
 */

namespace mcr\core;


use mcr\cache\cache_exception;
use mcr\cache\cache_value;
use mcr\config;
use mcr\database\db;
use mcr\database\db_exception;
use mcr\database\db_result;

class options
{
	const name = 'site_settings.options';

	private static $options;

	private static $instance = null;

	private function __construct(config $configs)
	{
		if (INSTALLED) {
			try {

				// Берём кеш настроек
				$options = \mcr\cache\cache::get(self::name);
				$options->deserialize();

				// Десериализируем кеш и забираем содержимое.
				self::$options = $options->pick();

			} catch (cache_exception $e) {

				// Если кеш не найден
				// Создаём кеш настроек.
				$options = $this->set_options_cache();

				// Для текущей сессии возвращаем записанные в кеш настройки.
				self::$options = $options;

			}
		}
	}

	/**
	 * Возвращаем экзмпляр настроек приложения
	 *
	 * @param config|null $configs
	 *
	 * @return options|null
	 */
	public static function get_instance(config $configs = null)
	{
		if (empty(self::$instance)) {
			self::$instance = new self($configs);
		}

		return self::$instance;
	}

	private function get_options()
	{
		try {

			$query_result = db::query("SELECT `option_key`, `option_value` FROM `mcr_configs`");
			$options = $this->get_options_from_db_result($query_result);

		} catch (db_exception $e) {

			$options = $this->get_default_options();

		}

		return $options;
	}

	private function set_options_cache()
	{
		$options = $this->get_options();
		$value = new cache_value($options);

		try {
			$value->serialize();
		} catch (cache_exception $e) {
			die('Leave building...');
		}

		\mcr\cache\cache::set(self::name, $value);

		return $options;
	}

	private function get_default_options()
	{
		return require 'mcr_default_options.php';
	}

	private function get_options_from_db_result(db_result $result)
	{
		return $this->get_default_options();
	}
}