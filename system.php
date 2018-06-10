<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

ini_set("upload_max_filesize", "50M");
ini_set("post_max_size", "50M");
@date_default_timezone_set('Europe/Kiev');

// System constants
define('PROGNAME', 'MagicMCR'. MCR);
define('VERSION', 'webmcr_gm_edition_beta_1.13');
define('FEEDBACK', PROGNAME.' powered by <a href="http://webmcr.com" target="_blank">WebMCR</a> &copy; 2017-'.date("Y").' <a href="http://www.jm-org.net/about/#Magicmen">Magicmen</a>');
define('MCR_ROOT', dirname(__FILE__).'/');
define('MCR_MODE_PATH', MCR_ROOT.'modules/');
define('MCR_TOOL_PATH', MCR_ROOT.'engine/');
define('MCR_LIBS_PATH', MCR_TOOL_PATH.'libs/');
define('MCR_MON_PATH', MCR_TOOL_PATH.'monitoring/');
define('MCR_SIDE_PATH', MCR_ROOT.'blocks/');
define('MCR_LANG_PATH', MCR_ROOT.'language/');
define('MCR_CONF_PATH', MCR_ROOT.'configs/');
define('MCR_UPL_PATH', MCR_ROOT.'uploads/');
define('MCR_CACHE_PATH', MCR_ROOT.'data/cache/');

error_reporting(0);

session_save_path(MCR_UPL_PATH.'tmp');
if(!session_start()){ session_start(); }

// Set default charset
header('Content-Type: text/html; charset=UTF-8');

// Load core
require_once(MCR_TOOL_PATH.'core.class.php');

// Create new core object
$core = new core();

// Debug
ini_set("display_errors", $core->cfg->main['debug']);
//$warn_type = ($core->cfg->main['debug']) ? E_ALL : 0;

$meta_json_data = array(
	'secure' => MCR_SECURE_KEY,
	'lang' => MCR_LANG,
	'base_url' => BASE_URL,
	'theme_url' => STYLE_URL,
	'upload_url' => UPLOAD_URL,
	'server_time' => time(),
	'is_auth' => $core->user->is_auth,
);

define('META_JSON_DATA', json_encode($meta_json_data));

// Csrf security validation
$core->csrf_check();
