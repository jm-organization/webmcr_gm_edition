<?php
/**
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

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

namespace modules\magicmen\magicmcr;


use mcr\cache\cache;
use mcr\cache\cache_value;
use mcr\core\application\application;
use mcr\core\core_v2;
use mcr\html\breadcrumbs;
use mcr\http\module;
use mcr\http\request;
use mcr\validation\validator;
use modules\magicmen\magicmcr\base_module;

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