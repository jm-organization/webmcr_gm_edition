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


use mcr\cache\drivers\driver_factory;
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
final class cache
{
	/**
	 * @var cache
	 */
	private static $instance;

	/**
	 * Экземпляр драйвера.
	 *
	 * @var cache_driver|null
	 */
	public static $driver = null;

	/**
	 * @var array
	 */
	public static $options = [];

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
	 * gets the instance via lazy initialization (created on first usage)
	 *
	 * @param array $options
	 *
	 * @return cache
	 * @throws cache_exception
	 */
	public static function instance(array $options = [])
	{
		if (empty(static::$instance)) {
			static::$instance = new static($options);
		}

		return static::$instance;
	}

	/**
	 * cache constructor.
	 *
	 * Предотвращаем повторное создание
	 * Singleton объекта \mcr\cache\cache через оператор new.
	 *
	 * @param $options
	 *
	 * @throws cache_exception
	 */
	private function __construct(array $options)
	{
		// Задаём настройки
		self::set_options($options);

		if (self::$options['enabled']) {
			// Если кешировнаие включено
			// создаём драйвер
			$driver = driver_factory::create_driver(self::$options['driver']);

			// и устанавливаем его.
			self::set_driver($driver);
		}
	}

	/**
	 * Предотвращаем клонирование обекта \mcr\cache\cache.
	 *
	 * Объект \mcr\cache\cache является Singleton
	 * и не может быть создан ещё раз после его создания.
	 */
	private function __clone() { }

	/**
	 * Предотвращаем создание обекта \mcr\cache\cache
	 * во время десериализации.
	 *
	 * Объект \mcr\cache\cache является Singleton
	 * и не может быть создан ещё раз после его создания.
	 */
	private function __wakeup() { }

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
	 * @param array $options
	 */
	public static function set_options(array $options)
	{
		$options += [
			'driver' => mcr_cache_driver::class,
			'enabled' => true,
			'expire' => 3600 * 24 * 30,
		];

		self::$options = $options;
	}

	/**
	 * @return cache_driver|null
	 */
	public static function get_driver()
	{
		return self::$driver;
	}

	/**
	 * @param cache_driver $driver
	 */
	public static function set_driver(cache_driver $driver)
	{
		self::$driver = $driver;
	}

	/**
	 * Производит очистку директории от файлов у которых истёк ttl
	 */
	private static function clear_cache()
	{
		// TODO: implement clear_cache
	}
}