<?php

namespace install;


use mcr\hashing\bcrypt_hasher;
use mcr\hashing\hashing_exception;
use mcr\log;

if (!defined('MCR')) {
	exit('Hacking Attempt!');
}

class install
{
	public $log = [];

	public $lng = [];

	public $title = '';

	public $header = '';

	public $hasher = null;

	public function __construct()
	{
		$https = (@$_SERVER['HTTPS'] == 'on' || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http';

		define('URL_ROOT', str_replace('\\', '/', dirname(dirname($_SERVER['PHP_SELF']))));
		define('URL_ROOT_FULL', $https.'://'.$_SERVER['SERVER_NAME'].'/');
		define('URL_INSTALL', $https.'://'.$_SERVER['SERVER_NAME'].'/install/');
		define('DIR_ROOT', dirname(dirname(__FILE__)).'/');
		define('DIR_INSTALL', dirname(__FILE__).'/');
		define('DIR_INSTALL_THEME', DIR_INSTALL.'theme/');

		$this->log = new log(config('main::debug'), log::L_ALL);

		require DIR_ROOT.'language/' . config('main::s_lang') . '/install.php';
		$this->lng = $lng;

		$this->hasher = new bcrypt_hasher();

		$this->title = $lng['mod_name'];
	}

	public function HSC($string)
	{
		return htmlspecialchars($string);
	}

	public function sp($page, $data = [])
	{
		ob_start();

		include(DIR_INSTALL_THEME.$page);

		return ob_get_clean();
	}

	public function init_step()
	{
		$do = (isset($_GET['do'])) ? $_GET['do'] : 'start';

		if (!preg_match("/^[\w\.]+$/i", $do)) {
			return 'Hacking Attempt';
		}

		$module = "\install\modules\\$do";
		if (class_exists($module)) {
			/** @var \install\modules\install_step $module */
			$module = new $module();

			return $module->content();
		}

		return 'Module not found';
	}

	public function notify($text = '', $title = '', $url = '')
	{
		$url = URL_ROOT.$url;

		$_SESSION['notify_title'] = $title;
		$_SESSION['notify_text'] = $text;

		header("Location: $url");

		exit();
	}

	public function get_notify()
	{
		if (!isset($_SESSION['notify_title']) || !isset($_SESSION['notify_text'])) {
			return;
		}

		if (empty($_SESSION['notify_title']) && empty($_SESSION['notify_text'])) {
			unset($_SESSION['notify_title']);
			unset($_SESSION['notify_text']);

			return;
		}

		$data = [
			'TITLE' => $_SESSION['notify_title'],
			'TEXT' => $_SESSION['notify_text'],
		];

		unset($_SESSION['notify_title']);
		unset($_SESSION['notify_text']);

		return $this->sp('notify.phtml', $data);
	}

	/**
	 * @function     : gen_password
	 *
	 * @documentation:
	 *
	 * @param string $string
	 * @param string $salt
	 *
	 * @return string
	 */
	public function gen_password($string, $salt = '')
	{
		try {
			return $this->hasher->make($string.$salt);
		} catch (hashing_exception $e) {
			echo 'Error: [ '.$e->getMessage().' ]';
		}
	}

	public function logintouuid($string)
	{
		$string = "OfflinePlayer:".$string;
		$val = md5($string, true);
		$byte = array_values(unpack('C16', $val));

		$tLo = ($byte[0] << 24) | ($byte[1] << 16) | ($byte[2] << 8) | $byte[3];
		$tMi = ($byte[4] << 8) | $byte[5];
		$tHi = ($byte[6] << 8) | $byte[7];
		$csLo = $byte[9];
		$csHi = $byte[8] & 0x3f | (1 << 7);

		if (pack('L', 0x6162797A) == pack('N', 0x6162797A)) {
			$tLo = (($tLo & 0x000000ff) << 24) | (($tLo & 0x0000ff00) << 8) | (($tLo & 0x00ff0000) >> 8) | (($tLo & 0xff000000) >> 24);
			$tMi = (($tMi & 0x00ff) << 8) | (($tMi & 0xff00) >> 8);
			$tHi = (($tHi & 0x00ff) << 8) | (($tHi & 0xff00) >> 8);
		}

		$tHi &= 0x0fff;
		$tHi |= (3 << 12);

		$uuid = sprintf('%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x', $tLo, $tMi, $tHi, $csHi, $csLo, $byte[10], $byte[11], $byte[12], $byte[13], $byte[14], $byte[15]);

		return $uuid;
	}

	public function ip()
	{
		if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
			$ip = $_SERVER['HTTP_X_REAL_IP'];
		} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return mb_substr($ip, 0, 16, "UTF-8");
	}

	public function random($length = 10, $safe = true)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
		if (!$safe) {
			$chars .= '$()#@!';
		}

		$string = "";

		$len = strlen($chars) - 1;
		while (strlen($string) < $length) {
			$string .= $chars[mt_rand(0, $len)];
		}

		return $string;
	}
}

?>