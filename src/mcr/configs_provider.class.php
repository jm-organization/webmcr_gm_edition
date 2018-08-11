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

namespace mcr;


use mcr\cache\cache;
use mcr\cache\cache_exception;
use mcr\cache\cache_value;
use mcr\core\registry\component;
use mcr\database\db;
use mcr\database\db_exception;

class configs_provider implements component
{
	const name = 'site_settings.options';

	/**
	 * Основные конфиги приложения.
	 *
	 * @var mixed
	 */
	private static $configs;

	/**
	 * @var configs_provider|null
	 */
	private static $instance = null;

	/**
	 * Мотод должен возвращать строковое
	 * абстрактное имя комопнента.
	 *
	 * @return string
	 */
	public function get_abstract_name()
	{
		return '_configs_provider';
	}

	/**
	 * Вызывается, когда происходит
	 * инициализация - добовление компонента
	 * в реестр.
	 *
	 * Должен возвращать экземпляр класса component
	 *
	 * @return component
	 */
	public function boot()
	{
		global $configs;

		foreach (self::$configs as $configs_group => $value) {
			$configs->attach($configs_group, $value);
		}

		return $this;
	}

	/**
	 * Возвращаем экзмпляр поставщика конфигов приложения.
	 * В основном предоставляет конфиги из кеша.
	 * Если данного кеша не было обнаружено,
	 * то будет возвращены данные из базы,
	 * но они также будут закешированы.
	 *
	 * @param config|null $configs
	 *
	 * @return configs_provider|null
	 */
	public static function get_instance()
	{
		if (empty(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * configs_provider constructor.
	 */
	private function __construct()
	{
		try {

			self::$configs = cache::get(self::name)
			                      ->deserialize()
			                      ->pick();

		} catch (cache_exception $e) {

			self::$configs = $this->set_options_cache();

		}
	}

	private function __wakeup() { }

	private function __clone() { }

	/**
	 * Производит выборку конфигов из
	 * таблици mcr_configs в базе данных.
	 *
	 * Если запрос к базе не удался, то будет возвращены дефолтные конфиги.
	 *
	 * @return mixed
	 */
	private function get_configs_from_db()
	{
		try {

//			$query_result = db::query("SELECT `option_key`, `option_value` FROM `mcr_configs`");
//			$options = $this->get_options_from_db_result($query_result);
			$options = db::table('configs')->select('option_key', 'option_value')->get();

		} catch (db_exception $e) {

			$options = self::get_default_configs();

		}

		return $options;
	}

	/**
	 * Получет конфиги из базы.
	 * Пытается построить по ним кеш. Если этого не удалось, то
	 * прекращаем выполнение скрипта.
	 *
	 * При удачном кешировании возвращаем данные,
	 * которые были получены из базы.
	 *
	 * @return mixed
	 */
	private function set_options_cache()
	{
		$configs_from_db = $this->get_configs_from_db();
		$value = new cache_value($configs_from_db);

		try {
			$value->serialize();
		} catch (cache_exception $e) {
			die('Whoops, looks like something went wrong.');
		}

		cache::set(self::name, $value);

		return $configs_from_db;
	}

	/**
	 * Возвращает дефолтные конфиги приложения.
	 *
	 * @return mixed
	 */
	public static function get_default_configs()
	{
		return require 'mcr_default_options.php';
	}
}