<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 22.07.2018
 * @Time         : 0:25
 *
 * @Documentation:
 */

namespace mcr\core;


trait cache
{
	public static $cache_factory;

	/**
	 * @throws \mcr\cache\cache_exception
	 */
	public function init_cache()
	{
		self::$cache_factory = new \mcr\cache\cache([
			'driver' => \mcr\cache\drivers\mcr_cache_driver::class,
			'enabled' => true,
			'expire' => 3600 * 24 * 30,
		]);
	}
}