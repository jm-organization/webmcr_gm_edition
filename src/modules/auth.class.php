<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 01.07.2018
 * @Time         : 15:12
 *
 * @Documentation:
 */

namespace modules;

use mcr\auth\auth as current_auth;

use mcr\database\db;
use mcr\http\module;
use mcr\http\request;
use mcr\validation\validator;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class auth extends base_module implements module
{
	use validator;

	public function index(request $request) { }

	/**
	 * @param request $request
	 *
	 * @return \mcr\http\redirect_response|\mcr\http\response|string
	 * @throws \mcr\validation\validation_exception
	 * @throws \mcr\database\db_exception
	 * @throws \mcr\auth\auth_exception
	 */
	public function login(request $request)
	{
		// Если пользователь не авторизован, выполняем его авторизацию:
		if (empty(current_auth::user())) {

			// Проверяем пришедшие данные
			$this->validate($request->all(), [
				'login' => 'required|regex:/[a-zA-Z0-9_]*/i',
				'password' => 'required'
			]);

			$authenticated = current_auth::guest()->authenticate([
				'login' => $request->login,
				'password' => db::escape_string($request->password),
				'remember' => !empty($request->remember) && $request->remember == 1,
			]);

			if (!$authenticated) {
				// если не прошла аутентификация, говим об этом.
				return redirect()->with('message', [
					'title' => translate('error_message'),
					'text' => translate('wrong_pass'),
				])->route('home', ['403']);
			} else {
				// если всё ок, делаем юзер лог-запись
				$this->actlog(translate('log_auth'), current_auth::guest()->id);

				// возвращаем саццесс
				return redirect()->with('message', [
					'title' => translate('auth_success'),
					'text' => translate('error_success'),
					'type' => 3
				])->route('home');
			}

		} else {
			return redirect()->with('message', ['text' => translate('auth_already'), 'type' => 1])->route('home');
		}
	}

	/**
	 * Обрабатывает запрос к модулю.
	 *
	 * @param request $request
	 *
	 * @return \mcr\http\response|\mcr\http\redirect_response|string
	 * @throws \mcr\database\db_exception
	 */
	public function logout(request $request)
	{
		// Если пользователь не авторизован, выполняем его авторизацию:
		if (!empty(current_auth::user()) && current_auth::user()->is_auth) {

			$tmp = str_random(16);
			$user_id = current_auth::user()->id;

			// Последнее обновление пользователя
			current_auth::user()->update();

			if (!db::query("
					UPDATE `mcr_users` 
					SET 
						`tmp`='$tmp', 
						`time_last`=NOW()
					WHERE `id`='{$user_id}'
					LIMIT 1
				")->result()) {
				return redirect()->with('message', [
					'title' => translate('error_attention'),
					'text' => translate('error_sql_critical')
				]);
			}

			setcookie("mcr_user", "");

			// Лог действия
			$this->actlog(translate('log_logout'), $user_id);

			return redirect(url('home'));

		} else {
			return redirect()->with('message', [
				'text' => translate('not_auth_error'),
				'title' => translate('error_403'),
				'type' => 1
			])->route('home', ['403']);
		}
	}
}