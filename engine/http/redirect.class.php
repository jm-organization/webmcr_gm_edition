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
	 * @param array $options
	 *
	 * @return $this
	 */
	public function with(array $options)
	{
		$_messages = [];

		if (ie($options['messages']) && is_array($options['messages'])) {
			foreach ($options['messages'] as $message) {
				$title = $message['title'];
				$text = $message['text'];
				$type = $message['type'];

				if (array_key_exists($type, self::$messages_types)) {
					$type = self::$messages_types[$type];
				} else {
					$type = 'default';
				}

				$_messages[] = [
					'message_type' => $type,
					'message_title' => $title,
					'message_content' => $text,
				];
			}

			unset($options['messages']);
		}

		$_SESSION['messages'] = $_messages;

		// other

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
		if (empty(trim($this->route))) {
			if (empty(trim($to))) {
				throw new \UnexpectedValueException('The route can`t be empty');
			}

			$this->route = str_replace($this->server_name, '', $to);
		}

		if ($_SERVER['REQUEST_URI'] != $this->route) {
			header('Location: ' . $this->route);
		}

		return $this;
	}
}