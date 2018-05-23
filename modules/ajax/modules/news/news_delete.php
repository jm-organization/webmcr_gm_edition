<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class submodule{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct(core $core){
		$this->core		= $core;
		$this->db		= $core->db;
		$this->cfg		= $core->cfg;
		$this->user		= $core->user;
		$this->l10n		= $core->l10n;
	}

	public function content(){

		if($_SERVER['REQUEST_METHOD']!='POST'){ $this->core->js_notify($this->l10n->gettext('error_hack')); }

		if(!$this->core->is_access('sys_adm_news')){ $this->core->js_notify($this->l10n->gettext('error_403')); }

		$id = intval(@$_POST['id']);

		if(!$this->db->remove_fast("mcr_news", "id='$id'")){ $this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=menu'); }

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_new_del')." #$id", $this->user->id);

		$this->core->js_notify($this->l10n->gettext('new_success_del'), $this->l10n->gettext('error_success'), true);
	}

}