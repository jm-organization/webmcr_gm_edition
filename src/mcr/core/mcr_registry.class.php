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

namespace mcr\core;


class mcr_registry
{
	const cache         = 'cache';
	const options       = 'options';
	const l10n          = 'l10n';
	const dispatcher    = 'dispatcher';
	const csrf          = 'csrf';
	const hasher        = 'hasher';
	const request       = 'request';
	const configs       = 'configs';

	/**
	 * @var array
	 */
	private static $allowed_components = [
		self::cache,
		self::options,
		self::l10n,
		self::dispatcher,
		self::csrf,
		self::hasher,
		self::request,
		self::configs
	];

	/**
	 * @var array
	 */
	private static $components = [];

	/**
	 * @param $component
	 */
	public static function set($component)
	{
		$args = func_get_args();

		$components = is_array($component) ? $component : [ $args[0] => $args[1] ];

		foreach ($components as $key => $component) {
			self::add($key, $component);
		}
	}

	/**
	 * @param $key
	 * @param $component
	 */
	private static function add($key, $component)
	{
		if (!in_array($key, self::$allowed_components)) {
			throw new \InvalidArgumentException("mcr_registry::set(): Invalid component name ('$key' given).");
		}

		self::$components[$key] = $component;
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public static function get($key)
	{
		if (!in_array($key, self::$allowed_components) || !isset(self::$components[$key])) {
			throw new \UnexpectedValueException("mcr_registry::get(): Unknown component $key.");
		}

		return self::$components[$key];
	}
}