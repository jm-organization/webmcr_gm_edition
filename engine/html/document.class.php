<?php
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


use mcr\http\request;

use modules\base_module;
use modules\module;

class document
{
	/**
	 * @var string
	 */
	public $layout = '';

	/**
	 * @var string
	 */
	public $title = '';

	/**
	 * @var string
	 */
	public $content = '';

	/**
	 * @var string
	 */
	public $blocks = '';

	/**
	 * @var string
	 */
	public $header = '';

	/**
	 * @var string
	 */
	public $def_header = '';

	/**
	 * @var string
	 */
	public $advise = '';

	/**
	 * @var menu|null
	 */
	public static $menu = null;

	/**
	 * @var string
	 */
	public $breadcrumbs = '';

	/**
	 * @var string
	 */
	public $search = '';

	public static $stylesheets = '';

	public static $scripts = [
		'body' => '',
		'head' => '',
	];

	/**
	 * document constructor.
	 *
	 * @param base_module|module $module
	 * @param request            $request
	 */
	public function __construct(base_module $module, request $request)
	{
		// Регистрируем меню
		self::$menu = new menu($request);

		// Определяем шаблон по каторому будет отабражена страница модуля.
		$this->layout = $module->layout;
		// Получаем содеримое отображаемой страници
		$this->content = $module->content($request);
	}

	/**
	 * @function     : render
	 *
	 * @documentation:
	 *
	 *
	 */
	public function render()
	{
		$content = $this->content;

		if (is_string($content)) {
			// Иначе был отправлен шаблон данных.
			// Перехваываем и оборачиваем его в layout,
			// который установлен у модуля.
			$title = $this->title;
			$blocks = $this->blocks;
			$header = $this->header;
			$def_header = $this->def_header;
			$advice = $this->advise;
			$breadcrumbs = $this->breadcrumbs;
			$search = $this->search;

			$_content = self::template($this->layout, compact(
				'content',
				'title',
				'blocks',
				'header',
				'def_header',
				'advice',
				'breadcrumbs',
				'search'
			));

			response($_content, 'utf-8', 200);
		}
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
}