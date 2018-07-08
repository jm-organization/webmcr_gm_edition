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
use mcr\user;
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
	 */
	public function content(request $request)
	{
		if ($request::method() == 'POST') {
			// Если пользователь не авторизован, выполняем его авторизацию:
			if (empty(current_auth::user())) {

				$this->validate($request->all(), [
					'login' => 'required|regex:/[a-zA-Z0-9_]*/i',
					'password' => 'required'
				]);

				return redirect('/');

			} else {
				return redirect()->with('message', ['text' => translate('auth_already'), 'type' => 1])->route('/');
			}
		} else {
			return redirect()->with('message', ['text' => 'Hacking Attempt!'])->route('/');
		}
	}
}