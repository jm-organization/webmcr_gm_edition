<?php
namespace blocks;

use mcr\auth\auth;
use mcr\html\blocks\base_block;
use mcr\html\blocks\standard_block;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class block_profile implements base_block
{
	public $tmpl = 'auth';

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
		if (!empty(auth::user())) {
			$this->tmpl = 'mini-profile';
		}

		return $this;
	}
}