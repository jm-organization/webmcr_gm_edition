<?php
namespace blocks;

use mcr\html\blocks\base_block;
use mcr\html\blocks\standard_block;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class block_online implements base_block
{
	use standard_block;

	/**
	 * base_block constructor.
	 */
	public function __construct() { }

	/**
	 * Инициализатор блока.
	 * Принимает конфиги блока.
	 *
	 * @param array $configs - конфиги блока, которые необходимы для его работы.
	 *
	 * @return base_block
	 */
	public function init(array $configs)
	{
		// TODO: Create is access in USER class
		/*if (!$this->core->is_access(@$this->core->cfg_b['PERMISSIONS'])) {
			return null;
		}*/

		return $this;
	}
}