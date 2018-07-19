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

namespace mcr\http\routing;


use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;
use mcr\http\request;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

include __DIR__ . '/../../../libs/fast-route/src/functions.php';

class router
{
	/**
	 * @var request|null
	 */
	private $request = null;

	/**
	 * @var array
	 */
	public $route = [];

	/**
	 * @var string
	 */
	public $controller = '';

	/**
	 * @var string
	 */
	public $action = 'index';

	/**
	 * router constructor.
	 *
	 * @param request $request
	 */
	public function __construct(request $request)
	{
		$this->request = $request;

		// Загружаем маршруты
		$dispatcher = simpleDispatcher(['\mcr\http\routing\route_builder', 'build'], [
			'routeCollector' => route_collector::class
		]);

		$httpMethod = $request::method();
		$uri = $request::$path;

		// Определяем маршрут
		$this->route = $dispatcher->dispatch($httpMethod, $uri);
	}

	/**
	 * Сообщает компилатору информацию о маршруте.
	 *
	 * Возвращает данные в виде масива.
	 * Первый эллемент масива - статус обработки маршрута.
	 * Второй - информация о маршруте
	 * Третий - дополнительная информация.
	 *
	 * @return array
	 */
	public function dispatch()
	{
		switch ($this->route[0]) {
			case Dispatcher::NOT_FOUND: 			return [ 404, [], [] ]; break;
			case Dispatcher::METHOD_NOT_ALLOWED:	return [ 405, [], $this->route[1] ]; break;
			case Dispatcher::FOUND:

				list($dispatch_status, $handler, $vars) = $this->route;
				// Помещаем извлечённые переменные из роута в реквест
				$this->request->put($vars);

				// Определяем контролер и экшин из обработчика маршрута
				$controller = $action = null;
				if (is_string($handler)) {
					list($controller, $action) = explode('@', $handler);

					$this->controller = $controller;
					$this->action = $action;
				}

				return [ 200, [ 'controller' => $controller, 'action' => $action ], $this ];

			break;
			default: return [ 404, [], [] ]; break;
		}
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