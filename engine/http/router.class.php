<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 27.06.2018
 * @Time         : 20:59
 *
 * @Documentation:
 */

namespace mcr\http;


use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

include __DIR__ . '/../../bootstrap/config/routes.php';
include __DIR__ . '/../libs/fast-route/src/functions.php';

class router
{
	/**
	 * @var request|null
	 */
	private $request = null;

	public $route = [];

	public $controller = '';

	public $action = 'index';

	/**
	 * router constructor.
	 *
	 * @param request $request
	 *
	 *
	 * @documentation:
	 */
	public function __construct(request $request)
	{
		$this->request = $request;

		// Загружаем маршруты
		$dispatcher = simpleDispatcher('\router_builder\build');

		$httpMethod = $this->request->method();
		$uri = $this->request->uri();

		$uri = trim(explode('?', $uri)[1], '/');
		if (strlen($uri) < 1) $uri = '/';

		// Определяем маршрут
		$this->route = $dispatcher->dispatch($httpMethod, $uri);
	}

	/**
	 *
	 */
	public function dispatch()
	{
		switch ($this->route[0]) {
			case Dispatcher::NOT_FOUND:

				response('', 'utf8', 404, [], true);

				break;
			case Dispatcher::METHOD_NOT_ALLOWED:
				$allowedMethods = $this->route[1];

				response('', 'utf8', 405, [], true);

				break;
			case Dispatcher::FOUND:

				$handler = $this->route[1];
				$vars = $this->route[2];

				$this->request->merge($vars);

				list($controller, $action) = explode('@', $handler);

				$this->controller = $controller;
				$this->action = $action;

				break;
		}

		return $this;
	}

	/**
	 * Возвращает базовый адрес сайта.
	 *
	 * @param bool $short
	 *
	 * @return string
	 */
	public static function base_url($short = false)
	{
		if ($short) {
			$pos = strripos($_SERVER['PHP_SELF'], 'install/index.php');

			if ($pos === false) {
				$pos = strripos($_SERVER['PHP_SELF'], 'index.php');
			}

			$_base_url =  mb_substr($_SERVER['PHP_SELF'], 0, $pos, 'UTF-8');
		} else {
			$_base_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];

			if ($_SERVER['SERVER_PORT'] != 80) {
				$_base_url .= ':' . $_SERVER['SERVER_PORT'];
			}
		}

		return $_base_url . '/';
	}
}