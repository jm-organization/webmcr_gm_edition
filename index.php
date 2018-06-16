<?php
define("DEBUG_PLT", microtime(true));
define('MCR', '');

if(file_exists('./init-krumo.php')) require_once("./init-krumo.php");
require_once("./system.php");

$core->def_header = $core->sp(MCR_THEME_PATH."header.phtml");

$mode = (isset($_GET['mode']))?$_GET['mode']:$core->cfg->main['s_dpage'];

if(!INSTALLED){ 
    $core->notify(
        $core->l10n->gettext('error_attention'),
        $core->l10n->gettext('error_install'),
        4, 
        'install/'
    );     
}

if ($core->cfg->func['close'] && !$core->is_access('sys_adm_main')) {
	if ($core->cfg->func['close_time']<=0 || $core->cfg->func['close_time'] > time()) {
		$mode = ($mode=='auth')
			? 'auth'
			: 'close';
	}
}

switch ($mode) {
	case 'news':
	case 'search':
	case 'auth':
	case 'register':
	case 'profile':
	case 'file':
	case 'restore':
	case 'ajax':
	case 'statics':
	case 'close':
		$content = $core->load_def_mode($mode);
	break;

	case '403':
		$core->title = $core->l10n->gettext('error_403');
		$content = $core->sp(MCR_THEME_PATH."default_sp/403.html");
	break;

	default:
		$content = $core->load_mode($mode);
	break;
}

$data_global = array(
    "CONTENT" => $content,
    "TITLE" => $core->title,
    "L_BLOCKS" => ($mode != 'admin')
		?$core->load_def_blocks()
		:$core->load_def_blocks(false, 'notify'),
    "HEADER" => $core->header,
    "DEF_HEADER" => $core->def_header,
    "CFG" => $core->cfg->main,
    "ADVICE" => $core->advice(),
    "MENU" => $core->menu->_list(),
    "BREADCRUMBS" => $core->bc,
    "SEARCH" => $core->search()
);

$view = '';

if ($mode == 'admin') {
	$data_global["ADMIN_MENU"] = $core->menu->admin_menu();
	$view = $core->sp(MCR_THEME_PATH."/modules/admin/global.v2.phtml", $data_global);
} else {
	$view = $core->sp(MCR_THEME_PATH."global.phtml", $data_global);
}

// Write global template
echo $view;

if($core->cfg->main['debug'] && @$core->user->permissions->sys_debug && $mode != 'admin'){
	$data_debug = array(
		"PLT" => number_format(microtime(true)-DEBUG_PLT,3),
		"QUERIES" => $core->db->count_queries,
		"MEMORY_USAGE" => intval(memory_get_usage()/1024),
		"MEMORY_PEAK" => intval(memory_get_peak_usage()/1024),
		"BASE_ERROR" => $core->db->error(),
		"PHP_ERROR" => error_get_last()
	);

	echo $core->sp(MCR_THEME_PATH."debug.html", $data_debug);
}
