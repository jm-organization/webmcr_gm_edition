<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 11.07.2018
 * @Time         : 22:50
 *
 * @Documentation:
 */

namespace router_builder;

use FastRoute\RouteCollector;

function build(RouteCollector $router) {

	$router->get('/', '\modules\news@index');
	$router->get('news', '\modules\news@index');

	//$router->get('admin/news/control', '\modules\news');

}