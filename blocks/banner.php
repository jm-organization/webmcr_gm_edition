<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class block_banner{
	private $core;

	public function __construct(core $core){
		$this->core = $core;
	}

	public function content(){

		if(!$this->core->is_access(@$this->core->cfg_b['PERMISSIONS'])){ return null; }

		$this->core->header .= $this->core->sp(MCR_THEME_PATH."blocks/banner/header.phtml");

		return $this->core->sp(MCR_THEME_PATH."blocks/banner/main.phtml");
	}
}