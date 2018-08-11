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


use mcr\core\registry\component;
use mcr\http\routing\router;

class request implements component
{
	/**
	 * @var array
	 */
	private static $attributes = [];

	/**
	 * @var string
	 */
	public static $host = '';

	/**
	 * @var string
	 */
	public static $path = '';

	/**
	 * Мотод должен возвращать строковое
	 * абстрактное имя комопнента.
	 *
	 * @return string
	 */
	public function get_abstract_name()
	{
		return 'request';
	}

	/**
	 * Вызывается, когда происходит
	 * инициализация - добовление компонента
	 * в реестр.
	 *
	 * Должен возвращать экземпляр класса component
	 *
	 * @return component
	 */
	public function boot()
	{
		return $this;
	}

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
		self::$attributes = array_merge(self::$attributes, $_POST, $_GET);

		// Очишаем пришедшие данные от пробелов в начале и конце.
		self::$attributes = array_map(function($attr) {
			return trim($attr);
		}, self::$attributes);

		if (!empty($_FILES)) {
			self::$attributes['files'] = $_FILES;
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

		$path = preg_replace('/\/index\.[a-z]*/', '/', $path);

		self::$path = $path == '/' ? $path : trim($path, '/');
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
		self::$attributes = array_merge(self::$attributes, $attributes);

		return $this;
	}

	/**
	 * Возвращает все данные, которые пришли с запросом
	 *
	 * @return array
	 */
	public static function all()
	{
		return self::$attributes;
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
	 * @param      $property
	 * @param null $default
	 *
	 * @return null
	 */
	public function old($property, $default = null)
	{
		// Если есть масив параметров, которые были запомнены
		if (isset($_SESSION['properties'])) {
			// и в них есть требуемый параметр
			if (isset($_SESSION['properties'][$property])) {

				// Запоминаем его локально чтобы удалить из общего реестра
				$value = $_SESSION['properties'][$property];
				// удаляем
				unset($_SESSION['properties'][$property]);

				// Возвращаем его
				return $value;

			}
		}

		return $default;
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
			return self::all()[$key];
		}

		return null;
	}
}