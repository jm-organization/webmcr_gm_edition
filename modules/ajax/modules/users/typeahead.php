<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $db, $cfg, $user;

	public function __construct($core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
	}

	public function content()
	{

		$login = $this->db->safesql(@$_GET['query']); // only latin1

		$ctables = $this->core->cfg->db['tables'];
		$us_f = $ctables['users']['fields'];

		$query = $this->db->query(
			"SELECT `login` FROM `mcr_users` WHERE `login` LIKE '%$login%'
			ORDER BY `login` ASC
			LIMIT 10"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->js_notify('Empty', 'Empty');
		}

		$data = [];

		while ($ar = $this->db->fetch_assoc($query)) {
			$data[] = $this->db->HSC($ar[$us_f['login']]);
		}

		$this->core->js_notify('success', 'success', true, $data);
	}
}

?>