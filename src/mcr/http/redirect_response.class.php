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

class redirect_response extends response
{
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

	public $properties = [];

	public function __construct($status = 301, array $headers = [])
	{
		parent::__construct('', 'UTF-8', $status, $headers);

		if (!$this->is_redirection()) {
			throw new \InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $status));
		}
	}

	/**
	 * Перенаправляет на маршрут с параметрами, которые указаны
	 *
	 * @uses redirect_response::set_message
	 * @uses redirect_response::set_property
	 *
	 * @param       $key
	 * @param array $options
	 *
	 * @return $this
	 */
	public function with($key, array $options)
	{
		$method = "set_$key";

		if (method_exists($this, $method)) {
			$this->$method($options);
		} else {
			throw new \UnexpectedValueException("redirect_response::with(): Unknown redirection property '$key'.");
		}

		return $this;
	}

	/**
	 * Добавляет сообщение при перенаправлении.
	 *
	 * @param array $message_data
	 */
	private function set_message(array $message_data)
	{
		$message_data['type'] = 'default';

		// Проверяем тип сообщения и проверяем наличие типа в списке доступных типов
		if (isset($message_data['type'])) {
			if (array_key_exists($message_data['type'], self::$messages_types)) {

				// если тип найден, то сохраняем сообщение
				// с указаным типом из списка доступных типов.
				$message_data['type'] = self::$messages_types[$message_data['type']];

			}
		}

		$message_data['text'] = isset($message_data['text']) ? htmlspecialchars($message_data['text']) : '';
		$message_data['title'] = isset($message_data['title']) ? htmlspecialchars($message_data['title']) : '';

		// Сохраняем сообщение
		array_push($this->messages, $message_data);
	}

	private function set_property(array $property_data)
	{
		list($key, $value) = $property_data;

		if (empty(trim($key))) throw new \UnexpectedValueException("redirect_response::with(): Your property name can`t be empty.");

		$this->properties[$key] = $value;
	}

	private function collect_properties()
	{
		$properties = request::all();

		foreach ($properties as $property => $value) {
			$this->with('property', [ $property, $value ]);
		}

		return $this->properties;
	}

	/**
	 * Перенаправляет на маршрут $to
	 *
	 * @param       $to
	 *
	 * @param array $route_variables
	 */
	public function route($to, array $route_variables = [])
	{
		if (empty($to)) throw new \UnexpectedValueException('The route can`t be empty');

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

		if (!$this->is_redirection()) {
			throw new \InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $this->status_code));
		}

		$_SESSION['properties'] = $this->collect_properties();
		$_SESSION['messages']   = $this->messages;

		$this->set_target_url($url);
	}

	/**
	 * @param $url
	 */
	private function set_target_url($url)
	{
		$this->header('Location', $url);

		$this->content(
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
</html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8')));
	}
}