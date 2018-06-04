<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $core, $db, $user, $cfg, $l10n;

	public function __construct(core $core){
		$this->core = $core;
		$this->db = $core->db;
		$this->user	= $core->user;
		$this->cfg = $core->cfg;
		$this->l10n = $core->l10n;
	}

	public function content() {
		if ($_SERVER['REQUEST_METHOD']!='POST') { $this->core->notify('Hacking Attempt!'); }
		
		if ($this->user->is_auth) { $this->core->notify('', $this->l10n->gettext('auth_already'), 1); }

		$login = $this->db->safesql($_POST['login']);
		$remember = (isset($_POST['remember']) && intval($_POST['remember'])==1) ? true : false;

		$query = $this->db->query(
			"SELECT 
				`u`.`id`, `u`.`password`, `u`.`salt`,
				
				`g`.`permissions`
			FROM `mcr_users` AS `u`
			
			INNER JOIN `mcr_groups` AS `g`
				ON `g`.`id`=`u`.`gid`
				
			WHERE `u`.`login`='$login' OR `u`.`email`='$login'
			
			LIMIT 1"
		);
		if(!$query || $this->db->num_rows($query)<=0){
			$this->core->notify(
				$this->l10n->gettext('error_message'),
				$this->l10n->gettext('wrong_pass')
			);
		}

		$ar = $this->db->fetch_assoc($query);

		$uid = intval($ar['id']);
		$permissions = json_decode($ar['permissions'], true);
		$password = $this->user->auth->createHash(@$_POST['password'], $ar['salt']);

		if (!$this->user->auth->authentificate(
			@$_POST['password'],
			$ar['password'],
			$ar['salt']
		)) { $this->core->notify(
			$this->l10n->gettext('error_message'),
			$this->l10n->gettext('wrong_pass')
		); }

		$new_tmp = $this->db->safesql($this->user->auth->createTmp());

		$new_ip = $this->user->ip;
		$password = $this->db->safesql($password);

		if (!$this->db->query("
			UPDATE `mcr_users`
			SET 
				`tmp`='$new_tmp', 
				`ip_last`='$new_ip', 
				`time_last`=NOW()
			WHERE `id`='$uid'
			LIMIT 1
		")) { $this->core->notify(
			$this->l10n->gettext('error_attention'),
			$this->l10n->gettext('error_sql_critical')
		); }

		if (!@$permissions['sys_auth']) { $this->core->notify(
			$this->l10n->gettext('error_403'),
			$this->l10n->gettext('auth_access'),
			2,
			'?mode=403'
		); }

		$new_hash = $uid.$new_tmp.$new_ip.md5($this->cfg->main['mcr_secury']);

		$new_hash = $uid.'_'.md5($new_hash);

		$safetime = ($remember) ? time()+3600*24*30 : time()+3600;

		setcookie("mcr_user", $new_hash, $safetime, '/');

		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_auth'), $this->user->id);

		$this->core->notify(
			$this->l10n->gettext('auth_success'),
			$this->l10n->gettext('error_success'), // TODO edit this shit
			3
		);
	}

}