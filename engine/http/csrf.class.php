<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 01.07.2018
 * @Time         : 0:04
 *
 * @Documentation:
 */

namespace mcr\http;


trait csrf
{
	public $csrf_time = 3600;

	public function gen_csrf_secure()
	{
		/*$time = time();
		$new_key = $time . '_' . md5($this->user->ip . $this->cfg->main['mcr_secury'] . $time);

		if (!isset($_COOKIE['mcr_secure'])) {
			setcookie("mcr_secure", $new_key, time() + $this->csrf_time, '/');
			return $new_key;
		}

		$cookie = explode('_', $_COOKIE['mcr_secure']);
		$old_time = intval($cookie[0]);
		$old_key = md5($this->user->ip . $this->cfg->main['mcr_secury'] . $old_time);

		if (!isset($cookie[1]) || $cookie[1] !== $old_key || ($old_time + $this->csrf_time) < $time) {
			setcookie("mcr_secure", $new_key, time() + $this->csrf_time, '/');
			return $new_key;
		}

		return $_COOKIE['mcr_secure'];*/
		return '';
	}
}