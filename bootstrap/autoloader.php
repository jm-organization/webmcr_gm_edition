<?php

$aliases = require __DIR__ . '/../src/libraries.php';

/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 30.06.2018
 * @Time         : 19:35
 *
 * @Documentation: Главный автозагрузжик приложения.
 *
 * @param $classname
 */
function __autoload($classname) {
	global $aliases;

	// Извлекаем эллементы пространства имён загружаемого класса.
	// Определяем родительское мастер пространство имён загружаемого класса.
	// В стандарте PSR-4
	$class_paths = explode('\\', $classname);
	$root_class_path = $class_paths[0].'\\'.$class_paths[1].'\\';

	// Определяем полный путь к классу
	$class = __DIR__ . '/../src/' . $classname . '.class.php';
	// Если корневое пространство имён по стандарту PSR-4
	// имеется в алиасах подгрузки, то определяем к нему путь
	if (array_key_exists($root_class_path, $aliases) || array_key_exists($class_paths[0].'\\', $aliases)) {
		if (array_key_exists($class_paths[0].'\\', $aliases)) $root_class_path = $class_paths[0].'\\';
		$classname = str_replace($root_class_path, '', $classname);

		$class = __DIR__ . '/../src/libs/' . $aliases[$root_class_path] . '/' . $classname . '.php';
	}

	// Иначе загружаем по методу
	// __NAMESPACE__ => __PATH__ .php
	load_if_exist($class);
}

function load_if_exist($file) {

	$file = str_replace('\\', '/', $file);

	if (file_exists($file)) {
		//echo $file . '<br>';
		include_once $file;
	}

}
