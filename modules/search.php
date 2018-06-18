<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class module
{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		$bc = [
			$this->l10n->gettext('module_search') => BASE_URL."?mode=search"
		];
		$this->core->bc = $this->core->gen_bc($bc);
	}

	public function content()
	{

		if (!$this->core->is_access('sys_search')) {
			$this->core->notify($this->l10n->gettext('error_403'), $this->l10n->gettext('search_permission_error'), 1, "?mode=403");
		}

		if (!isset($_GET['type']) || !file_exists(MCR_MODE_PATH.'search/'.$_GET['type'].'.php')) {
			$this->core->notify();
		}

		require_once(MCR_MODE_PATH.'search/'.$_GET['type'].'.php');

		$submodule = new submodule($this->core);

		$data['CONTENT'] = $submodule->results();

		return $this->core->sp(MCR_THEME_MOD."search/main.phtml", $data);
	}
}