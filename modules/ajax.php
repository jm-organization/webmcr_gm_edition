<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $core, $db, $user, $cfg, $l10n;

	public function __construct(core $core){
		$this->core = $core;
		$this->db = $core->db;
		$this->user = $core->user;
		$this->cfg = $core->cfg;
		$this->l10n = $core->l10n;
	}

	/*private function load_hook($param){
		if (!empty($hook) || !preg_match("/^[\w\|]+$/i", $param)) { return false; }

		$pathlist = explode('|', $hook);

		$hookpath = MCR_MODE_PATH.'ajax/'.implode('/', $pathlist).'.class.php';

		if (!file_exists($hookpath)) { return false; }

		require_once($hookpath);

		if (!class_exists('hook')) { return false; }

		return new hook($this->core);
	}*/

	public function content(){
		$ajax = (isset($_GET['do'])) ? $_GET['do'] : '';
		//$hook = (isset($_GET['hook'])) ? $_GET['hook'] : '';
		$path = str_replace('|', '/', $ajax);

		if(!preg_match("/^[\w\|]+$/i", $ajax) || !file_exists(MCR_MODE_PATH.'ajax/'.$path.'.php')){
			$this->core->js_notify('Hacking Attempt!');
		}

		require_once(MCR_MODE_PATH.'ajax/'.$path.'.php');

		if (!class_exists("submodule")) $this->core->js_notify(sprintf($this->l10n->gettext('class_not_found'), 'submodule'));

		//$this->core->hook = $this->load_hook($hook);

		$submodule = new submodule($this->core);
		
		if (!method_exists($submodule, "content")) $this->core->js_notify(sprintf($this->l10n->gettext('method_not_found'), 'content'));

		return $submodule->content();
	}

}