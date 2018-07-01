<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 01.07.2018
 * @Time         : 11:17
 *
 * @Documentation:
 */

namespace mcr\auth;


use mcr\database\db;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class auth
{
	/**
	 * @var user|null
	 */
	private static $user = null;

	private static $inited = false;

	/**
	 * @throws \mcr\database\db_exception
	 * @throws auth_exception
	 */
	public static function init()
	{
		if (!self::$inited) {
			if (isset($_COOKIE['mcr_user'])) {
				$permissions = self::get_default_permissions();

				self::$user = new user($permissions);
			}

			self::$inited = true;
		}
	}

	/**
	 * @function     : user
	 *
	 * @documentation:
	 *
	 *
	 * @return user|null
	 */
	public static function user()
	{
		return self::$user;
	}

	public static function guest()
	{
		// return guest
	}

	/**
	 * @function     : get_default_permissions
	 *
	 * @documentation:
	 *
	 *
	 * @return object
	 * @throws \mcr\database\db_exception
	 * @throws auth_exception
	 */
	private static function get_default_permissions()
	{
		if (file_exists(MCR_CACHE_PATH . 'permissions')) {
			$json = file_get_contents(MCR_CACHE_PATH.'permissions');

			return @json_decode($json);
		}

		$permissions = @json_decode(self::update_default_permissions());

		return $permissions;
	}

	/**
	 * @function     : update_default_permissions
	 *
	 * @documentation:
	 *
	 *
	 * @return null|string
	 * @throws \mcr\database\db_exception
	 * @throws auth_exception
	 */
	private static function update_default_permissions()
	{
		$query = db::query("SELECT `value`, `type`, `default` FROM `mcr_permissions`");

		if ($query->result()) {

			if ($query->num_rows > 0) {
				$permissions = [];

				while ($ar = $query->fetch_assoc()) {
					switch ($ar['type']) {
						case 'integer':
							$permissions[$ar['value']] = intval($ar['default']);
							break;

						case 'float':
							$permissions[$ar['value']] = floatval($ar['default']);
							break;

						case 'string':
							$permissions[$ar['value']] = db::escape_string($ar['default']);
							break;

						default:
							$permissions[$ar['value']] = ($ar['default'] == 'true') ? true : false;
							break;
					}
				}

				$permissions = json_encode($permissions);
				@file_put_contents(MCR_CACHE_PATH.'permissions', $permissions);

				return $permissions;
			} else {
				return null;
			}

		} else {
			throw new auth_exception("auth::update_default_permissions(): Query result type error. See logs");
		}
	}
}