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

namespace mcr;


use mcr\http\request;

use modules\base_module;
use modules\module;

class document
{
	/**
	 * @var string
	 */
	public $layout = '';

	public $title = '';

	/**
	 * @var string
	 */
	public $content = '';

	public $blocks = '';

	public $header = '';

	public $def_header = '';

	public $advise = '';

	public $menu = '';

	public $breadcrumbs = '';

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
	   $this->content = @$module->content($request);

	}
	
	public function render()
	{

		echo $this->content;
		/*$content = $this->content;
		$title = $this->title;
		$blocks = $this->blocks;
		$header = $this->header;
		$def_header = $this->def_header;
		$advise = $this->advise;
		$menu = $this->menu;
		$breadcrumbs = $this->breadcrumbs;
		$search = $this->search;

		echo self::template($this->layout, compact(
			'content',
			'title',
			'blocks',
			'header',
			'def_header',
			'advise',
			'menu',
			'breadcrumbs',
			'search'
		));*/
	}

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

		extract($data, EXTR_SKIP);

		include $file;

		return ob_get_clean();
	}
}