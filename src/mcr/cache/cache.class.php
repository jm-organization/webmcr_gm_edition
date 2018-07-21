<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 21.07.2018
 * @Time         : 18:41
 *
 * @Documentation:
 */

namespace mcr\cache;


use mcr\cache\drivers\mcr_cache_driver;

/**
 * Class cache
 *
 * @package mcr\cache
 *
 * @method static void 			set($key, cache_value $value)
 * @method static cache_value 	get($key)
 * @method static void 			delete($key)
 *
 * @see cache_driver
 */
class cache
{
	/**
	 * Экземпляр драйвера.
	 *
	 * @var cache_driver|null
	 */
	public static $driver = null;

	/**
	 * Список, доступных для взяимодействия,
	 * методов драйвера.
	 *
	 * @var array
	 */
	private static $accessed = [
		'set',
		'get',
		'delete',
	];

	/**
	 * cache constructor.
	 *
	 * @param $options
	 *
	 * @throws cache_exception
	 */
	public function __construct($options)
	{
		$options += [
			'driver' => mcr_cache_driver::class,
			'enabled' => true,
			'expire' => 3600 * 24 * 30,
		];

		if ($options['enabled']) {
			self::$driver = new $options['driver']();

			if (!(self::$driver instanceof cache_driver)) {
				throw new cache_exception("Unexpected cache driver. Your passed driver must be implement \mcr\cache\cache_driver.");
			}
		}
	}

	/**
	 * Вызывает методы драйвера
	 * для взаимодействия с кешем.
	 *
	 * @param $method
	 * @param $arguments
	 *
	 * @return bool
	 * @throws cache_exception
	 */
	public static function __callStatic($method, $arguments)
	{
		if (!empty(self::$driver)) {
			if (method_exists(self::$driver, $method)) {
				if (!in_array($method, self::$accessed)) throw new cache_exception("Access denied for method $method.");

				return self::$driver->$method(...$arguments);
			}

			throw new cache_exception("Unknown method $method. $method not found in " . get_class(self::$driver));
		}

		return false;
	}

	/**
	 * Производит очистку директории от файлов у которых истёк ttl
	 */
	private static function clear_cache()
	{
		// TODO: implement clear_cache
	}
}