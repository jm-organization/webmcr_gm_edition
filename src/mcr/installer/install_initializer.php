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
 * @Time  : 15:11
 */

use mcr\config;
use mcr\installer\install;
use mcr\log;

error_reporting(0);

include __DIR__ . '/../../../bootstrap/autoloader.php';

$configs = config::get_instance();

// Запускаем логирование
$log = new log($configs->get('mcr::app.debug'), log::L_ALL);
// Регистрируем функцию, которая будет отслеживать события,
// которые прекращаюит работу скрипта
register_shutdown_function(function () use ($log) {

	$error = error_get_last();

	if (!empty($error)) {
		$log->write($error['message'], $error['type'], $error['file'], $error['line']);

		// TODO: Сделать обработчик критических ошибок с выводом на экран!!!
		if ($error['type'] == log::FATAL_ERROR) {
			echo '[fatal error] ';
			echo $error['message'];
		}
	}

	return null;

});

define('INSTALL_INIT_TIME', time());

$installer = new install(INSTALL_INIT_TIME, $configs);