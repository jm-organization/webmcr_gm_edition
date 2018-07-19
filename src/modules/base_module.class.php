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

	public $name = '';

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
}