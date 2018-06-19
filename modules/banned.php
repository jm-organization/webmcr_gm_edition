<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct(core $core){
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		$bc = array(
			$this->l10n->gettext('module_ban-manager') => BASE_URL."?mode=banned"
		);
		$this->core->bc = $this->core->gen_bc($bc);
	}

	public function content() {
		// TODO: Ban-Manager
		/*$time = time();

		if($this->user->is_banned===false){ $this->core->notify(); }

		$expire = date("d.m.Y - H:i:s", $this->user->is_banned);

		$data = array(
			'EXPIRE' => ($this->user->is_banned<=0) ? $this->l10n->gettext('ban_forever') : $this->l10n->gettext('ban_expired').' '.$expire,
		);

		echo $this->core->sp(MCR_THEME_MOD."banned/main.phtml", $data);

		exit;*/
	}
}

?>