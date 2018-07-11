<?php
namespace modules;

use mcr\database\db;
use mcr\http\request;
use mcr\auth\auth;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class logout extends base_module implements module
{
	/**
	 * Обрабатывает запрос к модулю.
	 *
	 * @param request $request
	 *
	 * @return \mcr\http\response|\mcr\http\redirect|string
	 * @throws \mcr\database\db_exception
	 */
	public function index(request $request)
	{
		if ($request::method() == 'POST') {
			// Если пользователь не авторизован, выполняем его авторизацию:
			if (!empty(auth::user()) && auth::user()->is_auth) {

				$tmp = str_random(16);
				$user_id = auth::user()->id;

				// Последнее обновление пользователя
				auth::user()->update();

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

				return redirect('/');

			} else {
				return redirect()->with('message', [
					'text' => translate('not_auth_error'),
					'title' => translate('error_403'),
					'type' => 1
				])->route('/');
			}
		} else {
			return redirect()->with('message', ['text' => 'Hacking Attempt!'])->route('/');
		}
	}
}