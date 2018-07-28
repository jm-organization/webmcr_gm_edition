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


use mcr\auth\auth;
use mcr\config;
use mcr\database\db_connection;
use mcr\exception\exception_handler;
use mcr\hashing\bcrypt_hasher;
use mcr\hashing\hasher;
use mcr\http;
use mcr\http\csrf;
use mcr\http\request;
use mcr\http\routing\router;
use mcr\l10n\l10n;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

global $configs;

define("INSTALLED", $configs->main['install']);

class core_v2
{
    use cache,
		l10n,
		csrf,
		dispatcher
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

		return $this;
	}

	/**
     * Метод, который делает инициализацию ядра.
     * Необходим для расширений ядра.
     *
	 * @return void
	 */
	public function init()
	{
		$core_extensions = class_uses($this);

		foreach ($core_extensions as $extension) {
			$namespace_class = explode('\\', $extension);
			$extension_short_name = end($namespace_class);

			$method = 'init_' . $extension_short_name;

			if (method_exists($this, $method)) {
				$this->$method();
			}
		}
	}

	/**
	 * Запускает приложение.
	 *
	 * Проверяет csrf ключ на валидность
	 * Определяет константы для работы приложения
	 * Инициализирует модуль
	 * Создаёт и отрисовывает документ
	 *
	 * @return bool|http\redirect_response
	 */
	public function run()
	{
		// Защищаемся от CSRF
		// Проверяем пришедший ключ csrf.
		// Если вовсе не пришёл, функция вернёт ложь - редиректим.
		// Если всё ок, то продолжаем загрузку страници
		// ---------------------------------------------------------
		// Если ip юзера есть в белов списике,
		// проверка ключа не будет произведена
		if (!$this->csrf_check()) return redirect()->with('message', ['text' => translate('error_hack')])->route('home');

		// Пытаемся запустить приложение
		try {
			$this->request = new request();
			$this->router = new router($this->request);

			// Инициализируем расширения ядра
			$this->init();

			$options = options::get_instance($this->configs);

            ////////////////////////////////////////////////////////////////////////////
            // Получение авторизированых пользователей.
            // Инициализация расширения ядра auth
            ////////////////////////////////////////////////////////////////////////////
            auth::init();

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
            if (is_filled($this->request->mode)) {
                $mode_url =  BASE_URL . '?mode=' . filter($this->request->mode, 'chars');
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
			define("MCR_SECURE_KEY", 	$this->gen_csrf_key());
			define('META_JSON_DATA',	 json_encode(array(
				'secure' => MCR_SECURE_KEY,
				'lang' => MCR_LANG,
				'base_url' => BASE_URL,
				'theme_url' => asset(''),
				'upload_url' => UPLOAD_URL,
				'server_time' => time(),
				'is_auth' => empty(auth::user()) ? false : true,
			)));

			// Компилируем приложение
            $this->dispatch($this);

		} catch (\Exception $e) {

			$exception = new exception_handler($e, [
				'log' => true,
				'throw_on_screen' => config('main::debug'),
			]);

			// Если возникли исключения:
			// запускаем обработчик исключений,
			// Выводим сообщение о том, что случилась ошибка.
			// Если включён debug, то будет выведено и исключение
			$exception->handle()->throw_on_screen();

		}

		return true;
	}

	public static function version()
	{
		echo VERSION;
	}
}