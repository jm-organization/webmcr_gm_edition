<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 27.06.2018
 * @Time         : 22:17
 *
 * @Documentation:
 */

namespace modules;


use mcr\http\request;

interface module
{
	public function content(request $request);
}