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
 * @Date  : 29.07.2018
 * @Time  : 15:05
 */

namespace mcr\installer;


use mcr\cache\cache_exception;
use mcr\config;
use mcr\core\mcr_registry;
use mcr\http\request;
use mcr\options;
use mcr\exception\exception_handler;
use mcr\hashing\bcrypt_hasher;

class install
{
	/**
	 * Версия установщика.
	 */
	const version = '1.0.21';

	/**
	 * Время инициализации установщика.
	 *  -- timestamp
	 *
	 * @var int
	 */
	public static $init_start_time = 0000000000;

	/**
	 * @var string
	 */
	public $step_handler = '';

	/**
	 * Заголовко страници установки
	 *
	 * @var string
	 */
	public static $page_title;

	/**
	 * @var array
	 */
	public static $steps = [
		'start'  => 0,
		'step_1' => 1,
		'step_2' => 2,
		'step_3' => 3,
		'finish' => 6,
	];

	/**
	 * @var int
	 */
	public static $current_step = 0;

	/**
	 * install_v2 constructor.
	 *
	 * @param $installation_init_start_time
	 */
	public function __construct($installation_init_start_time, config $configs)
	{
		self::$init_start_time = $installation_init_start_time;

		try {
			mcr_registry::set([
				mcr_registry::configs => $configs,
				mcr_registry::cache => \mcr\cache\cache::instance(),
				mcr_registry::options => options::get_instance($configs),

				mcr_registry::hasher => new bcrypt_hasher(),

				mcr_registry::request => new request(),
			]);
		} catch (cache_exception $e) {
			$this->handle_error($e);
		}

		$status = $handler = $additional_info = null;
		require 'install_router.php';

		$this->step_handler = $handler;
	}

	/**
	 * @var array
	 */
	public static $tables = [
		// local-tables
		//
		// Таблицы, которые можно создать
		// в любом порядке их добавления.
		// В них нету внешних связей, но с
		// других таблиц могут быть связит к ним.
		// Порядок: по алфавиту
		'mcr_configs',
		'mcr_l10n_languages',
		'mcr_l10n_phrases',
		'mcr_menu',
		'mcr_menu_adm_groups',
		'mcr_menu_adm_icons',
		'mcr_monitoring',
		'mcr_online',
		'mcr_permissions',

		// menu-tables
		//
		// Таблица меню админки.
		// Требует наличия групп меню админки.
		// Порядок: первая после local-tables
		'mcr_menu_adm',

		// users-tables
		//
		// Таблица пользователей
		// и таблица групп пользователей
		// Порядок: первый после local-tables и menu-tables
		'mcr_groups',
		'mcr_users',

		// Таблицы, которые требуют наличия
		// таблицы с пользователями
		// Порядок: первые после users-tables.
		// Внутри: в зависимости от связей.
		'mcr_iconomy',
		'mcr_files',
		'mcr_logs',
		'mcr_logs_of_edit',
		'mcr_users_comments',
		'mcr_statics',

		// news-tables
		//
		// Таблицы новостей
		// Требуют наличия таблицы пользователей
		// Внутренний порядок: зависит от связей
		'mcr_news_cats',
		'mcr_news',
		'mcr_news_comments',
		'mcr_news_views',
		'mcr_news_votes',
	];

	/**
	 *
	 */
	public function run_installation()
	{
		try {

			list($step, $action) = explode('@', $this->step_handler);

			$content = $this->handle($step, $action);
			$title = self::$page_title;

			$installation_page = tmpl('index',
				compact('title', 'content', 'step')
			);

			response()->content($installation_page);

		} catch (\Exception $e) {
			$this->handle_error($e);
		}
	}

	private function handle_error(\Exception $e)
	{
		$exception = new exception_handler($e, [
			'log' => true,
			'throw_on_screen' => config('mcr::app.debug'),
		]);

		// Если возникли исключения:
		// запускаем обработчик исключений,
		// Выводим сообщение о том, что случилась ошибка.
		// Если включён debug, то будет выведено и исключение
		$exception->handle()->throw_on_screen();
	}

	/**
	 * @param $step
	 * @param $action
	 *
	 * @return mixed
	 */
	private function handle($step, $action)
	{
		if ($this->check_step($step)) {
			$step = '\mcr\installer\modules\\' . $step;

			if (class_exists($step)) {
				/** @var \mcr\installer\modules\install_step $step */
				$step = new $step();

				if (method_exists($step, $action)) {
					return $step->$action();
				} else {
					throw new \UnexpectedValueException("Unexpected step handler " . get_class($step) . "@$action. Contact the team MagicMCR.");
				}
			} else {
				throw new \UnexpectedValueException("Unexpected step " . get_class($step) . ". Contact the team MagicMCR.");
			}
		}
	}

	/**
	 * @param $phrase
	 *
	 * @return mixed
	 */
	public function translate($phrase)
	{
		$phrases = include 'phrases.php';

		if (array_key_exists($phrase, $phrases)) {
			return $phrases[$phrase];
		}

		return $phrase;
	}

	/**
	 * @return array|mixed
	 */
	public function render_messages()
	{
		$messages = isset($_SESSION['messages']) ? $_SESSION['messages'] : [];
		$messages = tmpl('notify', compact('messages'));

		unset($_SESSION['messages']);

		return $messages;
	}

	/**
	 * @return array
	 */
	private static function passed_steps()
	{
		$passed_steps = MCR_ROOT . 'data/tmp/.install-passed-steps';

		if (!file_exists($passed_steps)) file_put_contents($passed_steps, 'null');

		return explode(',', file_get_contents($passed_steps));
	}

	/**
	 * @param $step
	 *
	 * @return bool
	 */
	public static function step_is_passed($step)
	{
		$passed_steps = self::passed_steps();

		if (in_array($step, $passed_steps)) {
			return true;
		}

		return false;
	}

	/**
	 * @param $step
	 *
	 * @return bool
	 */
	public static function is_greater($step)
	{
		$last_passed_step = max(self::passed_steps());

		$current_step_number = self::$steps[$step];
		$next_step_number = ($last_passed_step == 'null' ? -1 : self::$steps[$last_passed_step]) + 1;

		if ($current_step_number > $next_step_number) return true;

		return false;
	}

	/**
	 * @param $step
	 *
	 * @return bool
	 */
	private function check_step($step)
	{
		if ($step != 'reinstall') {
			if (self::step_is_passed($step) || self::is_greater($step)) {
				self::$current_step = self::$steps[$step];

				self::to_next_step();
			}
		}

		return true;
	}

	/**
	 * @param $step
	 */
	public static function remember_step($step)
	{
		$passed_steps = implode(',', self::passed_steps());

		if ($passed_steps == 'null') {
			$passed_steps = $step;
		} else {
			$passed_steps .= ",$step";
		}

		file_put_contents(MCR_ROOT . 'data/tmp/.install-passed-steps', $passed_steps);
	}

	/**
	 *
	 */
	public static function forget_steps()
	{
		file_put_contents(MCR_ROOT . 'data/tmp/.install-passed-steps', 'null');
	}

	/**
	 *
	 */
	public static function to_next_step()
	{
		$fliped_steps = array_flip(self::$steps);
		$last_passed_step = max(self::passed_steps());
		$last_passed_step_number = $last_passed_step == 'null' ? -1 : self::$steps[$last_passed_step];

		$next_step = $fliped_steps[$last_passed_step_number+1];

		redirect()->url("/install/index.php?$next_step/");
	}
}

function installer($component)
{
	return mcr_registry::get($component);
}