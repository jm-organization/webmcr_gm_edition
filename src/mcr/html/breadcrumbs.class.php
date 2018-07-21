<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 21.07.2018
 * @Time         : 0:42
 *
 * @Documentation:
 */

namespace mcr\html;


class breadcrumbs
{
	/**
	 * @var bool
	 */
	private $enabled = true;

	/**
	 * @var string
	 */
	public $view = '';

	/**
	 * @var array
	 */
	public $routes = [];

	public function __construct($routes, $enabled = true)
	{
		$this->enabled = $enabled;
		$this->routes = $routes;
	}

	/**
	 * Генератор хлебных крошек
	 *
	 * @return string
	 */
	public function generate()
	{
		$routes = $this->routes;
		if (empty($routes) && count($routes) < 1) throw new \UnexpectedValueException('Can`t generate breadcrumbs from empty routes');

		$crumbs = $this->generate_crumbs($this->routes);

		if ($this->enabled) $this->view = tmpl('breadcrumbs.list', [ 'crumbs' => $crumbs ]);

		return $this->view;
	}

	/**
	 * Генератор списка хлебных крошек
	 *
	 * @param array $routes - массив элементов списка
	 *
	 * @return string
	 */
	private function generate_crumbs(array $routes)
	{
		$count = count($routes) - 1;
		$document_title = $crumbs = '';

		$i = 0;
		foreach ($routes as $title => $url) {
			$document_title .= ($i == 0) ? $title : ' — ' . $title;

			if ($count == $i) {
				$crumbs .= tmpl('breadcrumbs.id-active', [ 'title' => $title ]);
			} else {
				$crumbs .= tmpl('breadcrumbs.id-inactive', compact('title', 'url'));
			}

			$i++;
		}

		document::$title = htmlspecialchars($document_title);

		return $crumbs;
	}
}