<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class submodule{
	private $core, $db, $cfg, $user, $lng, $cfg_m;

	public function __construct($core){
		$this->core		= $core;
		$this->db		= $core->db;
		$this->cfg		= $core->cfg;
		$this->user		= $core->user;
	}

	public function content(){

		$login = $this->db->safesql(@$_GET['query']); // only latin1

		$ctables	= $this->core->cfg->db['tables'];
		$us_f		= $ctables['users']['fields'];

		$query = $this->db->query("SELECT `{$us_f['login']}`
									FROM `{$this->cfg->tabname('users')}`
									WHERE `{$us_f['login']}` LIKE '%$login%'
									ORDER BY `{$us_f['login']}` ASC
									LIMIT 10");

		if(!$query || $this->db->num_rows($query)<=0){ $this->core->js_notify('Empty', 'Empty'); }

		$data = array();

		while($ar = $this->db->fetch_assoc($query)){
			$data[] = $this->db->HSC($ar[$us_f['login']]);
		}

		$this->core->js_notify('success', 'success', true, $data);
	}

}

?>