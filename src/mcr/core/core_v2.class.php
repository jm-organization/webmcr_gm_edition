<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 26.06.2018
 * @Time         : 20:45
 *
 * @Documentation:
 */

namespace mcr\core;


use mcr\cache\cache;
use mcr\core\configs\config;
use mcr\core\configs\configs_application_provider;
use mcr\core\configs\configs_blocks_provider;
use mcr\core\configs\configs_modules_provider;
use mcr\core\registry\mcr_registry;
use mcr\database\db_connection;
use mcr\exception\exception_handler;
use mcr\hashing\bcrypt_hasher;

if (!defined("MCR")) exit("Hacking Attempt!");

class core_v2
{
	/**
	 * Содержит текущую конфигурацию приложения во время
	 * выполнения скрипта.
	 *
	 * @var config|null
	 */
	public static $configs;

	/**
	 * @var db_connection
	 */
	public static $db_connection;

	/**
	 * @var core_v2
	 */
	private static $instance;

	/**
	 * @param config $configs
	 *
	 * @return core_v2
	 */
	public static function get_instance(config $configs)
	{
		if (self::$instance == null) {
			self::$instance = new self($configs);
		}

		return self::$instance;
	}

	/**
	 * core_v2 constructor.
	 *
	 * @param config $configs
	 *
	 * @documentation: Основной конструктор приложения.
	 * Запускает его инициализацию, инициализирует EventListener,
	 * подключенные модули.
	 */
	private function __construct(config $configs)
	{
		// Сохранение конфигураций в локальную среду ядра.
		self::$configs = $configs;

		try {

			mcr_registry::set(new bcrypt_hasher(), $configs, cache::instance(self::$configs->get('mcr::cache')));

			// Установка и сохранение соединения с базой данных.
			self::$db_connection = new db_connection($configs);

			mcr_registry::get('configs')->bind(
				configs_application_provider::class,
				configs_modules_provider::class,
				configs_blocks_provider::class
			);

		} catch (\Exception $e) {
		    self::handle_exception($e);
		}

		return $this;
	}

	private function __wakeup()	{ }

	private function __clone()	{ }

	/**
	 * @param \Exception $e
	 */
	public static function handle_exception(\Exception $e)
	{
		$exception = new exception_handler($e, [
			'log' => true,
			'throw_on_screen' => self::$configs->get('mcr::app.debug'),
		]);

		// Если возникли исключения:
		// запускаем обработчик исключений,
		// Выводим сообщение о том, что случилась ошибка.
		// Если включён debug, то будет выведено и исключение
		$exception->handle()->throw_on_screen();
	}
}