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


use mcr\database\db;
use mcr\database\db_result;
use mcr\http\request;

class news extends base_module implements module
{
	/**
	 * @param request $request
	 *
	 * @throws \mcr\database\db_exception
	 */
	public function content(request $request)
	{
		echo '<h1>Hello World!</h1>';
	}
}