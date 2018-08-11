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
 * @Date  : 02.08.2018
 * @Time  : 22:01
 */

namespace mcr\core\registry;


use mcr\core\core_v2;

class mcr_registry
{
	/**
	 * @var array
	 */
	private static $components = [];

	/**
	 * @param $component
	 */
	public static function set($component)
	{
		$components = is_array($component) ? $component : func_get_args();

		foreach ($components as $component) {
			if ($component instanceof component) {

				$key = $component->boot()->get_abstract_name();

				self::add($key, $component);

			} else {
				throw new \UnexpectedValueException("mcr_registry::add(): You can only add classes that implement the registry component.");
			}
		}
	}

	/**
	 * @param $key
	 * @param $component
	 */
	private static function add($key, component $component)
	{
		self::$components[$key] = $component;
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public static function get($key)
	{
		if (!isset(self::$components[$key])) {
			throw new \UnexpectedValueException("mcr_registry::get(): Unknown component $key.");
		}

//		if (in_array($key, self::$private_components)) {
//			throw new
//		}

		return self::$components[$key];
	}

	public static function get_registry()
	{
		return self::$components;
	}
}