<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 27.06.2018
 * @Time         : 22:30
 *
 * @Documentation:
 */

namespace modules;


use mcr\database\db;

abstract class base_module
{
	public $layout = 'global';

	/**
	 * Делает лог-запись в таблице действия пользователей.
	 *
	 * @param $msg
	 * @param $uid
	 *
	 * @return bool
	 * @throws \mcr\database\db_exception
	 */
	public function actlog($msg, $uid)
	{
		if (!empty(config('db'))) {
			if (!config('db::log')) {
				return false;
			}

			$uid = intval($uid);
			$msg = db::escape_string($msg);

			$date = time();

			$result = db::query(
				"INSERT INTO `mcr_logs` (`uid`, `message`, `date`)
				VALUES ('$uid', '$msg', $date)"
			)->result();

			if (!$result) return false;

			return true;
		}

		return false;
	}

	/*public function update_user($user)
	{
		if (!$user->is_auth) {
			return false;
		}
		$ctables = $this->cfg->db['tables'];
		$us_f = $ctables['users']['fields'];
		$update = $this->query("
			UPDATE `{$this->cfg->tabname('users')}`
			SET `{$us_f['ip_last']}`='{$user->ip}', `{$us_f['date_last']}`=NOW()
			WHERE `{$us_f['id']}`='{$user->id}'
		");
		if (!$update) {
			return false;
		}

		return true;
	}*/
}