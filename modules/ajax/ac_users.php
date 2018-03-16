<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $db, $user, $cfg, $l10n;

	public function __construct(core$core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->user = $core->user;
		$this->cfg = $core->cfg;
		$this->l10n = $core->l10n;

		if (!$this->user->is_auth || !$this->core->is_access('sys_share')) {
			$this->core->js_notify($this->l10n->gettext('error_403'));
		}
	}

	public function content()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->js_notify($this->l10n->gettext('error_method'));
		}

		$login = $this->db->safesql(urldecode(@$_POST['query']));
		$query = $this->db->query("SELECT `login` FROM `mcr_users` WHERE `login` LIKE '%$login%' ");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->js_notify($this->l10n->gettext('ok'));
		}

		$array = [];

		while ($ar = $this->db->fetch_assoc($query)) {
			$array[] = $this->db->HSC($ar['login']);
		}

		$this->core->js_notify($this->l10n->gettext('ok'), $this->l10n->gettext('ok'), true, $array);
	}
}