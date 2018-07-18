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

use mcr\core_v2;
use mcr\http\request;
use mcr\http\routing\route_collector;

function build(route_collector $router) {

	$router->get('/', '\modules\news@index', 'home');
	$router->get('news', '\modules\news@index');

	$router->addGroup('auth/', function(route_collector $router) {
		$router->get('register', '\modules\auth@register', 'auth.register');
		$router->post('login', '\modules\auth@login', 'auth.login');
		$router->post('logout', '\modules\auth@logout', 'auth.logout');
		$router->get('restore', '\modules\auth@restore', 'auth.restore');
	});

	$router->get('version', function(request $request) {
		core_v2::version();
	});

}