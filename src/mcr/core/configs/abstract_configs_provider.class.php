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
 * @Date  : 11.08.2018
 * @Time  : 19:47
 */

namespace mcr\core\configs;


use mcr\cache\cache;
use mcr\cache\cache_exception;
use mcr\cache\cache_value;

abstract class abstract_configs_provider
{
	/**
	 * Основные конфиги приложения.
	 *
	 * @var array
	 */
	public static $configs;

	/**
	 * Пробует взять конфиги из кеша,
	 * иначе берёт из базы и создаёт кеш.
	 *
	 * Устанавлвиает полученные
	 * конфиги в локальную память.
	 *
	 * @param $provider_cache_name
	 */
	public function get_configs_to_provide($provider_cache_name)
	{
		try {

			self::$configs = cache::get($provider_cache_name)->deserialize()->pick();

		} catch (cache_exception $e) {

			self::$configs = $this->configs_to_cache($provider_cache_name, true);

		}
	}

	/**
	 * Вызывается, когда происходит
	 * инициализация - добовление компонента
	 * в реестр.
	 *
	 * Должен возвращать экземпляр класса component
	 *
	 * @param config $configs
	 */
	public function boot(config $configs)
	{
		foreach (self::$configs as $configs_group => $value) {
			$configs->attach($configs_group, $value);
		}
	}

	/**
	 * Производит выборку конфигов из
	 * таблици mcr_configs в базе данных.
	 *
	 * Если запрос к базе не удался, то будет возвращены дефолтные конфиги.
	 *
	 * @return array
	 */
	abstract public function get_configs_from_db();

	/**
	 * Абстрактный метод установки кеша конфигов
	 *
	 * @param      $provider_cache_name
	 * @param bool $return
	 *
	 * @return array
	 */
	public function configs_to_cache($provider_cache_name, $return = false)
	{
		$configs_from_db = $this->get_configs_from_db();
		$value = new cache_value($configs_from_db);

		try {
			$value->serialize();
		} catch (cache_exception $e) {
			die('Whoops, looks like something went wrong.');
		}

		cache::set($provider_cache_name, $value);

		if ($return) return $configs_from_db;
	}
}