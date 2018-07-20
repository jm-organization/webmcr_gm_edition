<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 01.07.2018
 * @Time         : 21:34
 *
 * @Documentation:
 */

namespace mcr\http;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class redirect
{
	/**
	 * @var string
	 */
	private $server_name;

	/**
	 * @var array
	 */
	public static $messages_types = [
		1 => 'warning',
		2 => 'error',
		3 => 'success',
		4 => 'info'
	];

	public $messages = [];

	/**
	 * redirect constructor.
	 *
	 * @param string $route
	 *
	 * @throws \mcr\http\routing\url_builder_exception
	 */
	public function __construct($route = '', array $route_variables = [])
	{
		$this->server_name = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];
		if ($_SERVER['SERVER_PORT'] != 80) {
			$this->server_name .= ':' . $_SERVER['SERVER_PORT'];
		}

		if (!empty(trim($route))) {
			$this->route($route, $route_variables);
		}
	}

	/**
	 * @function     : with
	 *
	 * @documentation: Перенаправляет на маршрут с параметрами, которые указаны
	 *
	 * @param       $key
	 * @param array $options
	 *
	 * @return $this
	 */
	public function with($key, array $options)
	{
		if ($key == 'message') {
			if (isset($options['type'])) {
				if (array_key_exists($options['type'], self::$messages_types)) {
					$options['type'] = self::$messages_types[@$options['type'] ];
				} else {
					$options['type'] = 'default';
				}
			} else {
				$options['type'] = 'default';
			}

			$options['text'] = isset($options['text']) ? htmlspecialchars($options['text']) : '';
			$options['title'] = isset($options['title']) ? htmlspecialchars($options['title']) : '';

			array_push($this->messages, $options);
		}

		return $this;
	}

	/**
	 * Перенаправляет на маршрут $to
	 *
	 * @param       $to
	 *
	 * @param array $route_variables
	 *
	 * @throws \mcr\http\routing\url_builder_exception
	 */
	public function route($to, array $route_variables = [])
	{
		if (empty(trim($to))) throw new \UnexpectedValueException('The route can`t be empty');

		$this->url(
			url($to, $route_variables)
		);

	}

	/**
	 * Перенаправляет по урлу.
	 *
	 * @param $url
	 */
	public function url($url)
	{
		if (empty(trim($url))) throw new \UnexpectedValueException('The url can`t be empty');

		$_SESSION['messages'] = $this->messages;

		$this->set_target_url($url);
	}

	/**
	 * @param $url
	 */
	private function set_target_url($url)
	{
		response()->status(301)->header('Location', $url)->content(
			sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="0;url=%1$s" />
        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.
    </body>
</html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'))
		);
	}
}