<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 05.07.2018
 * @Time         : 22:58
 *
 * @Documentation:
 */

namespace mcr\html\blocks;


use mcr\html\document;

class block
{
	public $name = '';

	public $configs = [];

	public $view = '';

	/**
	 * block constructor.
	 *
	 * @param $block_name
	 * @param $configs
	 *
	 * @throws blocks_manager_exception
	 */
	public function __construct($block_name, $configs)
	{
		// Устанавливаем имя блока
		$this->name = $block_name;

		// Устанавливаем его конфиги и загружаем блок
		$this->set_configs($configs)->load_block($block_name);
	}

	/**
	 * @param array $configs
	 *
	 * @return block
	 */
	public function set_configs(array $configs)
	{
		$format = [ 'ENABLE', 'POSITION', 'TITLE', 'DESC', 'AUTHOR', 'SITE', 'EMAIL', 'VERSION', 'UPDATES', 'UPDATER' ];

		// Вычисляем расхождение с форматом конфига блоков
		$diff = array_diff($format, array_keys($configs));

		// если какого-то параметра в конфиге блока нет или он выключен, то конфиг не устанавливаем.
		if (empty($diff) && $configs['ENABLE']) {
			$this->configs = $configs;
		}

		return $this;
	}

	/**
	 * @param $name
	 *
	 * @throws blocks_manager_exception
	 */
	public function load_block($name)
	{
		if (!empty($this->configs)) {

			$block_class = '\blocks\\' . $name;

			if (class_exists($block_class)) {
				$block = new $block_class();

				// Если блок реализует базовый блок,
				// то инициализируем его
				if ($block instanceof base_block) {

					$block = $block->init($this->configs);

					if (is_object($block)) {
						// данные и шаблон их вывода
						$data = [];
						$tmpl = '';

						// Берём данные сгенерированые блоком, если они указаны в блоке
						if (property_exists($block, 'data')) $data = $block->data;
						// Берём короткий путь к шаблону, если он указан
						if (property_exists($block, 'tmpl')) $tmpl = $block->tmpl;

						$this->load_block_assets($block);

						if (!empty(trim($tmpl))) {
							$this->view = tmpl("blocks.$name.$tmpl", $data);
						}
					}

				} else {
					// выбрасываем исключение
					throw new blocks_manager_exception('Block ' . $block_class . '  not implement base_block.');
				}
			}

		}
	}

	private function load_block_assets(base_block $block)
	{
		// Берём короткие пути к стилям и скриптам, если они указаны
		if (property_exists($block, 'styles')) {
			document::$stylesheets .= asset("blocks.{$this->name}.{$block->styles}", true);
		}

		if (property_exists($block, 'head_scripts')) {
			document::$scripts['body'] .= asset("blocks.{$this->name}.{$block->head_scripts}", true);
		}

		if (property_exists($block, 'body_scripts')) {
			document::$scripts['head'] .= asset("blocks.{$this->name}.{$block->body_scripts}", true);
		}
	}
}