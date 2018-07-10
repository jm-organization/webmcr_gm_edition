<?php
namespace blocks;

use mcr\auth\auth;
use mcr\html\blocks\base_block;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class block_banner implements base_block
{
	/**
	 * @var string
	 */
	public $tmpl = 'main';

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
		if (empty(auth::user()) || auth::user()->cannot($configs['PERMISSIONS'])) return null;

		return $this;
	}
}