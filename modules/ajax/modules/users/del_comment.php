<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class submodule{
	private $core, $db, $cfg, $user, $lng, $cfg_m;

	public function __construct($core){
		$this->core			= $core;
		$this->db			= $core->db;
		$this->cfg			= $core->cfg;
		$this->user			= $core->user;
		$this->lng			= $core->load_language('users');;
		$this->core->lng_m	= $this->lng;

		require_once(MCR_CONF_PATH.'modules/users.php');

		$this->cfg_m = $cfg;
	}

	public function content(){
		if($_SERVER['REQUEST_METHOD']!='POST'){ $this->core->js_notify($this->core->lng['e_hack']); }

		if(!$this->core->is_access('mod_users_comment_del') && !$this->core->is_access('mod_users_comment_del_all')){
			$this->core->js_notify($this->core->lng['403']);
		}

		$id = intval(@$_POST['id']);

		$query = $this->db->query("SELECT uid, `from` FROM `mod_users_comments` WHERE id='$id'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->core->js_notify($this->lng['com_not_found']); }

		$ar = $this->db->fetch_assoc($query);

		if(intval($ar['uid'])!=$this->user->id && intval($ar['from'])!=$this->user->id && !$this->core->is_access('mod_users_comment_del_all')){
			$this->core->js_notify($this->core->lng['403']);
		}

		$delete = $this->db->query("DELETE FROM `mod_users_comments` WHERE id='$id'");

		if(!$delete){ $this->core->js_notify($this->core->lng['e_sql_critical']); }

		$this->core->js_notify($this->lng['com_del_success'], $this->core->lng['e_success'], true);
	}

}

?>