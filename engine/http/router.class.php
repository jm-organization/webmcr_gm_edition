<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 27.06.2018
 * @Time         : 20:59
 *
 * @Documentation:
 */

namespace mcr\http;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class router
{
	private $mode = '';

	public $controller = '';

	public $action = 'content';

	/*
	 * Constructor for router
	 */
	 public function __construct()
	 {
	 	if (isset($_GET['mode'])) {
	    	$this->mode = $_GET['mode'];
		} else {
	 		$this->mode = config('main::s_dpage');
		}


		$this->controller = '\modules\\' . $this->mode;
	 }

	public function get_mode()
	{
		return $this->mode;
	}

	/**
	 * @function     : base_url
	 *
	 * @documentation: Возвращает базовый адрес сайта.
	 *
	 * @param bool $short
	 *
	 * @return string
	 */
	public static function base_url($short = false)
	{
		if ($short) {
			$pos = strripos($_SERVER['PHP_SELF'], 'install/index.php');

			if ($pos === false) {
				$pos = strripos($_SERVER['PHP_SELF'], 'index.php');
			}

			$_base_url =  mb_substr($_SERVER['PHP_SELF'], 0, $pos, 'UTF-8');
		} else {
			$_base_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];

			if ($_SERVER['SERVER_PORT'] != 80) {
				$_base_url .= ':' . $_SERVER['SERVER_PORT'];
			}
		}

		return $_base_url . '/';

	}
}