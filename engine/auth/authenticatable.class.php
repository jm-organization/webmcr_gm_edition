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

trait authenticatable
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

	/**
	 * @param $auth_data
	 *
	 * @return bool|int
	 * @throws \mcr\database\db_exception
	 * @throws auth_exception
	 */
	public function authenticate($auth_data)
	{
		if (!$this->is_auth) {
			// Берём данные пользователя по введённому логину/мылу
			$user = $this->get_user($auth_data['login']);

			// Если пользователь найден, то проверяем на соответсвие паролей
			if (!empty($user)) {

				// если у пользователя нет права на авторизацию,
				// то возвращаем об этом сообщение
				$user_permissions = @json_decode($user['permissions']);
				if (empty($user_permissions) || !$user_permissions->sys_auth) {
					return redirect()->with('message', [
						'title' => translate('error_403'),
						'text' => translate('auth_access'),
					])->route('/?403');
				}

				global $application;
				$hasher = $application::$hasher;

				$password = $auth_data['password'].$user['salt'];

				// Если введёный пароль совпадает с паролем пользователя
				if ($hasher->check($password, $user['password'])) {
					// Устанавливаем маркер аутентификации
					$this->is_auth = true;

					// устанавливаем временные данные авторизации пользователя
					$temp_hash = $this->update_user_tmp();
					// устанавливаем кукис чтобы запомнить авторизацию
					$this->set_cookie($user, $temp_hash, $auth_data['remember']);
				}

			} else {
				return redirect()->with('message', [
					'title' => translate('error_message'),
					'text' => translate('wrong_pass'),
				])->route('/?wrong_pass');
			}
		}

		return $this->is_auth;
	}

	/**
	 * @param $login
	 *
	 * @return array|null
	 * @throws \mcr\database\db_exception
	 */
	private function get_user($login)
	{
		$query = db::query("
				SELECT 
					`u`.`email`, `u`.`password`, `u`.`salt`,
					`u`.`id`,
					
					`g`.`permissions`
				FROM `mcr_users` AS `u`
				
				INNER JOIN `mcr_groups` AS `g`
					ON `g`.`id`=`u`.`gid`
					
				WHERE `u`.`login`='$login' OR `u`.`email`='$login'
				
				LIMIT 1
			");

		if ($query->result() && $query->num_rows == 1) {

			$user = $query->fetch_assoc();

			$this->login = $login;
			$this->password = $user['password'];
			$this->salt = $user['salt'];
			$this->email = $user['email'];

			return $user;

		} else {
			return null;
		}
	}

	/**
	 * @throws \mcr\database\db_exception
	 * @throws auth_exception
	 */
	private function update_user_tmp()
	{
		$this->tmp = str_random(16);
		$ip = auth::ip();

		// Обновляем tmp-hash, ip и время оследней активности у пользователя
		if (!db::query("
			UPDATE `mcr_users`
			SET 
				`tmp`='{$this->tmp}', 
				`ip_last`='$ip', 
				`time_last`=NOW()
			WHERE `login`='{$this->login}'
			LIMIT 1
		")->result()) {
			throw new auth_exception('user::authenticate(): Can`t update tmp-has for user.');
		};

		return $this->tmp . $ip;
	}

	private function set_cookie($user, $temp_hash, $remember)
	{
		$hash = $user['id'] . $temp_hash . md5(config('main::mcr_secury'));
		$hash = $user['id'] . '_' . md5($hash);

		// устанавливаем время жизни пользователя,
		// если установленно "запомнить" -
		// максимальная жизни кукисов из серверных настроек
		$safetime = ($remember) ? time() + MAX_COOKIE_LIFETIME : time() + 3600;

		setcookie("mcr_user", $hash, $safetime, '/');
	}
}