<?php

/**
 * @Created in JM Organization.
 * @Author: Magicmen
 *
 * @Date: 16.10.2017
 * @Time: 12:45
 *
 * @documentation:
 */
class submodule {
	private $core, $db, $l10n;

	public function __construct($core) {
		$this->core = $core;
		$this->db	= $core->db;
		$this->l10n = $core->l10n;

		if(!$this->core->is_access('sys_adm_l10n')){ $this->core->notify('403'); }

		$bc = array(
			$this->l10n->translate('mod_name') => ADMIN_URL,
			$this->l10n->translate('news') => ADMIN_URL."&do=news"
		);
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/header.html");
	}

	public function add() {

	}

	public function content() {
		$op = (isset($_GET['op'])) ? $_GET['op'] : 'list';

		switch ($op) {
			case 'add': $content = $this->add(); break;
			default: $content = ''; break;
		}

		return $content;
	}
}