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

		$bc = array(
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL
		);

		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_PATH . "modules/admin/header.phtml");
	}

	public function content()
	{
		if (!$this->core->is_access('sys_adm_main')) {
			$this->core->notify(
				$this->l10n->gettext('error_403'),
				sprintf($this->l10n->gettext('error_code'), 403)
			);
		}

		$do = isset($_GET['do']) ? $_GET['do'] : 'dashboard';

		if (!preg_match("/^[\w\.\-]+$/i", $do)) {
			$this->core->notify(
				$this->l10n->gettext('error_403'),
				sprintf($this->l10n->gettext('error_code'), 403)
			);
		}

		if (!file_exists(MCR_MODE_PATH . 'admin/' . $do . '.class.php')) {
			$this->core->notify(
				$this->l10n->gettext('error_404'),
				sprintf($this->l10n->gettext('error_code'), 404)
			);
		}

		require_once(MCR_MODE_PATH . 'admin/' . $do . '.class.php');
		if (!class_exists('submodule')) {
			$this->core->notify(
				$this->l10n->gettext('error_404'),
				sprintf($this->l10n->gettext('error_code'), 404)
			);
		}

		$submodule = new submodule($this->core);

		return $submodule->content();
	}
}