<?php

/*if ($core->cfg->main['debug'] && @$core->user->permissions->sys_debug && $mode != 'admin') {
	$data_debug = array(
		"PLT" => number_format(microtime(true) - DEBUG_PLT, 3),
		"QUERIES" => $core->db->count_queries,
		"MEMORY_USAGE" => intval(memory_get_usage() / 1024),
		"MEMORY_PEAK" => intval(memory_get_peak_usage() / 1024),
		"BASE_ERROR" => $core->db->error(),
		"PHP_ERROR" => error_get_last()
	);

	echo $core->sp(MCR_THEME_PATH . "debug.phtml", $data_debug);
}*/
