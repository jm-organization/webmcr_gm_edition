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

		if (!$this->core->is_access('sys_comment_del') && !$this->core->is_access('sys_comment_del_all')) {
			$this->core->js_notify($this->l10n->gettext('com_perm_del'));
		}

		$id = intval(@$_POST['id']);
		$nid = intval(@$_POST['nid']);
		$cond = "id='$id' AND nid='$nid' AND uid='{$this->user->id}'";

		if ($this->core->is_access('sys_comment_del_all')) {
			$cond = "id='$id' AND nid='$nid'";
		}

		if (!$this->db->remove_fast("mcr_news_comments", $cond)) {
			$this->core->js_notify($this->l10n->gettext('error_sql_critical'));
		}

		if ($this->db->affected_rows() <= 0) {
			$this->core->js_notify($this->l10n->gettext('com_del_empty'));
		}

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_com_del')." #$id", $this->user->id);

		$this->core->js_notify($this->l10n->gettext('com_del_success'), $this->l10n->gettext('error_success'), true);
	}
}