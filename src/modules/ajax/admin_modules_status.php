<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;
	}

	public function content()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->js_notify($this->l10n->gettext('error_hack'));
		}

		$act = @$_POST['act'];

		switch ($act) {
			case 'enable':
			case 'disable':
				$this->change_status($act);
				break;

			default:
				$this->core->js_notify($this->l10n->gettext('error_hack'));
				break;
		}

		$this->core->js_notify($this->l10n->gettext('error_hack'));
	}

	private function change_status($act)
	{
		$ids = @$_POST['ids'];
		$status = ($act == 'enable') ? true : false;

		if (empty($ids)) {
			$this->core->js_notify($this->l10n->gettext('ams_mod_not_selected'));
		}

		$ids = explode(',', $ids);

		foreach ($ids as $key => $mod) {
			if (!file_exists(MCR_CONF_PATH . 'modules/' . $mod . '.php')) {
				continue;
			}
			include(MCR_CONF_PATH . 'modules/' . $mod . '.php');

			if (!isset($cfg['MOD_ENABLE'])) {
				continue;
			}

			$cfg['MOD_ENABLE'] = $status;

			if (!$this->cfg->savecfg($cfg, 'modules/' . $mod . '.php', 'cfg')) {
				continue;
			}
		}

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_change_ams'), $this->user->id);

		$this->core->js_notify($this->l10n->gettext('ok'), $this->l10n->gettext('ok'), true);
	}
}