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
			$this->l10n->gettext('module_statics') => BASE_URL."?mode=statics"
		];
		$this->core->bc = $this->core->gen_bc($bc);
	}

	public function content()
	{
		if (!isset($_GET['id']) || empty($_GET['id'])) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'));
		}

		$uniq = $this->db->safesql(@$_GET['id']);

		$ctables = $this->cfg->db['tables'];
		$us_f = $ctables['users']['fields'];

		$query = $this->db->query(
			"SELECT 
				`s`.`title`, 
				`s`.`text_html`, 
				`s`.`uid`, 
				`s`.`permissions`, 
				`s`.`data`,
				
				`u`.`login`
			FROM `mcr_statics` AS `s`
			
			LEFT JOIN `mcr_users` AS `u`
				ON `u`.`id`=`s`.`uid`
				
			WHERE `s`.`uniq`='$uniq'"
		);
		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'));
		}

		$ar = $this->db->fetch_assoc($query);

		if (!$this->core->is_access($ar['permissions'])) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'));
		}

		$uniq = $this->db->HSC($uniq);
		$title = $this->db->HSC($ar['title']);

		$bc = [
			$this->l10n->gettext('module_statics') => BASE_URL."?mode=statics&id=$uniq",
			$title => BASE_URL."?mode=statics&id=$uniq"
		];

		$this->core->bc = $this->core->gen_bc($bc);

		$page_data = [
			"TITLE" => $this->db->HSC($ar['title']),
			"TEXT" => $ar['text_html'],
			"UID" => intval($ar['uid']),
			"LOGIN" => $this->db->HSC($ar[$us_f['login']]),
			"DATA" => json_decode($ar['data'], true),

		];

		return $this->core->sp(MCR_THEME_MOD."statics/static-id.phtml", $page_data);
	}
}