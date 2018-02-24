<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class module
{
	private $core, $db, $user, $l10n, $cfg;

	public function __construct($core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->user = $core->user;
		$this->cfg = $core->cfg;
		$this->l10n = $core->l10n;
	}

	public function content()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->notify('Hacking Attempt!');
		}

		if (!$this->user->is_auth) {
			$this->core->notify($this->l10n->gettext('error_403'), $this->l10n->gettext('not_auth_error'), 1, '?mode=403');
		}

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_logout'), $this->user->id);

		$new_tmp = $this->db->safesql($this->core->random(16));
		$date		= time();
    
    if (!$this->db->query("
			UPDATE `mcr_users` 
			SET `tmp`='$new_tmp', `time_last`=$date
			WHERE `id`='{$this->user->id}'
			LIMIT 1
		")) {
			$this->core->notify($this->l10n->gettext('error_attention'), $this->l10n->gettext('error_sql_critical'));
		}

		setcookie("mcr_user", "", time() - 3600, '/');

		$this->core->notify('', '', 1);
	}
}