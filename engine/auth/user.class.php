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

use mcr\database\db;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class user
{
	use authenticatable,
		permissions
	;

	/**
	 * @var int
	 */
	public $id = 0;

	/**
	 * @var string|null
	 */
	public $uuid = null;

	/**
	 * @var int
	 */
	public $gid = -1;

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
	public $ip = '127.1.0.1';

	/**
	 * @var mixed|null
	 */
	//public $permissions = null;

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

	public function __construct($permissions, array $all_data = null)
	{
		$this->set_ip(auth::ip());
		$this->set_time_last(time());
		$this->set_time_create(null);
		$this->set_permissions($permissions);

		// Если были переданы не только права пользователя,
		// то заполняем информацию о пользователе
		if (!empty($all_data)) {
			$this->fill_user($all_data);
		}
	}

	/**
	 * @param mixed $uuid
	 */
	public function set_uuid($uuid)
	{
		$this->uuid = $uuid;
	}

	/**
	 * @param string $group
	 */
	public function set_group($group)
	{
		$this->group = $group;
	}

	/**
	 * @param string $group_desc
	 */
	public function set_group_desc($group_desc)
	{
		$this->group_desc = $group_desc;
	}

	/**
	 * @param string $ip
	 */
	public function set_ip($ip)
	{
		$this->ip = $ip;
	}

	/**
	 * @param mixed|null $permissions
	 */
	public function set_permissions($permissions)
	{
		if (!is_object($permissions)) {
			$permissions = @json_decode($permissions);
		}

		$this->permissions = $permissions;
	}

	/**
	 * @param int|string $gender
	 */
	public function set_gender($gender)
	{
		$this->gender = $gender;
	}

	/**
	 * @param int $time_create
	 */
	public function set_time_create($time_create)
	{
		$this->time_create = $time_create;
	}

	/**
	 * @param int $time_last
	 */
	public function set_time_last($time_last)
	{
		$this->time_last = $time_last;
	}

	/**
	 * @param string $skin
	 */
	public function set_skin($skin)
	{
		$this->skin = $skin;
	}

	/**
	 * @param string $cloak
	 */
	public function set_cloak($cloak)
	{
		$this->cloak = $cloak;
	}

	/**
	 * @param float|int $money
	 */
	public function set_money($money)
	{
		$this->money = $money;
	}

	/**
	 * @param float|int $realmoney
	 */
	public function set_realmoney($realmoney)
	{
		$this->realmoney = $realmoney;
	}

	/**
	 * @param float|int $bank
	 */
	public function set_bank($bank)
	{
		$this->bank = $bank;
	}

	/**
	 * @param int $gid
	 */
	public function set_gid($gid)
	{
		$this->gid = $gid;
	}

	private function fill_user(array $user_data)
	{
		foreach ($user_data as $key => $value) {
			if ($key != 'permissions') {

				$method_name = 'set_' . $key;

				if (method_exists($this, $method_name)) {
					$this->$method_name($value);
				}

			}
		}

		$this->id = $user_data['id'];

		$this->login = $user_data['login'];
		$this->password = $user_data['password'];
		$this->salt = $user_data['salt'];
		$this->email = $user_data['email'];
		$this->tmp = $user_data['tmp'];

		$this->is_auth = true;
	}

	/**
	 * @return bool|\mysqli_result|null
	 * @throws \mcr\database\db_exception
	 */
	public function update()
	{
		if ($this->is_auth) {
			$ip = auth::ip();

			return db::query("
				UPDATE `mcr_users`
				SET 
					`ip_last`='$ip', 
					`time_last`=NOW()
				WHERE `id`='{$this->id}'
			")->result();
		}

		return false;
	}


}