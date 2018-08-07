<?php

namespace mcr;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class config
{
	const path = MCR_CONF_PATH;

	/**
	 * Набор конфигов mcr-приложения
	 *
	 * @var \stdClass
	 */
	private $configs;

	/**
	 * Файлы, которые необходимо учитывать при инициализации конфигов
	 *
	 * @var array
	 */
	private $conf_files = [
		'mcr.ini',
		'db.ini'
	];

	/**
	 * @var null
	 */
	private static $instance = null;

	/**
	 * @return config|null
	 */
	public static function get_instance()
	{
		if (empty(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * config constructor.
	 */
	private function __construct()
	{
		$this->configs = new \stdClass();

		$this->initialize();
	}

	/**
	 *
	 */
	private function __wakeup() { }

	/**
	 *
	 */
	private function __clone() { }

	/**
	 * @param $key
	 * @param $value
	 */
	public function attach($key, $value)
	{
		if (property_exists($this->configs, $key)) {
			$this->configs->$key = array_merge($this->configs->$key, $value);
		} else {
			$this->configs->$key = $value;
		}
	}

	/**
	 * Добавляет файлы, которые
	 * находятся в диреткории MCR_CONF_PATH в реестр инициализации
	 *
	 * @param $filename
	 *
	 * @return $this
	 */
	public function to_initialize($filename)
	{
		array_push($this->conf_files, $filename);

		return $this;
	}

	/**
	 * @return void
	 */
	private function initialize()
	{
		foreach ($this->conf_files as $conf_file) {
			$this->parse(self::path . $conf_file);
		}
	}

	/**
	 * Извлекает конфигги из конфигурационных файлов
	 *
	 * @param $file
	 *
	 * @return bool
	 */
	private function parse($file)
	{
		if (file_exists($file) && $this->config_is_valid($file)) {
			$config_name =  pathinfo($file, PATHINFO_FILENAME);

			$this->configs->$config_name = parse_ini_file($file, true);

			return true;
		}

		return false;
	}

	/**
	 * Проверряет указаный для инициализации файл.
	 * Если валидный - возвращает true
	 *
	 * @param $file
	 *
	 * @return bool
	 */
	private function config_is_valid($file)
	{
		$extension = pathinfo($file, PATHINFO_EXTENSION);

		if ($extension != 'ini') return false;

		return true;
	}

	/**
	 * @param array $data
	 * @param       $name
	 *
	 * @return void
	 */
	public static function save(array $data, $name)
	{
		$config ="; MagicMCR | " . date('d/m/Y в H:i:s') . "\n\n";

		foreach ($data as $key => $value) {
			$config .= "$key = $value\n";
		}

		file_put_contents(self::path . $name . '.ini', $config);
	}

	/**
	 * Возвращает набор всех конфигов
	 *
	 * @return \stdClass
	 */
	public function all()
	{
		return $this->configs;
	}

	/**
	 * Возвращает значение указанного ключа конфига
	 *
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function get($key)
	{
		$key = explode('::', $key);
		$config = $this->get_config_property($key[0]);

		if (count($key) == 2 && !empty($config)) {
			if (array_key_exists($key[1], $config)) return $config[$key[1]];
		} else {
			return $config;
		}

		return null;
	}

	/**
	 * возвращает конфиг, из которого
	 * необходимо получить значение по ключу
	 *
	 * @param $property
	 *
	 * @return null
	 */
	private function get_config_property($property)
	{
		if (!property_exists($this->configs, $property)) return null;

		return $this->configs->$property;
	}

	/**
	 * @param array $config
	 * @param       $key
	 *
	 * @deprecated
	 *
	 * @return array|mixed
	 */
	private function extract_value(array $config, $key)
	{
		$config_keys = explode('.', $key);

		foreach ($config_keys as $key) {
			if (array_key_exists($key, $config)) {
				$config = $config[$key];
			}
		}

		return $config;
	}
}