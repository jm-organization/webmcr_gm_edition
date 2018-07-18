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


use mcr\http\request;
use mcr\validation\validator;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class news extends base_module implements module
{
	use validator;

	public $name = self::class;

	/**
	 * Обрабатывает запрос к модулю.
	 *
	 * @param request $request
	 *
	 * @return \mcr\http\response|\mcr\http\redirect|string
	 * @throws \engine\http\routing\url_builder_exception
	 */
	public function index(request $request)
	{
		return '<a href="' . url('home', ['test', 'foo' => 'bar', 'test' => 'привет']) . '">' . url('home', ['test', 'foo' => 'bar', 'test' => 'привет']) . '</a>';
	}
}