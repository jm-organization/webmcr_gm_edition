<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct(core $core){
		$this->core	= $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		$bc = array(
			$this->l10n->gettext('module_close-site') => BASE_URL."?mode=close"
		);
		$this->core->bc = $this->core->gen_bc($bc);
	}

	public function content() {
		if (!$this->cfg->func['close']) $this->core->notify();

		$format = $this->l10n->get_date_format().' '.$this->l10n->gettext('in').' '.$this->l10n->get_time_format();
		$time = $this->l10n->localize($this->cfg->func['close_time'], 'unixtime', $format);

		$for_time =  ($this->cfg->func['close_time']<=0)
			?$this->l10n->gettext('close_time_for1')
			:$this->l10n->gettext('close_time_for2').' '.$time;

		$data = array(
			'FOR_TIME' => $for_time,
		);

		echo $this->core->sp(MCR_THEME_MOD."close/main.phtml", $data);

		exit;
	}
}