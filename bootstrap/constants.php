<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 26.06.2018
 * @Time         : 20:14
 */


// Имя директории, в которой следует искать файлы библиотек, ядро приложения и т.п.
define('ENGINE_ROOT_NAME', 'engine');

// Утилитарные константы для версионирования.
define('PROGNAME', 			'MagicMCR' . MCR);
define('VERSION', 			'webmcr_gm_edition_v1.21-beta');
define('FEEDBACK', 			PROGNAME . ' powered by <a href="http://webmcr.com" target="_blank">WebMCR</a> &copy; 2017-' . date("Y") . ' <a href="http://www.jm-org.net/about/#Magicmen">Magicmen</a>');


//////////////////////////////////////////////////////////////////////////////
// Общедоступные директории

// Константа главной директории.
define('MCR_ROOT',	 		dirname(dirname(__FILE__)) . '/');

// Директория доступных модулей.
define('MCR_MODE_PATH', 	MCR_ROOT . 'modules/');

// Ядро.
define('MCR_TOOL_PATH', 	MCR_ROOT . ENGINE_ROOT_NAME . '/');

// Библиотеки.
define('MCR_LIBS_PATH', 	MCR_TOOL_PATH . 'libs/');

// Утилитарные классы для работы с мониторингом серверов.
define('MCR_MON_PATH', 		MCR_TOOL_PATH . 'monitoring/');

// Блоки.
define('MCR_SIDE_PATH', 	MCR_ROOT . 'blocks/');

// Языки (DEPRECATED)
define('MCR_LANG_PATH', 	MCR_ROOT . 'language/');

// Файлы конфигураций движка
define('MCR_CONF_PATH', 	MCR_ROOT . 'configs/');

// Директория, в которую загружаются файлы загруженные через web-интерфейс.
define('MCR_UPL_PATH', 		MCR_ROOT . 'data/uploads/');

// Директория кеша.
define('MCR_CACHE_PATH', 	MCR_ROOT . 'data/cache/');


//////////////////////////////////////////////////////////////////////////////
// Константы для серверных настроек.

/**
 * Максимальное время жизни сесии и кукисов
 *
 * Рекомендуется отавлять их одинаковыми
 */
define('MAX_COOKIE_LIFETIME', 	3600 * 24 * 30); // 30 дней
define('MAX_SESSION_LIFETIME', 	3600 * 24 * 30); // 30 дней

/** Максимальный размер загружаемых файлов
 *
 * Не должен быть больше,
 * чем указанное значение в MAX_POST_REQUEST_DATA_SIZE
 */
define('MAX_FILE_SIZE', 	"50M");

/** Максимальный размер данных, отправляемый POST запросом. */
define('MAX_POST_REQUEST_DATA_SIZE', 	"50M");

/** Таймзона */
define('TIMEZONE', 'Europe/Kiev');


//////////////////////////////////////////////////////////////////////////////
// Выставляем серверные настройки.

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


//////////////////////////////////////////////////////////////////////////////
// Получем инормацию о приложении (статус установки и его ключ).

function installed()
{
	$status = false;
	$app_key = '';

	if (file_exists(MCR_ROOT . 'src/mcr/.installed')) {
		$app_key = file_get_contents(MCR_ROOT . 'src/mcr/.installed');

		if (strlen($app_key) >= 128) {
			$status = true;
		}
	}

	return (object) compact('status', 'app_key');
}

