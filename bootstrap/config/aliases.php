<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 07.07.2018
 * @Time         : 23:29
 *
 * @Documentation: Список алиасов для загрузки библиотек
 * Список формируется по правилам: __ROOT_NAMESPACE__ => __PATH_TO_ROOT_NAMESPACE__
 * __PATH_TO_ROOT_NAMESPACE__ определяется относительно директории engine/libs/
 */

return [
	// Библиотечка для валидации данных
	'Particle\\Validator\\' => 'particle-php/src'
];