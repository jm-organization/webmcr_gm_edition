<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 19.07.2018
 * @Time         : 21:37
 *
 * @Documentation:
 */

namespace mcr\http\routing;


class route_builder
{
	const routes_dir = MCR_CONF_PATH . 'routes/';

	public static function build(route_collector $router)
	{
		$routes = scandir(route_builder::routes_dir);

		foreach ($routes as $route) {
			if ($route == '.' || $route == '..') continue;

			require route_builder::routes_dir . $route;
		}
	}
}