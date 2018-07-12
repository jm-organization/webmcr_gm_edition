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
	 * @var mixed
	 */
	private $route;

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
	 */
	public function __construct($route = '')
	{
		$this->server_name = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];
		if ($_SERVER['SERVER_PORT'] != 80) {
			$this->server_name .= ':' . $_SERVER['SERVER_PORT'];
		}

		$this->route = str_replace($this->server_name, '', $route);

		if (!empty(trim($route))) {
			$this->route($this->route);
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
	 * @param $to
	 *
	 * @return redirect
	 */
	public function route($to)
	{
		$_SESSION['messages'] = $this->messages;

		if (empty(trim($this->route))) {
			if (empty(trim($to))) {
				throw new \UnexpectedValueException('The route can`t be empty');
			}

			$this->route = str_replace($this->server_name, '', $to);
		}

//		if ($_SERVER['REQUEST_URI'] != $this->route) {
			$location = url($this->route);
			header('Location: ' . $location);
//		}


		return $this;
	}
}