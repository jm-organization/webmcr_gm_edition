<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 26.06.2018
 * @Time         : 19:33
 *
 * @Documentation:
 */

// Фиксируем время начала загрузки страници.
define("DEBUG_PLT", microtime(true));
define('MCR', '');

if (file_exists('./init-krumo.php')) require_once("./init-krumo.php");

// Загружаем файл, где определяются константы
require 'bootstrap/constants.php';

ini_set("upload_max_filesize", MAX_FILE_SIZE);
ini_set("post_max_size", MAX_POST_REQUEST_DATA_SIZE);
@date_default_timezone_set(TIMEZONE);

// Запрещаем вывод ошибок.
// Для адекватного логирования
error_reporting(0);

session_save_path(MCR_ROOT . 'data/tmp');
ini_set('session.gc_maxlifetime', MAX_SESSION_LIFETIME);
ini_set('session.cookie_lifetime', MAX_COOKIE_LIFETIME);
if (!session_start()) { session_start(); }


// Загружаем ядро
require ENGINE_ROOT_NAME . '/filter.php';
require 'bootstrap/core.php';

require 'bootstrap/helpers.php';

$application->run();