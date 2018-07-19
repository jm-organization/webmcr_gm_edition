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
	 * Хеш сумма авторизации
	 *
	 * @var string
	 */
	public static $hash = '';

	/**
	 * @var user|null
	 */
	private static $user = null;
	/**
	 * @var user|null
	 */
	private static $guest = null;

	/**
	 * @var bool
	 */
	private static $inited = false;

	/**
	 * @throws \mcr\database\db_exception
	 * @throws auth_exception
	 */
	public static function init()
	{
		// Инициализируем auth если не инициализирована.
		if (!self::$inited) {
			// если есть кука пользователя
			if (isset($_COOKIE['mcr_user'])) {

				// тогда зашёл пользователь сайта,
				// извлекаем его данные с бд

				// берём id и хеш сумму авторизации из куки
				$user_cookie = explode("_", $_COOKIE['mcr_user']);
				// если кол-во параметров куки 2 - она валидна на первом этпе её проверки
				if (count($user_cookie) == 2) {
					list($user_id, $auth_hash) = $user_cookie;

					// устанавливаем хеш авторизации
					self::$hash = $auth_hash;

					// получаем данные пользователя
					$user_data = self::get_all_user_data($user_id);
					$user = $user_data->fetch_assoc();

					// проверяем куку
					if (self::check_cookie($user)) {
						// если она правильная - возвршщаем пользователя
						self::$user = new user($user['permissions'], $user);
					} else {
						// если кука не правильная, она будет удаляена, а возвращён гость.
						self::set_guest();
					}

				} else {
					// удаляем невалидную куку
					setcookie("mcr_user", "");

					self::set_guest();
				}


			} else {
				// иначе зашёл гость - возвращаем гостя.
				self::set_guest();
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

	/**
	 * @return user
	 */
	public static function guest()
	{
		return self::$guest;
	}

	/**
	 * @throws \mcr\database\db_exception
	 * @throws auth_exception
	 */
	private static function set_guest()
	{
		$permissions = self::get_default_permissions();

		self::$guest = new user($permissions);
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
		if (file_exists(MCR_CACHE_PATH.'permissions')) {
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

	public static function ip()
	{
		$ip = $_SERVER['REMOTE_ADDR'];

		return mb_substr($ip, 0, 16, "UTF-8");
	}

	/**
	 * @param $user_id
	 *
	 * @return \mysqli_result|null
	 * @throws \mcr\database\db_exception
	 */
	private static function get_all_user_data($user_id)
	{
		$query = db::query("
			SELECT 
				`u`.`id`, `u`.`gid`, `u`.`login`, `u`.`email`, 
				`u`.`password`, `u`.`salt`, `u`.`tmp`, 
				`u`.`time_create`, `u`.`time_last`, `u`.`gender`,
				`u`.`is_skin`, `u`.`is_cloak`, `u`.`uuid`,
				
				`g`.`title` as `group`, `g`.`description` as `group_desc`, `g`.`permissions`, 
				`g`.`color` AS `gcolor`,
				
				`i`.`money`, `i`.`realmoney`, `i`.`bank`
			FROM `mcr_users` AS `u`
			
			INNER JOIN `mcr_groups` AS `g`
				ON `g`.`id`=`u`.`gid`
				
			LEFT JOIN `mcr_iconomy` AS `i`
				ON `i`.`login`=`u`.`login`
				
			WHERE `u`.`id`='$user_id'
			
			LIMIT 1
		");

		if ($query->result() && $query->num_rows > 0) {
			return $query->result();
		}

		return null;
	}

	private static function check_cookie(array $user)
	{
		$hash = $user['id'] . $user['tmp'] . self::ip() . md5(config('main::mcr_secury'));
		$hash = $user['id'] . '_' . md5($hash);

		if ($_COOKIE['mcr_user'] !== $hash) {
			setcookie("mcr_user", "");

			return false;
		}

		return true;
	}
}