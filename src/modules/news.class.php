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


use mcr\cache\cache;
use mcr\cache\cache_value;
use mcr\core\application;
use mcr\core\core_v2;
use mcr\html\breadcrumbs;
use mcr\http\request;
use mcr\validation\validator;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class news extends base_module implements module
{
	use validator;

	public $name = self::class;

	public function boot(application $app)
	{
		parent::boot($app);

		breadcrumbs::add(
			url('news'),
			translate('news')
		);
	}

	/**
	 * Обрабатывает запрос к модулю.
	 *
	 * @param request $request
	 *
	 * @return string
	 */
	public function index(request $request)
	{
		return '';
	}
}