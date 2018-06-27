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
 * @Documentation: Главный автозагрузжик приложения.
 */

//use mcr\core;
use mcr\config;
use mcr\core_v2;
use mcr\log;

function __autoload($classname) {
	// Извлекаем эллементы пространства имён загружаемого класса.
	$class_paths = explode('\\', $classname);

	// Определяем родительское мастер пространство имён загружаемого класса.
	$root_class_path = $class_paths[0];

	$class = $classname;

	if ($root_class_path == 'mcr') {

		$class = preg_replace('/mcr|engine/', ENGINE_ROOT_NAME, $classname);
	}

	// Определяем полный путь к классу
	$class = __DIR__ . '/../' . $class . '.class.php';

	// Иначе загружаем по методу
	// __NAMESPACE__ => __PATH__ .php
	load_if_exist($class);
}

function load_if_exist($file) {

	if (file_exists($file)) {
		include_once $file;
	}

}

$configs = new config();

$log = new log($configs->main['debug'], log::L_ALL);

register_shutdown_function(function () use ($log) {

	$error = error_get_last();

	if (!empty($error)) {
		$log->write($error['message'], $error['type'], $error['file'], $error['line']);

		// TODO: Сделать обработчик критических ошибок с выводом на экран!!!
		if ($error['type'] == log::FATAL_ERROR) {
			echo 'fatal error';
		}
	}

	return null;

});

$application = new core_v2($configs, $log);