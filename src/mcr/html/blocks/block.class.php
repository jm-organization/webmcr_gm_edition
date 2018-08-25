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
	/**
	 * Кодовое имя блока
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Конфиги блока
	 *
	 * @var array
	 */
	public $configs = [];

	/**
	 * Представление блока
	 *
	 * @var string
	 */
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
		// если какого-то параметра в конфиге блока нет или он выключен, то конфиг не устанавливаем.
		if ($this->check_configs($configs['configs']) && $this->is_enabled()) {
			$this->configs = $configs;
		}

		return $this;
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 * @throws blocks_manager_exception
	 */
	public function load_block($name)
	{
		if (empty($this->configs)) return false;

		$block_class = '\blocks\\' . $name;

		if (class_exists($block_class)) {
			$block = new $block_class();

			// Если блок реализует базовый блок,
			// то инициализируем его
			if ($block instanceof base_block) {
				return $this->initialize_block($block, $name);
			}

			throw new blocks_manager_exception('Block ' . $block_class . '  not implement base_block.');
		}
	}

	/**
	 * @param base_block $block
	 */
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

	/**
	 * Выполняет проверку валидности конфигов блока.
	 *
	 * @param $configs
	 *
	 * @return bool
	 */
	private function check_configs($configs)
	{
		if ( (!isset($configs['order']) || empty($configs['order'])) && $configs['order'] < 1 ) return false;

		if ( !array_key_exists('permissions_level', $configs) ) return false;

		return true;
	}

	/**
	 * Проверяет активацию блока (вкл/выкл).
	 *
	 * @return bool
	 */
	public function is_enabled()
	{
		return in_array($this->name, config('components::enabled_blocks'));
	}

	/**
	 * @param base_block $block
	 * @param            $name
	 *
	 * @return bool
	 */
	private function initialize_block(base_block $block, $name)
	{
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

			return true;
		}

		return false;
	}
}