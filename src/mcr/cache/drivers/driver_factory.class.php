<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 22.07.2018
 * @Time         : 17:46
 */

namespace mcr\cache\drivers;


use mcr\cache\cache_driver;
use mcr\cache\cache_exception;

/**
 * Class driver_factory
 *
 * Фабрика драйверов.
 * Создаёт драйвер, если он реализует класс cache_driver
 *
 * @package mcr\cache\drivers
 */
class driver_factory
{
	/**
	 * @param $driver_name
	 *
	 * @return mixed
	 * @throws cache_exception
	 */
	public static function create_driver($driver_name)
	{
		$driver = new $driver_name();

		if (!self::is_driver($driver)) throw new cache_exception("Unexpected cache driver. Your passed driver must be implement \mcr\cache\cache_driver.");

		return $driver;
	}

	/**
	 * Проверет пришедший драйвер.
	 * Драйвер должен реализовывать cache_driver
	 *
	 * @param $driver
	 *
	 * @return bool
	 */
	private static function is_driver($driver)
	{
		return $driver instanceof cache_driver;
	}
}