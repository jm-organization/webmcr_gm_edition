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


use mcr\http\request;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class auth extends base_module implements module
{
	public function content(request $request)
	{
		// TODO: Implement content() method.
	}
}