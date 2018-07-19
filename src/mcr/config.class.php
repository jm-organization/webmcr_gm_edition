<?php

namespace mcr;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class config
{
	/**
	 * @var array
	 */
	public $blocks = [];

	/**
	 * @var array
	 */
	public $modules = [];

	/**
	 * @var array|null
	 */
	public $db = null;

	/**
	 * @var array|null
	 */
	public $functions = null;

	/**
	 * @var array|null
	 */
	public $mail = null;

	/**
	 * @var array|null
	 */
	public $main = null;

	/**
	 * @var array|null
	 */
	public $pagin = null;

	/**
	 * @var array|null
	 */
	public $search = null;

	/**
	 * config constructor.
	 */
	public function __construct()
	{
		// Подгружаем основные конфиги
		$this->set_configs();

		// подгружаем конфиги блоков
		$this->set_configs(MCR_CONF_PATH . 'blocks/', 'blocks');

		// подгружаем конфиги модулей
		$this->set_configs(MCR_CONF_PATH . 'modules/', 'modules');
	}

	/**
	 * @param        $configs
	 * @param string $file
	 *
	 * @return bool
	 */
	public static function save($configs, $file = 'main.php')
	{
		if (!is_array($configs) || empty($configs)) return false;

		$filename = MCR_CONF_PATH . $file;

		return file_put_contents($filename, "<?php\n\nreturn " . var_export($configs, true) . ";");
	}

	private function set_configs($dir = MCR_CONF_PATH, $container = null)
	{
		$configs = scandir($dir);

		foreach ($configs as $config_file) {
			if ($config_file == '.' || $config_file == '..' || is_dir(MCR_CONF_PATH . $config_file)) continue;

			$config_root_namespace = pathinfo(MCR_CONF_PATH . $config_file, PATHINFO_FILENAME);

			$configs = require_once $dir . $config_file;

			if (empty($container)) {
				if (property_exists($this, $config_root_namespace)) {
					$this->$config_root_namespace = $configs;
				}
			} else {
				$this->$container = array_merge($this->$container, [ $config_root_namespace => $configs ]);
			}
		}
	}

	public function all()
	{
		return get_object_vars($this);
	}
}