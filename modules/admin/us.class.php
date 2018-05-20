<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $db, $cfg, $user, $l10n, $cfg_m;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		require_once(MCR_CONF_PATH.'modules/users.php');
		$this->cfg_m = $cfg;

		if (!$this->core->is_access('mod_users_adm_settings')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => BASE_URL."?mode=admin",
			$this->l10n->gettext('module_users') => BASE_URL."?mode=admin&do=us"
		];

		$this->core->bc = $this->core->gen_bc($bc);
	}

	public function content()
	{
		$cfg = $this->cfg_m;

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {

			$cfg['enable_comments'] = (intval(@$_POST['use_comments']) === 1) ? true : false;
			$cfg['users_on_page'] = (intval(@$_POST['users_on_page']) < 1) ? 5 : intval(@$_POST['users_on_page']);
			$cfg['comments_on_page'] = (intval(@$_POST['comments_on_page']) < 1) ? 3 : intval(@$_POST['comments_on_page']);

			if (!$this->cfg->savecfg($cfg, 'modules/users.php', 'cfg')) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('set_e_cfg_save'), 2, '?mode=admin&do=us');
			}

			// Последнее обновление пользователя
			$this->db->update_user($this->user);

			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_set_main_save'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('set_save_success'), 3, '?mode=admin&do=us');
		}

		$data = [
			"CFG" => $cfg,
			"USE_COMMENTS" => ($cfg['enable_comments']) ? 'selected' : '',
		];

		return $this->core->sp(MCR_THEME_MOD."admin/us/main.html", $data);
	}
}