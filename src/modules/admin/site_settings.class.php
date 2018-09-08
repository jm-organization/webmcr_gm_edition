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
 * @e-mail: admin@jm-org.net
 * @Author: Magicmen
 *
 * @Date  : 08.09.2018
 * @Time  : 17:36
 */

namespace modules\admin;


use mcr\core\application\application;
use mcr\html\breadcrumbs;
use mcr\http\request;
use modules\module;

class site_settings extends admin implements module
{
	public function boot(application $app)
	{
		parent::boot($app);

		breadcrumbs::add(
			url('admin.site.settings'),
			translate('site_settings')
		);
	}

	/**
	 * Обрабатывает запрос к модулю.
	 *
	 * @param request $request
	 *
	 * @return \mcr\http\response|\mcr\http\redirect_response|string
	 */
	public function index(request $request)
	{
		return tmpl('modules.admin.settings.sitesettings.index');
	}
}