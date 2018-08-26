<?php

namespace mcr\installer\modules;


use mcr\installer\install;
use mcr\validation\validator;

if (!defined("MCR")) exit("Hacking Attempt!");

class step_2 extends install_step
{
	use validator;

	/**
	 * @throws \mcr\validation\validation_exception
	 */
	public function register()
	{
		$user_data = array_trim_value($_POST);
		if ($this->check_register_data($user_data)) {

			$connection = new \mysqli(config('db::host'), config('db::username'), config('db::passwd'), config('db::basename'), config('db::port'));

			$login = $connection->real_escape_string($user_data['login']);
			$email = $connection->real_escape_string($user_data['email']);
			$salt = str_random(64);
			$password = bcrypt($user_data['password'] . $salt);

			$ip = $connection->real_escape_string(ip());

			self::try_query(
				$connection,
				"INSERT INTO `mcr_users` (`gid`, `login`, `email`, `password`, `uuid`, `salt`, `ip_last`, `time_create`) VALUE ('4', '$login', '$email', '$password', UNHEX(REPLACE(UUID(), '-', '')), '$salt', '$ip', NOW())",
				translate('e_add_admin')
			);

			self::try_query(
				$connection,
				"INSERT INTO `mcr_iconomy` (`login`, `money`, `realmoney`, `bank`) VALUE ('$login', 0, 0, 0)",
				translate('e_add_economy')
			);

			install::remember_step('step_2');
			install::to_next_step();
		}
	}

	public function register_form()
	{
		install::$page_title = translate('mod_name') . ' — ' . translate('step_2');

		return tmpl('steps.step_2');
	}

	/**
	 * @throws \mcr\validation\validation_exception
	 */
	private function check_register_data(array $data)
	{
		$this->validate($data, [
			'login'     => 'required|regex:/^[\w\-]{3,}$/i',
			'password'  => 'required',
			'repassword'=> 'required',
			'email'     => 'required|email'
		]);

		if ($data['password'] !== $data['repassword']) {
			$message = [ 'title' => translate('e_msg'), 'text' => translate('e_pass_match') ];

			return redirect()->with('message', $message)->url('/install/index.php?step_2/');
		}

		return true;
	}

	private static function throw_query_error($error)
	{
		$message = [ 'title' => translate('e_msg'), 'text' => $error ];

		return redirect()->with('message', $message)->url('/install/index.php?step_2/');
	}

	private static function try_query(\mysqli $connection, $query, $on_error_message)
	{
		if (!$connection->query($query)) self::throw_query_error($on_error_message);
	}
}