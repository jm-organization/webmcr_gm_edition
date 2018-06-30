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

namespace mcr;

use mcr\database\db_connection;
use mcr\http\request;
use mcr\http\router;
use modules\module;

//use modules\module;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

global $configs;

define("INSTALLED", $configs->main['install']);

class core_v2
{
	/**
	 * Содержит текущую конфигурацию приложения во время
	 * выполнения скрипта.
	 *
	 * @var config|object|null
	 */
	public $configs = null;

	/**
	 * Содержит экземпляр класса документа приложения.
	 *
	 * @var document|object|null
	 */
	private $document = null;

	/**
	 * Основное соединение с базой данных.
	 * Статично во время всего выполнения скрипта
	 *
	 * @var \mysqli|null
	 */
	public static $db_connection = null;

	public $csrf_time = 3600;

	/**
	 * core_v2 constructor.
	 *
	 * @param config $configs
	 *
	 * @documentation: Основной конструктор приложения.
	 * Запускает его инициализацию, инициализирует EventListener,
	 * подключенные модули.
	 */
	public function __construct(config $configs)
	{
		// Если приложение не установленно, то перенаправляем на скрипт установки
		/*if (!INSTALLED) {
			header("Location: /install/index.php");
		}*/

		// Сохранение конфигураций в локальную среду ядра.
		$this->configs = $configs;

		// Установка и сохранение соединения с базой данных.
		self::$db_connection = new db_connection($configs);
	}

	public function gen_csrf_secure()
	{
		/*$time = time();
		$new_key = $time . '_' . md5($this->user->ip . $this->cfg->main['mcr_secury'] . $time);

		if (!isset($_COOKIE['mcr_secure'])) {
			setcookie("mcr_secure", $new_key, time() + $this->csrf_time, '/');
			return $new_key;
		}

		$cookie = explode('_', $_COOKIE['mcr_secure']);
		$old_time = intval($cookie[0]);
		$old_key = md5($this->user->ip . $this->cfg->main['mcr_secury'] . $old_time);

		if (!isset($cookie[1]) || $cookie[1] !== $old_key || ($old_time + $this->csrf_time) < $time) {
			setcookie("mcr_secure", $new_key, time() + $this->csrf_time, '/');
			return $new_key;
		}

		return $_COOKIE['mcr_secure'];*/
		return '';
	}

	public function version()
	{
		echo VERSION;
	}

	/**
	 *
	 */
	public function run()
	{
		global $log;

		$router = new router();
		$request = new request();


		////////////////////////////////////////////////////////////////////////////
		// Определение системных констант приложения.
		////////////////////////////////////////////////////////////////////////////

		// CSRF ключ защиты  -------------------------------------------------------
		define("MCR_SECURE_KEY", 	$this->gen_csrf_secure());


		// Системные константы  ----------------------------------------------------
		define('MCR_LANG', 			config('main::s_lang'));
		define('MCR_LANG_DIR', 		MCR_LANG_PATH . MCR_LANG . '/');
		define('MCR_THEME_PATH', 	MCR_ROOT . 'themes/' . config('main::s_theme') . '/');
		define('MCR_THEME_MOD', 	MCR_THEME_PATH . 'modules/');
		define('MCR_THEME_BLOCK',	MCR_THEME_PATH . 'blocks/');


		// MCR ссылки, маршруты  ---------------------------------------------------
		$base_url = (INSTALLED) ? config('main::s_root') : $router->base_url();
		define('BASE_URL', 			$base_url);
		define('ADMIN_MOD', 		'mode=admin');
		define('ADMIN_URL', 		BASE_URL . '?' . ADMIN_MOD);

		$mode_url = BASE_URL . '?mode=' . config('main::s_dpage');
		if (ie($request->mode)) {
			$mode_url =  BASE_URL . '?mode=' . filter($request->mode, 'chars');
		}
		define('MOD_URL', 			$mode_url);
		define('STYLE_URL', 		BASE_URL . 'themes/' . config('main::s_theme') . '/');
		define('UPLOAD_URL', 		BASE_URL . 'uploads/');
		define('LANG_URL', 			BASE_URL . 'language/' . MCR_LANG . '/');

		// Пути к плащам и скинам  -------------------------------------------------
		define('SKIN_URL', 			BASE_URL . config('main::skin_path'));
		define('MCR_SKIN_PATH', 	MCR_ROOT . config('main::skin_path'));
		define('CLOAK_URL', 		BASE_URL . config('main::cloak_path'));
		define('MCR_CLOAK_PATH', 	MCR_ROOT . config('main::cloak_path'));


		////////////////////////////////////////////////////////////////////////////
		// Инициализация текущего модуля приложения
		////////////////////////////////////////////////////////////////////////////

		$module = $this->initialize($router->controller);

		try {

			$module->content($request);

		} catch (\Exception $e) {

			// Создаём запись в лог файле
			$log->write($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine());
			?>

			Fatal Error

			<?php

		}

		//dd($module instanceof module);
		/*$document = @$module->content($request);

		$document = new document($document);

		$document->render();*/
	}

	/**
	 * @return document|null|object
	 */
	public function get_document()
	{
		return $this->document;
	}

	/**
	 * @param document|null|object $document
	 */
	public function set_document(document $document)
	{
		$this->document = $document;
	}

	/**
	 * @param string $module
	 *
	 * @return module|bool
	 */
	private function initialize($module)
	{
		$class = MCR_ROOT . $module . '.php';
		// Если файл модуля найден, погружаем его.
		load_if_exist($class);


		// Если класс модуля доступен - инициализируем модуль
		// и возвращаем экземпляр объекта модуля.
		if (class_exists($module)) {

			return new $module();

		}

		return false;
	}
}