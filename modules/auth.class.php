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
use mcr\http\request;
use mcr\validation\validator;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class auth extends base_module implements module
{
	use validator;

	/**
	 * @param request $request
	 *
	 * @return \mcr\http\redirect|\mcr\http\response|string
	 * @throws \mcr\validation\validation_exception
	 * @throws \mcr\database\db_exception
	 * @throws \mcr\auth\auth_exception
	 */
	public function content(request $request)
	{
		if ($request::method() == 'POST') {
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
						])->route('/?wrong_pass');
					} else {
						// если всё ок, делаем юзер лог-запись
						$this->actlog(translate('log_auth'), current_auth::user()->id);

						// возвращаем саццесс
						return redirect()->with('message', [
							'title' => translate('auth_success'),
							'text' => translate('error_success'),
							'type' => 3
						])->route('/');
					}

			} else {
				return redirect()->with('message', ['text' => translate('auth_already'), 'type' => 1])->route('/');
			}
		} else {
			return redirect()->with('message', ['text' => 'Hacking Attempt!'])->route('/');
		}
	}
}