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


use mcr\http\redirect;
use mcr\http\request;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class news extends base_module implements module
{
	/**
	 * @param request $request
	 */
	public function content(request $request)
	{
		//
	}
}