<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 27.06.2018
 * @Time         : 22:26
 *
 * @Documentation:
 */

namespace mcr\http;


class request
{
	/**
	 * @var array
	 */
	private $attributes = [];

	/**
	 * @var string
	 */
	public static $host = '';

	/**
	 * @var string
	 */
	public static $path = '';

	/*
	 * Constructor for request
	 */
	public function __construct()
	{
		$this->set_attributes();

		self::set_host();
		self::set_path();
	}

	private function set_attributes()
	{
		$this->attributes = array_merge($this->attributes, $_POST, $_GET);

		// Очишаем пришедшие данные от пробелов в начале и конце.
		$this->attributes = array_map(function($attr) {
			return trim($attr);
		}, $this->attributes);

		if (is_filled($_FILES)) {
			$this->attributes['files'] = $_FILES;
		}
	}

	private static function set_host()
	{
		self::$host = $_SERVER['SERVER_NAME'];
	}

	private static function set_path()
	{
		$uri = self::uri();

		if (false !== $pos = strpos($uri, '?')) {
			$uri = substr($uri, 0, $pos);
		}

		$path = rawurldecode($uri);

		self::$path = trim($path, '/');
	}

	/**
	 * Помещает данные в запрос.
	 * Данные отдаёт роутер.
	 *
	 * @param array $attributes
	 *
	 * @return request
	 */
	public function put(array $attributes)
	{
		$this->attributes = array_merge($this->attributes, $attributes);

		return $this;
	}

	/**
	 * Возвращает все данные, которые пришли с запросом
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->attributes;
	}

	/**
	 * Возвращает данные, о методе запроса
	 *
	 * @return mixed
	 */
	public static function method()
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * @return string
	 */
	public static function url()
	{
		$_base_url = router::base_url();
		$_request_uri = $_SERVER['REQUEST_URI'];

		return $_base_url . $_request_uri;
	}

	/**
	 * @return mixed
	 */
	public static function uri()
	{
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function __isset($key)
	{
		return !is_null($this->__get($key));
	}

	/**
	 * Возвращает значение параметра $key в запросе.
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function __get($key)
	{
		if (is_null($key)) {
			return null;
		}

		if (array_key_exists($key, $this->all())) {
			return $this->all()[$key];
		}

		return null;
	}
}