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

		require_once(MCR_CONF_PATH . 'modules/users.php');
		$this->cfg_m = $cfg;
	}

	public function content()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->js_notify($this->l10n->gettext('error_hack'));
		}

		if (!$this->core->is_access('mod_users_comment_del') && !$this->core->is_access('mod_users_comment_del_all')) {
			$this->core->js_notify($this->l10n->gettext('error_403'));
		}

		$id = intval(@$_POST['id']);

		$query = $this->db->query("SELECT uid, `from` FROM `mod_users_comments` WHERE id='$id'");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->js_notify($this->l10n->gettext('com_not_found'));
		}

		$ar = $this->db->fetch_assoc($query);

		if (intval($ar['uid']) != $this->user->id && intval($ar['from']) != $this->user->id && !$this->core->is_access('mod_users_comment_del_all')) {
			$this->core->js_notify($this->l10n->gettext('error_403'));
		}

		$delete = $this->db->query("DELETE FROM `mod_users_comments` WHERE id='$id'");

		if (!$delete) {
			$this->core->js_notify($this->l10n->gettext('error_sql_critical'));
		}

		$this->core->js_notify($this->l10n->gettext('com_del_success'), $this->l10n->gettext('error_success'), true);
	}
}