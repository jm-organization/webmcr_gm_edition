<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 15.07.2018
 * @Time         : 21:42
 *
 * @Documentation:
 */

namespace engine\http\routing;


use FastRoute\RouteParser\Std as route_parser;
use mcr\http\routing\route_collector;

class url_builder
{
	public $route = '/';

	public $name = 'index';

	/**
	 * url_builder constructor.
	 *
	 * @param $name
	 *
	 * @throws url_builder_exception
	 */
	public function __construct($name)
	{
		$this->name = $name;

		$this->set_route($this->name);
	}

	/**
	 * Получает полный сырой маршрут, по имени.
	 * Если маршрут не найден в коллекции маршрутов,
	 * то выдаёт исключение.
	 *
	 * @param $route_name
	 *
	 * @throws url_builder_exception
	 */
	public function set_route($route_name)
	{
		$routes_collection = route_collector::$routes;

		if (array_key_exists($route_name, $routes_collection)) {
			$this->route = $routes_collection[$route_name];
		} else {
			throw new url_builder_exception('Unknown route name: `' . $route_name . '`.');
		}
	}

	/**
	 * Строит url аддрес относительно переменных,
	 * которые были предоставленны для маршрута, route
	 *
	 * Авторство функции принадлежит https://github.com/nikic/
	 * Адоптировано под MagicMCR by @Magicmen
	 *
	 * @param array $variables
	 *
	 * @return string
	 * @throws url_builder_exception
	 */
	public function build(array $variables = [])
	{
		$routeParser = new route_parser;
		// Извлекаем все возможные маршруты
		$routes = $routeParser->parse($this->route);

		foreach ($routes as $route) {
			$url = '';
			$paramIdx = 0;

			foreach ($route as $part) {
				// Фиксируем сегмент в маршруте
				if (is_string($part)) {
					$url .= $part;
					continue;
				}

				// добавляем параметр
				if ($paramIdx === count($variables)) {
					throw new url_builder_exception('Not enough parameters given');
				}
				$paramIdx++;
				$url .= $variables[$part[0]];
			}

			// Если число параметров в маршруте соответствует числу заданных параметров, используйте этот маршрут.
			// В противном случае попробуйте найти маршрут с большим количеством параметров
			if ($paramIdx === count($variables)) {
				return $url;
			} else {
				// Получаеременные лишьние переменные
				$params = array_slice($variables, $paramIdx);

				$params = $this->variables_to_query_params($params);

				// собираем url-строку.
				// url + query запрос + фрагмент
				return $url . $params[0] . $params[1];
			}
		}
	}

	/**
	 * Перебирает пришедший массив.
	 * Если эллемент имеет ассоциативный ключ,
	 * то добавляет его в query запрос
	 * иначе создаёт из него фрагмент.
	 * Фрагмент перезаписывается тем, который был крайним не ассоциативныйм эллементом.
	 *
	 * @param array $variables
	 *
	 * @return array
	 */
	private function variables_to_query_params(array $variables)
	{
		$query = '';
		$fragment = '';

		// перебираем их
		foreach ($variables as $variable => $value) {
			// если есть параметр без ассоциотивного ключа, то перебиваем фрагмент (#fragment)
			if (is_integer($variable)) {
				$fragment = '#' . $value;
				continue;
			}

			$query .= $variable . '=' . urlencode($value) . '&';
		}

		// собираем query строку
		$query = ($query != '') ? '?' . trim($query, '&') : '';

		return [ $query, $fragment ];
	}
}