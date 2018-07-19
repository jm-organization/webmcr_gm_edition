<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 15.07.2018
 * @Time         : 12:50
 *
 * @Documentation:
 */

namespace mcr\http\routing;


use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;

class route_collector extends RouteCollector
{
	public static $routes = [];

	/**
	 * Constructs a route collector.
	 *
	 * @param RouteParser   $routeParser
	 * @param DataGenerator $dataGenerator
	 */
	public function __construct(RouteParser $routeParser, DataGenerator $dataGenerator)
	{
		parent::__construct($routeParser, $dataGenerator);
	}

	private function add_route($route, $name)
	{
		$route = $this->currentGroupPrefix . $route;

		if (!in_array($route, self::$routes)) {

			// Удаляем регулярные выражаения для переменных запроса.
			$route = preg_replace('/:.*?}/i', '}', $route);

			// если указано имя, помещаем маршрут
			// в масив маршрутов с указанным именем
			if (!empty($name)) {
				self::$routes[$name] = $route;
			} else {
				self::$routes[] = $route;
			}

		}
	}

	public function addRoute($httpMethod, $route, $handler, $name = null)
	{
		parent::addRoute($httpMethod, $route, $handler);

		$this->add_route($route, $name);
	}

	/**
	 * Добавляет GET маршрут с именем name.
	 *
	 * @param string $route
	 * @param mixed  $handler
	 * @param string|null   $name 		Имя маршрута, может быть пустым, если маршрут называть нет необходимости
	 */
	public function get($route, $handler, $name = null)
	{
		$this->add_route($route, $name);

		parent::get($route, $handler);
	}

	/**
	 * Добавляет POST маршрут с именем name.
	 *
	 * @param string $route
	 * @param mixed  $handler
	 * @param string|null   $name 		Имя маршрута, может быть пустым, если маршрут называть нет необходимости
	 */
	public function post($route, $handler, $name = null)
	{
		$this->add_route($route, $name);

		parent::post($route, $handler);
	}

	/**
	 * Добавляет PUT маршрут с именем name.
	 *
	 * @param string $route
	 * @param mixed  $handler
	 * @param string|null   $name 		Имя маршрута, может быть пустым, если маршрут называть нет необходимости
	 */
	public function put($route, $handler, $name = null)
	{
		$this->add_route($route, $name);

		parent::put($route, $handler);
	}

	/**
	 * Добавляет DELETE маршрут с именем name.
	 *
	 * @param string $route
	 * @param mixed  $handler
	 * @param string|null   $name 		Имя маршрута, может быть пустым, если маршрут называть нет необходимости
	 */
	public function delete($route, $handler, $name = null)
	{
		$this->add_route($route, $name);

		parent::delete($route, $handler);
	}

	/**
	 * Добавляет PATCH маршрут с именем name.
	 *
	 * @param string 		$route
	 * @param mixed  		$handler
	 * @param string|null   $name 		Имя маршрута, может быть пустым, если маршрут называть нет необходимости
	 */
	public function patch($route, $handler, $name = null)
	{
		$this->add_route($route, $name);

		parent::patch($route, $handler);
	}

	/**
	 * Добавляет HEAD маршрут с именем name.
	 *
	 * @param string $route
	 * @param mixed  $handler
	 * @param string|null   $name 		Имя маршрута, может быть пустым, если маршрут называть нет необходимости
	 */
	public function head($route, $handler, $name = null)
	{
		$this->add_route($route, $name);

		parent::head($route, $handler);
	}
}