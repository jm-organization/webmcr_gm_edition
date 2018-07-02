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
	 * @var string
	 */
	public $menu = '';

	/**
	 * @var string
	 */
	public $breadcrumbs = '';

	/**
	 * @var string
	 */
	public $search = '';

	/**
	 * document constructor.
	 *
	 * @param base_module|module $module
	 * @param request            $request
	 */
	public function __construct(base_module $module, request $request)
	{
		$this->layout = $module->layout;

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

		// Если пришёл ответ от сервера, то отправляем заголовки и содержимое ответа.
		if ($content instanceof \mcr\http\response) self::response($content);
		// Если было отправленно перенаправление, то перенаправляем
		if ($content instanceof \mcr\http\redirect) self::redirect($content);

		// Иначе был отправлен шаблон данных.
		// Перехваываем и оборачиваем его в layout,
		// который установлен у модуля.
		$title = $this->title;
		$blocks = $this->blocks;
		$header = $this->header;
		$def_header = $this->def_header;
		$advise = $this->advise;
		$menu = $this->menu;
		$breadcrumbs = $this->breadcrumbs;
		$search = $this->search;

		$_content = self::template($this->layout, compact(
			'content',
			'title',
			'blocks',
			'header',
			'def_header',
			'advise',
			'menu',
			'breadcrumbs',
			'search'
		));

		response($_content, 'utf-8', 200)->send();
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

		extract($data, EXTR_OVERWRITE);

		include $file;

		return ob_get_clean();
	}

	/**
	 * @function     : response
	 *
	 * @documentation:
	 *
	 * @param \mcr\http\response $content
	 *
	 */
	private static function response(\mcr\http\response $content)
	{
		$content->send();
	}

	/**
	 * @function     : redirect
	 *
	 * @documentation:
	 *
	 * @param \mcr\http\redirect $content
	 *
	 */
	private static function redirect(\mcr\http\redirect $content)
	{
		header($content->header());
	}
}