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
 * @Time  : 22:30
 */

namespace mcr\core\configs;


use mcr\database\db;
use mcr\database\db_exception;

class configs_blocks_provider extends abstract_configs_provider implements provider
{
	const cache_name = 'site_settings.blocks_configs';

	public static $configs;

	/**
	 * Мотод должен возвращать строковое
	 * абстрактное имя поставщика конфигов.
	 *
	 * @return string
	 */
	public function get_abstract_name()
	{
		return 'blocks_configs';
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
			$blocks = [];
			$blocks_configs = db::table('blocks_configs')
			                     ->select(
				                     'block_id',
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

			foreach ($blocks_configs as $block_config) {
				$configs = empty($block_config['configs']) ?[]: unserialize($block_config['configs']);

				$blocks[$block_config['block_id']] = [
					'configs'             => $configs,
					'name'                => $block_config['name'],
					'description'         => $block_config['description'],
					'author'              => $block_config['author'],
					'site'                => $block_config['site'],
					'email'               => $block_config['email'],
					'version'             => $block_config['version'],
					'updation_url'        => $block_config['updation_url'],
					'checking_on_update'  => $block_config['checking_on_update'],
				];
			}

		} catch (db_exception $e) {
			$blocks = [];
		}

		return [
			'components' => [
				'blocks' => $blocks,
			]
		];
	}
}