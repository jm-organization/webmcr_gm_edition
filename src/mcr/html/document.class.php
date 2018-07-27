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
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 27.06.2018
 * @Time         : 22:58
 *
 * @Documentation:
 */

namespace mcr\html;


use mcr\html\blocks\blocks_manager;
use mcr\http\request;

use modules\base_module;
use modules\module;

class document
{
	/**
	 * @var string
	 */
	private $layout = 'global';

	/**
	 * @var string
	 */
	public static $title;

	/**
	 * @var string
	 */
	public $content;

	/**
	 * @var string
	 */
	public static $advice;

	/**
	 * @var blocks_manager|null
	 */
	public static $blocks = null;

	/**
	 * @var menu|null
	 */
	public static $menu = null;

	/**
	 * @var breadcrumbs|null
	 */
	public static $breadcrumbs = null;

	/**
	 * @var string
	 */
	public static $stylesheets;

	/**
	 * @var array
	 */
	public static $scripts = [ 'body' => '', 'head' => '' ];

	/**
	 * document constructor.
	 *
	 * @param base_module|module $module
	 * @param request            $request
	 *
	 * @throws blocks\blocks_manager_exception
	 */
	public function __construct(base_module $module, request $request, $action)
	{
		// Регистрируем меню
		self::$menu = new menu($request);
		// Регистрируем менеджер блоков
		self::$blocks = new blocks_manager();
		// Регистрируем хлембные крошки
		self::$breadcrumbs = new breadcrumbs();

//		self::$title = translate('home');

		// Определяем шаблон по каторому будет отабражена страница модуля.
		$this->layout = $module->layout;
		// Получаем содеримое отображаемой страници
		$this->content = $module->$action($request);

		$this->load_module_assets($module->name);
	}

	/**
	 *	Возвращает ответ документ
	 */
	public function render()
	{
		$content = $this->content;

		if (is_string($content)) {
			// Иначе был отправлен шаблон данных.
			// Перехваываем и оборачиваем его в layout,
			// который установлен у модуля.
			$breadcrumbs = breadcrumbs()->generate();
			$title = self::$title;

			$advice = self::$advice;

			$_content = self::template($this->layout, compact(
				'content',
				'title',
				'breadcrumbs',
				'advice'
			));

			//response($_content, 'utf-8', 200);
			return $_content;
		}

		return '';
	}

	/**
	 * @function     : template
	 *
	 * @documentation:
	 *
	 * @param       $tmpl
	 * @param array $data
	 *
	 * @return string
	 */
	public static function template($tmpl, array $data = [])
	{
		$file = MCR_THEME_PATH . str_replace('.', '/', $tmpl) . '.phtml';

		if (!file_exists($file)) {
			$file = MCR_ROOT . 'themes/default/' . str_replace('.', '/', $tmpl) . '.phtml';

			if (!file_exists($file)) {
				throw new \InvalidArgumentException('Unknown template. Template: `' . $tmpl . '`.');
			}
		}

		ob_start();

		// Переводим пришедший масив в переменные
		// $$key = $value.
		// Если переменная имеет схожее имя с ранее объявленной - пропускаем её.
		extract($data, EXTR_SKIP);

		include $file;

		return ob_get_clean();
	}

	/**
	 * Загружает стили и скрипты для страници модуля.
	 * Дефолтный загрузчик для модуля, ищит файлы со специальным
	 * названием и загружает стили с него.
	 *
	 * @param $module
	 */
	private function load_module_assets($module)
	{
		$module = str_replace('\\', '.', $module);

		self::$stylesheets .= asset($module . '.header-styles', true);
		self::$scripts['body'] .= asset($module . '.header-body-scripts', true);
	}
}