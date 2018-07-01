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

use mcr\http\request;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class auth extends base_module implements module
{
	public function content(request $request)
	{
		// Если метод запроса не POST возвращаем ошибку
		if ($request::method() != 'POST') {
			redirect()->with([
				'messages' => [ ['text' => 'Hacking Attempt!'] ]
			])->route('/');
		}

		// Если пользователь уже авторизован, сообщаем об этом.
		if (!empty(current_auth::user()) && current_auth::user()->is_auth) {
			redirect()->with([
				'messages' => [ ['text' => translate('auth_already'), 'type' => 1] ]
			])->route('/');
		}
	}
}