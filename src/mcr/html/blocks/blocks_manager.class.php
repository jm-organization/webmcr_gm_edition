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


/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 05.07.2018
 * @Time         : 21:41
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
		$this->configs = config('components::blocks');

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
		$_blocks = $this->blocks;

		// Если пришёл масив блоков или вывести нужно все, то перебираем блоки и выводим их.
		if (is_array($blocks) || $blocks == 'all') {

			// Если выбраны не все блоки, то выводим только указанные.
			if ($blocks != 'all')  {
				$executed_blocks = array_diff(array_keys($this->blocks), $blocks);

				foreach ($executed_blocks as $block) {
					if (array_key_exists($block, $this->blocks)) {
						unset($_blocks[$block]);
					}
				}
			}

			foreach ($_blocks as $block) {
				// берём позицию блока, если такая есть, то инкрементим её.
				$blocks_position = $block->configs['configs']['order'];
				if (array_key_exists($blocks_position, $_blocks)) $blocks_position++;

				// сохраняем в масиве блоков этот блок под новым ключём
				$_blocks[$blocks_position] = $block;
				// удаляем старый ключ со значением
				unset($_blocks[$block->name]);
			}

			// сортируем по ключу asc
			ksort($_blocks);

			// рендерим каждый из блоков
			foreach ($_blocks as $block) {
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
			if (in_array($block->name, config('components::enabled_blocks'))) {
				// Рендерим блок
				echo $block->view;
			}
		}
	}
}