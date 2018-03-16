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
		if (!$this->user->is_auth) {
			$this->core->js_notify($this->l10n->gettext('not_auth_error'));
		}

		$id = intval(@$_POST['id']);
		$nid = intval(@$_POST['nid']);

		$query = $this->db->query(
			"SELECT 
				`c`.text_bb, `c`.`data`, 
				
				`u`.`login`
			FROM `mcr_news_comments` AS `c`
			
			LEFT JOIN `mcr_users` AS `u`
				ON `c`.uid=`u`.`id`
				
			WHERE `c`.nid='$nid' AND `c`.id='$id'"
		);

		if (!$query) {
			$this->core->js_notify($this->l10n->gettext('error_sql_critical'));
		}
		if ($this->db->num_rows($query) <= 0) {
			$this->core->js_notify($this->l10n->gettext('error_hack'));
		}

		$ar = $this->db->fetch_assoc($query);
		$data = json_decode($ar['data'], true);

		$this->core->js_notify($this->l10n->gettext('com_load_success'), $this->l10n->gettext('error_success'), true, [
			'create' => date("d.m.Y - H:i:s", $data['time_create']),
			'login' => $this->db->HSC($ar['login']),
			'text' => $this->db->HSC($ar['text_bb']),
		]);
	}
}