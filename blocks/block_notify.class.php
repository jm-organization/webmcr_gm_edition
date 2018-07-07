<?php
namespace blocks;

use mcr\html\blocks\base_block;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class block_notify implements base_block
{
	/**
	 * @var array|null
	 */
	public $data = null;

	/**
	 * @var string
	 */
	public $tmpl = 'alert';

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
		// Выходим из инициализации, если нету блока сообщений в сессии
		if (!isset($_SESSION['messages'])) return null;

		$messages = $_SESSION['messages'];
		$this->data = [ 'messages' => $messages ];

		unset($_SESSION['messages']);

		return $this;
	}
}