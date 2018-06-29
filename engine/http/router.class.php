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
	 * TODO: Необходимо рассмотреть надобновть в переписании метода.
	 *
	 *
	 * @return string
	 */
	public function base_url()
	{
		$pos = strripos($_SERVER['PHP_SELF'], 'install/index.php');

		if ($pos === false) {
			$pos = strripos($_SERVER['PHP_SELF'], 'index.php');
		}

		return mb_substr($_SERVER['PHP_SELF'], 0, $pos, 'UTF-8');
	}
}