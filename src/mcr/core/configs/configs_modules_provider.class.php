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
 * @Time  : 19:36
 */

namespace mcr\core\configs;


use mcr\database\db;
use mcr\database\db_exception;

final class configs_modules_provider extends abstract_configs_provider implements provider
{
	const cache_name = 'site_settings.modules_configs';

	public static $configs;

	/**
	 * Мотод должен возвращать строковое
	 * абстрактное имя поставщика конфигов.
	 *
	 * @return string
	 */
	public function get_abstract_name()
	{
		return 'modules_configs';
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
		parent::boot($configs);
	}

	/**
	 * configs_modules_provider constructor.
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
			$modules = [];
			$modules_configs = db::table('modules_configs')
			             ->select(
			             	'module_id',
			                'configs',
			                'name',
			                'description',
			                'author',
			                'site',
			                'email',
			                'version',
			                'updation_url',
			                'checking_on_update'
			             )->get();

			foreach ($modules_configs as $module_config) {
				$configs = empty($module_config['configs']) ?[]: unserialize($module_config['configs']);

				$modules[$module_config['module_id']] = [
					'configs'             => $configs,
					'name'                => $module_config['name'],
					'description'         => $module_config['description'],
					'author'              => $module_config['author'],
					'site'                => $module_config['site'],
					'email'               => $module_config['email'],
					'version'             => $module_config['version'],
					'updation_url'        => $module_config['updation_url'],
					'checking_on_update'  => $module_config['checking_on_update'],
				];
			}

		} catch (db_exception $e) {
			$modules = [];
		}

		return [
			'components' => [
				'modules' => $modules,
			]
		];
	}
}