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
 * @Date  : 11.08.2018
 * @Time  : 11:31
 */

namespace mcr\core\application;


use mcr\auth\auth;
use mcr\core\configs\config;
use mcr\core\core_v2;
use mcr\core\registry\mcr_registry;
use mcr\http\csrf;
use mcr\http\request;
use mcr\http\routing\router;
use mcr\l10n\l10n;

define("INSTALLED", installed()->status);

class application
{
	use csrf,
		l10n,
		dispatcher
	;

	/**
	 * @var core_v2
	 */
	public $core;

	/**
	 * @var request
	 */
	private $request;

	/**
	 * @var \mcr\http\routing\router
	 */
	private $router;

	/**
	 * @var array
	 */
	public $registry;

	/**
	 * application constructor.
	 *
	 * @param config $configs
	 *
	 * @throws \mcr\database\db_exception
	 */
	public function __construct(config $configs)
	{
		// Если приложение не установленно, то перенаправляем на скрипт установки
		if (!INSTALLED) header("Location: /install/index.php");

		$this->core = core_v2::get_instance($configs);

		$this->registry = mcr_registry::get_registry();
	}

	/**
	 * Запускает приложение.
	 *
	 * Проверяет csrf ключ на валидность
	 * Определяет константы для работы приложения
	 * Инициализирует модуль
	 * Создаёт и отрисовывает документ
	 *
	 * @return \mcr\http\redirect_response|\mcr\http\response|bool
	 */
	public function run()
	{
		try {

			$this->init_l10n();

			$this->request = new request();
			$this->router = new router($this->request);

			// Защищаемся от CSRF
			// Проверяем пришедший ключ csrf.
			// Если вовсе не пришёл, функция вернёт ложь - редиректим.
			// Если всё ок, то продолжаем загрузку страници
			// ---------------------------------------------------------
			// Если ip юзера есть в белов списике,
			// проверка ключа не будет произведена
			if (!$this->csrf_check()) return redirect()->with('message', ['text' => translate('error_hack')])->route('home');

			////////////////////////////////////////////////////////////////////////////
			// Получение авторизированых пользователей.
			// Инициализация расширения ядра auth
			////////////////////////////////////////////////////////////////////////////
			auth::init();

			////////////////////////////////////////////////////////////////////////////
			// Определение системных констант приложения.
			////////////////////////////////////////////////////////////////////////////

			// Системные константы  ----------------------------------------------------
			define('MCR_LANG', 			config('mcr::site.locale'));
			define('MCR_LANG_DIR', 		MCR_LANG_PATH . MCR_LANG . '/');
			define('MCR_THEME_PATH', 	MCR_ROOT . 'themes/' . config('mcr::site.theme') . '/');
			define('MCR_THEME_MOD', 	MCR_THEME_PATH . 'modules/');
			define('MCR_THEME_BLOCK',	MCR_THEME_PATH . 'blocks/');


			// MCR ссылки, маршруты  ---------------------------------------------------
			$base_url = (INSTALLED) ? config('mcr::base_full_url') : router::base_url();
			define('BASE_URL', 			$base_url);
			define('ADMIN_MOD', 		'admin');
			define('ADMIN_URL', 		BASE_URL . '?' . ADMIN_MOD);
			define('UPLOAD_URL', 		BASE_URL . 'uploads/');

			// Пути к плащам и скинам  -------------------------------------------------
//			define('SKIN_URL', 			BASE_URL . config('mcr::skin_path'));
//			define('MCR_SKIN_PATH', 	MCR_ROOT . config('mcr::skin_path'));
//			define('CLOAK_URL', 		BASE_URL . config('mcr::cloak_path'));
//			define('MCR_CLOAK_PATH', 	MCR_ROOT . config('mcr::cloak_path'));

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
			$this->handle_exception($e);
		}

		return true;
	}

	public function component($name)
	{
		if (array_key_exists($name, $this->registry)) {
			return $this->registry[$name];
		}

		return null;
	}

	public function handle_exception(\Exception $e)
	{
		$core = $this->core;

		$core::handle_exception($e);
	}
}