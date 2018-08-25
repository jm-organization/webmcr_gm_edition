<?php
namespace blocks;

use mcr\auth\auth;
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
	 * @param array $block_info - конфиги блока, которые необходимы для его работы.
	 *
	 * @return base_block
	 */
	public function init(array $block_info)
	{
		if (empty(auth::user()) || auth::user()->cannot($block_info['configs']['permissions_level'])) return null;

		return $this;
	}
}