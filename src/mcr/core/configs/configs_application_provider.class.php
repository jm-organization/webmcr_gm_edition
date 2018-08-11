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

namespace mcr\core\configs;


use mcr\core\registry\mcr_registry;
use mcr\database\db;
use mcr\database\db_exception;

final class configs_application_provider extends abstract_configs_provider implements provider
{
	const cache_name = 'site_settings.application_configs';

	/**
	 * Основные конфиги приложения.
	 *
	 * @var mixed
	 */
	public static $configs;

	/**
	 * Мотод должен возвращать строковое
	 * абстрактное имя комопнента.
	 *
	 * @return string
	 */
	public function get_abstract_name()
	{
		return 'application_configs';
	}

	/**
	 * Вызывается, когда происходит
	 * инициализация - добовление компонента
	 * в реестр.
	 *
	 * @param config $configs
	 */
	public function boot(config $configs)
	{
		parent::boot($configs);
	}

	/**
	 * configs_provider constructor.
	 *
	 * Возвращаем экзмпляр поставщика конфигов приложения.
	 * В основном предоставляет конфиги из кеша.
	 * Если данного кеша не было обнаружено,
	 * то будет возвращены данные из базы,
	 * но они также будут закешированы.
	 */
	public function __construct()
	{
		$this->get_configs_to_provide(self::cache_name);
	}

	/**
	 * Производит выборку конфигов из
	 * таблици mcr_configs в базе данных.
	 *
	 * Если запрос к базе не удался, то будет возвращены дефолтные конфиги.
	 *
	 * @return array
	 */
	public function get_configs_from_db()
	{
		try {

			$configs = db::table('configs')->pluck('option_value', 'option_key');
			$configs = mcr_registry::get('configs')->decompress($configs);

		} catch (db_exception $e) {
			$configs = self::get_default_configs();
		}

		return $configs;
	}

	/**
	 * Возвращает дефолтные конфиги приложения.
	 *
	 * @return array
	 */
	public static function get_default_configs()
	{
		return require 'mcr_default_options.php';
	}
}