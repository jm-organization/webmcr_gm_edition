<?php

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

	$file = str_replace('\\', '/', $file);

	if (file_exists($file)) {
		//echo $file . '<br>';
		include_once $file;
	}

}