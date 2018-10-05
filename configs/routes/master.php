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

$router->get('/', '\modules\magicmen\magicmcr\news@index', 'home');
$router->get('news', '\modules\magicmen\magicmcr\news@index', 'news');

$router->addGroup('auth/', function(route_collector $router) {
	$router->get('register', '\modules\magicmen\magicmcr\auth@register', 'auth.register');
	$router->post('login', '\modules\magicmen\magicmcr\auth@login', 'auth.login');
	$router->post('logout', '\modules\magicmen\magicmcr\auth@logout', 'auth.logout');
	$router->get('restore', '\modules\magicmen\magicmcr\auth@restore', 'auth.restore');
});

$router->addGroup('admin/', function(route_collector $router) {
	$router->get('dashboard', '\modules\magicmen\magicmcr\admin\dashboard@index', 'admin.dashboard');

	$router->addGroup('l10n/', function(route_collector $router) {
		$router->get('phrases[/{id:\d+}]', '\modules\magicmen\magicmcr\admin\l10n\phrases@index', 'admin.l10n.phrases');
		$router->post('phrases[/{id:\d+}]', '\modules\magicmen\magicmcr\admin\l10n\phrases@get_phrases');
	});

	$router->addGroup('settings/', function(route_collector $router) {
		$router->get('sitesettings', '\modules\magicmen\magicmcr\admin\settings\site_settings@index', 'admin.site.settings');
	});
});