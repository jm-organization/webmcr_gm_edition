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
 */

use mcr\http\routing\route_collector;

/** @var route_collector $router */

$router->get('/', '\modules\news@index', 'home');
$router->get('news', '\modules\news@index', 'news');

$router->addGroup('auth/', function(route_collector $router) {
	$router->get('register', '\modules\auth@register', 'auth.register');
	$router->post('login', '\modules\auth@login', 'auth.login');
	$router->post('logout', '\modules\auth@logout', 'auth.logout');
	$router->get('restore', '\modules\auth@restore', 'auth.restore');
});

$router->addGroup('admin/', function(route_collector $router) {
	$router->get('dashboard', '\modules\admin\dashboard@index', 'admin.dashboard');

	$router->addGroup('l10n/', function(route_collector $router) {
		$router->get('phrases[/{id:\d+}]', '\modules\admin\l10n\phrases@index', 'admin.l10n.phrases');
	});

	$router->addGroup('settings/', function(route_collector $router) {
		$router->get('sitesettings', '\modules\admin\site_settings@index', 'admin.site.settings');
	});
});