<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 01.07.2018
 * @Time         : 11:18
 *
 * @Documentation:
 */

namespace mcr\auth;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

trait autentificable
{
	/**
	 * @var string
	 */
	public $email = '';

	/**
	 * @var string
	 */
	public $login = '';

	/**
	 * @var string
	 */
	private $password = '';

	/**
	 * @var string
	 */
	private $salt;

	/**
	 * @var string|null
	 */
	private $tmp = null;

	/**
	 * @var bool
	 */
	public $is_auth = false;

	public function autentificate($post_password, $password, $salt = '')
	{
		if (!$this->is_auth) {
			$post_password = passwd_hash($post_password, $salt);

			if ($post_password === $password) {
				$this->is_auth = true;

				return true;
			}

			return false;
		}

		return true;
	}

	private function generate_tmp()
	{
		return str_random(16);
	}
}