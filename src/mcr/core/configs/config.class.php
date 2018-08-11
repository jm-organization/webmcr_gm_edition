<?php
/**
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

namespace mcr\core\configs;


use mcr\core\registry\component;

if (!defined("MCR")) exit("Hacking Attempt!");

class config implements component
{
	const path = MCR_CONF_PATH;

	/**
	 * Набор конфигов mcr-приложения
	 *
	 * @var \stdClass
	 */
	private $configs;

	/**
	 * Файлы, которые необходимо учитывать
	 * при инициализации конфигов
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
	 * Список поставщиков конфигов.
	 *
	 * @var array
	 */
	private $providers = [];

	/**
	 * Мотод должен возвращать строковое
	 * абстрактное имя комопнента.
	 *
	 * @return string
	 */
	public function get_abstract_name()
	{
		return 'configs';
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

	private function __wakeup() { }

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
	 * Возвращает значение указанного ключа конфига.
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
	 * Приводит масив всех конфигов
	 * к одномерному и сериализирует значения конфигов.
	 *
	 * Возвращает результат компресии.
	 *
	 * @param array $configs
	 *
	 * @return array
	 */
	public function compress(array $configs = [])
	{
		$compressed = [];
		if (count($configs) <= 0) {
			$configs = $this->configs;
		}

		foreach ($configs as $config_group => $group) {
			foreach ($group as $config => $value) {

				$compressed["$config_group::$config"] = serialize($value);

			}
		}

		return $compressed;
	}

	/**
	 * Обратный методу compress.
	 * Также десериализирует значения конфигов
	 *
	 * Возвращает декомпресированый
	 * массив конфигов.
	 *
	 * @param array $configs
	 *
	 * @return array
	 */
	public function decompress(array $configs)
	{
		$decompressed = [];

		foreach ($configs as $config_group_and_config => $value) {
			list($config_group, $config) = explode('::', $config_group_and_config);

			$decompressed[$config_group][$config] = unserialize($value);
		}

		return $decompressed;
	}

	/**
	 * Привязывает поставщик конфигов к configs.
	 * Вызывает метод инициализации провайдера чтобы
	 * выполнить добавление конфигов,
	 * поставляемых поставщиком в
	 * общий список конигов
	 *
	 * @param $provider
	 *
	 * @return $this
	 */
	public function bind($provider)
	{
		$providers = func_get_args();

		foreach ($providers as $_provider) {
			$provider = new $_provider();

			if ($provider instanceof provider) {
				$this->add_provider($provider);
			} else {
				throw new \UnexpectedValueException("You can`t register '$_provider' in config providers. Your provider don`t implement of \mcr\core\configs\provider");
			}
		}

		return $this;
	}

	/**
	 * @param provider $provider
	 */
	private function add_provider(provider $provider)
	{
		$provider_abstract_name = $provider->get_abstract_name();

		$provider->boot($this);

		$this->providers[$provider_abstract_name] = $provider;
	}
}