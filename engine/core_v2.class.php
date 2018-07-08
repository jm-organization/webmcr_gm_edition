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

use mcr\auth\auth;
use mcr\database\db_connection;
use mcr\hashing\bcrypt_hasher;
use mcr\hashing\hasher;
use mcr\html\document;
use mcr\http\csrf;
use mcr\http\request;
use mcr\http\router;
use mcr\l10n\l10n;
use modules\module;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

global $configs;

define("INSTALLED", $configs->main['install']);

class core_v2
{
    use csrf,
        l10n
    ;

	/**
	 * Содержит текущую конфигурацию приложения во время
	 * выполнения скрипта.
	 *
	 * @var config|object|null
	 */
	public $configs = null;

	/**
	 * Основное соединение с базой данных.
	 * Статично во время всего выполнения скрипта
	 *
	 * @var \mysqli|null
	 */
	public static $db_connection = null;

	/**
	 * Содержит экземпляр класса,
	 * хеширующего пароли
	 *
	 * @var hasher|null
	 */
	public static $hasher = null;

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
		if (!INSTALLED) {
			return header("Location: /install/index.php");
		}

		// Сохранение конфигураций в локальную среду ядра.
		$this->configs = $configs;

		////////////////////////////////////////////////////////////////////////////
		// Инициализация Хашера паролей
		////////////////////////////////////////////////////////////////////////////
		self::$hasher = new bcrypt_hasher();

		// Установка и сохранение соединения с базой данных.
		self::$db_connection = new db_connection($configs);
	}

	/**
     * Метод, который делает инициализацию ядра.
     * Необходим для расширений ядра.
     *
	 * @return void
	 */
	public function init() { }

	public static function version()
	{
		echo VERSION;
	}

	public function run()
	{
		global $log;

		try {
		    // Инициализируем расширения ядра
		    $this->init();

            ////////////////////////////////////////////////////////////////////////////
            // Получение авторизированых пользователей.
            // Инициализация расширения ядра auth
            ////////////////////////////////////////////////////////////////////////////
            auth::init();

            $router = new router();
            $request = new request();

            ////////////////////////////////////////////////////////////////////////////
            // Определение системных констант приложения.
            ////////////////////////////////////////////////////////////////////////////

            // Системные константы  ----------------------------------------------------
            define('MCR_LANG', 			config('main::s_lang'));
            define('MCR_LANG_DIR', 		MCR_LANG_PATH . MCR_LANG . '/');
            define('MCR_THEME_PATH', 	MCR_ROOT . 'themes/' . config('main::s_theme') . '/');
            define('MCR_THEME_MOD', 	MCR_THEME_PATH . 'modules/');
            define('MCR_THEME_BLOCK',	MCR_THEME_PATH . 'blocks/');


            // MCR ссылки, маршруты  ---------------------------------------------------
            $base_url = (INSTALLED) ? config('main::s_root') : router::base_url();
            define('BASE_URL', 			$base_url);
            define('ADMIN_MOD', 		'mode=admin');
            define('ADMIN_URL', 		BASE_URL . '?' . ADMIN_MOD);

            $mode_url = BASE_URL . '?mode=' . config('main::s_dpage');
            if (is_filled($request->mode)) {
                $mode_url =  BASE_URL . '?mode=' . filter($request->mode, 'chars');
            }
            define('MOD_URL', 			$mode_url);
            define('UPLOAD_URL', 		BASE_URL . 'uploads/');
            define('LANG_URL', 			BASE_URL . 'language/' . MCR_LANG . '/');

            // Пути к плащам и скинам  -------------------------------------------------
            define('SKIN_URL', 			BASE_URL . config('main::skin_path'));
            define('MCR_SKIN_PATH', 	MCR_ROOT . config('main::skin_path'));
            define('CLOAK_URL', 		BASE_URL . config('main::cloak_path'));
            define('MCR_CLOAK_PATH', 	MCR_ROOT . config('main::cloak_path'));

			// CSRF ключ защиты  -------------------------------------------------------
			define("MCR_SECURE_KEY", 	$this->gen_csrf_secure());
			define('META_JSON_DATA',	 json_encode(array(
				'secure' => MCR_SECURE_KEY,
				'lang' => MCR_LANG,
				'base_url' => BASE_URL,
				'theme_url' => asset(''),
				'upload_url' => UPLOAD_URL,
				'server_time' => time(),
				//'is_auth' => $core->user->is_auth,
			)));


            ////////////////////////////////////////////////////////////////////////////
            // Инициализация текущего модуля приложения
            ////////////////////////////////////////////////////////////////////////////

            $module = $this->initialize($router->controller);

            if ($module) {
				$document = new document($module, $request);
				$document->render();
			} else {
				response('', 'utf8', 404, [], true);
			}

		} catch (\Exception $e) {

			// Создаём запись в лог файле
			$log->write($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine());

		}
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