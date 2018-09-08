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
 * @Date  : 07.09.2018
 * @Time  : 20:27
 */

namespace modules\admin;


use mcr\core\application\application;
use mcr\html\breadcrumbs;
use mcr\html\document;

abstract class admin
{
	public $layout = 'modules.admin.global'; //

	public $name = 'admin';

	/**
	 * Метод, который вызывается при загрузке модуля.
	 * Принимает экземпляр ядра.
	 *
	 * @param application $app
	 *
	 * @return void
	 */
	public function boot(application $app)
	{
		breadcrumbs::add(
			url('admin.dashboard'),
			translate('module_admin-panel')
		);

		global $log;

		document::$variables['errors_count'] = $log->get_logs_num('error');
	}
}