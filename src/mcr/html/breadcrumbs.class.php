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
	public static $routes = [];

	/**
	 * breadcrumbs constructor.
	 *
	 * @param array $routes
	 * @param bool  $enabled
	 */
	public function __construct(array $routes = [], $enabled = true)
	{
		$this->enabled = $enabled;
		self::$routes += $routes;
	}

	/**
	 * Генератор хлебных крошек
	 *
	 * @return string
	 */
	public function generate()
	{
		$routes = self::$routes;
		if (empty($routes) && count($routes) < 1) return '';

		$crumbs = $this->generate_crumbs(self::$routes);

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

	/**
	 * @param $url
	 * @param $name
	 */
	public static function add($url, $name)
	{
		self::$routes[$name] = $url;
	}
}