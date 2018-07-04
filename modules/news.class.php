<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 27.06.2018
 * @Time         : 22:31
 *
 * @Documentation:
 */

namespace modules;


use mcr\html\document;
use mcr\http\request;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class news extends base_module implements module
{
	public $name = self::class;

	/**
	 * Обрабатывает запрос к модулю.
	 *
	 * @param request $request
	 *
	 * @return \mcr\http\response|\mcr\http\redirect|string
	 */
	public function content(request $request)
	{
		// Подключаем чтили и скрипты для страници
		document::$stylesheets .= asset('modules.news.header-styles-list', true);
		document::$scripts['body'] .= asset('modules.news.header-bscripts-list', true);

		return 'test';
	}
}