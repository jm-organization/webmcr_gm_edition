<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 05.07.2018
 * @Time         : 21:41
 *
 * @Documentation:
 */

namespace mcr\html\blocks;


class blocks_manager
{
	private $configs = [];

	private $blocks = [];

	/**
	 * blocks_manager constructor.
	 *
	 * @throws blocks_manager_exception
	 */
	public function __construct()
	{
		// регистрируем конфиги блоков локально
		$this->configs = config('blocks');

		$blocks = array_keys($this->configs);
		$this->set_blocks($blocks);
	}

	/**
	 * Перезаписывает реестер блоков
	 *
	 * @param array $blocks
	 *
	 * @throws blocks_manager_exception
	 */
	public function set_blocks(array $blocks)
	{
		$this->blocks = [];

		foreach ($blocks as $block) {
			$this->add_block($block);
		}
	}

	/**
	 * @param $block
	 *
	 * @throws blocks_manager_exception
	 */
	public function add_block($block)
	{
		$configs = $this->configs;

		// если существует указаный блок, добавляем его.
		if (array_key_exists($block, $configs)) {
			$block_configs = $configs[$block];
			// создаём новый блок
			$block = new block($block, $block_configs);

			// добавляем блок в реестре менеджера блоков
			if (count($block->configs) > 0) $this->blocks[$block->name] = $block;
		}
	}

	public function render($blocks = 'all')
	{
		// Если пришёл масив блоков или вывести нужно все, то перебираем блоки и выводим их.
		if (is_array($blocks) || $blocks == 'all') {

			// Если выбраны все блоки, то берём блоки из реестра
			if ($blocks == 'all') $blocks = $this->blocks;

			// Сортируем блоки по их позиции -->
			foreach ($blocks as $block) {
				// берём позицию блока, если такая есть, то инкрементим её.
				$blocks_position = $block->configs['POSITION'];
				if (array_key_exists($blocks_position, $blocks)) $blocks_position++;

				// сохраняем в масиве блоков этот блок под новым ключём
				$blocks[$blocks_position] = $block;
				// удаляем старый ключ со значением
				unset($blocks[$block->name]);
			}

			// сортируем по ключу asc
			ksort($blocks);
			// <-- конец сортировки

			// рендерим каждый из блоков
			foreach ($blocks as $block) {
				if (array_key_exists($block->name, $this->blocks)) $this->render_block($this->blocks[$block->name]);
			}

		} elseif (is_string($blocks)) {
			// иначе выводим только тот блок, который был укаан
			if (array_key_exists($blocks, $this->blocks)) $this->render_block($this->blocks[$blocks]);
		}
	}

	private function render_block($block)
	{
		if ($block instanceof block) {
			// Если блок включен
			if ($block->configs['ENABLE']) {
				// Рендерим блок
				echo $block->view;
			}
		}
	}
}