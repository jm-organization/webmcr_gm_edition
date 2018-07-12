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
use mcr\core_v2;
use mcr\http\request;

function build(RouteCollector $router) {

	$router->get('/', '\modules\news@index');
	$router->get('news', '\modules\news@index');

	$router->addGroup('auth/', function(RouteCollector $router) {
		$router->get('register', '\modules\auth@register');
		$router->post('login', '\modules\auth@login');
		$router->get('restore', '\modules\auth@restore');
		$router->post('logout', '\modules\auth@logout');
	});

	$router->get('version', function(request $request) {
		core_v2::version();
	});

}