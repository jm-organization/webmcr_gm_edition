<?php
/**
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

/**
 * Created in JM Organization.
 *
 * @e-mail: admin@jm-org.net
 * @Author: Magicmen
 *
 * @Date  : 29.07.2018
 * @Time  : 15:29
 */

include __DIR__.'/../../libs/fast-route/src/functions.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

function mcr_install_routes(RouteCollector $router)
{
	include 'routes.php';
}

function get_route()
{
	$uri = $_SERVER['REQUEST_URI'];

	if (stristr($uri, 'index.php?')) {
		$uri = trim(explode('?', $uri)[1], '/');
		if (strlen($uri) < 1) {
			$uri = '/';
		}

		return $uri;
	} else {
		header('Location: /install/index.php?start/');
	}

	exit;
}

$route = simpleDispatcher('mcr_install_routes')->dispatch($_SERVER['REQUEST_METHOD'], get_route());

if ($route[0] == FastRoute\Dispatcher::FOUND) {

	list($status, $handler, $additional_info) = $route;

} else {
	die('<a href="https://github.com/jm-organization/webmcr_gm_edition/issues/new">I can`t install MagicMCR</a>.');
}