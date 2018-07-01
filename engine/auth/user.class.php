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

class user
{
	use autentificable;

	/**
	 * @var int
	 */
	public $id = 0;

	/**
	 * @var
	 */
	public $uuid;

	/**
	 * @var string
	 */
	public $group = '';

	/**
	 * @var string
	 */
	public $group_desc = '';

	/**
	 * @var string
	 */
	public $ip;

	/**
	 * @var mixed|null
	 */
	public $permissions = null;

	/**
	 * @var int|string
	 */
	public $gender = 0;

	/**
	 * @var int
	 */
	public $time_create = 0;

	/**
	 * @var int
	 */
	public $time_last = 0;

	/**
	 * @var bool
	 */
	public $is_skin = false;

	/**
	 * @var bool
	 */
	public $is_cloak = false;

	/**
	 * @var string
	 */
	public $skin = 'default';

	/**
	 * @var string
	 */
	public $cloak = '';

	/**
	 * @var float|int
	 */
	public $money = 0;

	/**
	 * @var float|int
	 */
	public $realmoney = 0;

	/**
	 * @var float|int
	 */
	public $bank = 0;

	/**
	 * @var int
	 */
	public $gid = -1;

	public function __construct($permissions)
	{
		$this->permissions = $permissions;
	}
}