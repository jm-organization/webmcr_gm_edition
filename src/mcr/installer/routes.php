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
 * @Date  : 30.07.2018
 * @Time  : 0:55
 */

/** @var \FastRoute\RouteCollector $router */

// Проверка тех характеристик
$router->get('start', 'start@get_requirements');
$router->post('start', 'start@validate_requirements');

// Настройка соединения с базой
$router->get('step_1', 'step_1@create');
$router->post('step_1', 'step_1@save');

// Регистрация суперпользователя
$router->get('step_2', 'step_2@register_form');
$router->post('step_2', 'step_2@register');

// Базоые настрйоки сайта
$router->get('step_3', 'step_3@settings_form');
$router->post('step_3', 'step_3@save_settings');

// Готово!
$router->get('finish', 'finish@done_installing');

$router->get('reinstall[/{app_key}]', 'reinstall@confirm');
$router->post('reinstall[/{app_key}]', 'reinstall@reinstall');