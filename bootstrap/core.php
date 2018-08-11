<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 26.06.2018
 * @Time         : 19:27
 *
 * @Documentation: Скрипт запуска ядра приложения
 */

//use mcr\core;
use mcr\core\configs\config;
use mcr\core\application\application;
use mcr\core\core_v2;
use mcr\log;

include 'autoloader.php';

// Загружаем конфиги
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

// Создаём приложение по конфигам.
$application = new application($configs);