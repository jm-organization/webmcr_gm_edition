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
		$this->db = $core->db;
		$this->l10n = $core->l10n;

		if(!$this->core->is_access('sys_adm_l10n')){ $this->core->notify('403'); }

		$bc = array(
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('phrases') => ADMIN_URL."&do=news"
		);
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/header.html");
	}

	public function phrases_list($phrases) {
		ob_start();

		while ($phrase = $this->core->db->fetch_assoc($phrases)) {
			$language_title = json_decode($phrase['language_settings'])->title;

			$data = array(
				"ID" => $phrase['id'],
				"PHRASE" => $phrase['phrase_key'],
				"PHRASE_VALUE" => $phrase['phrase_value']
			);

			echo $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/phrase.html", $data);
		}

		return ob_get_clean();
	}

	public function all_phrases() {
		$phrases = $this->l10n->get_phrases();
		$phrases_list = (isset($phrases))?$this->phrases_list($phrases):'';

		$data = array(
			"LANGUAGE_TITLE" => $this->l10n->get_locale()->locale,
                        "PHRASES_LIST" => $phrases_list
		);

		return $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/phrases.html", $data);
	}

	public function add() {

	}

	public function edit() {

	}

	public function delete() {

	}

	public function content() {
		$op = (isset($_GET['op'])) ? $_GET['op'] : 'list';

		switch ($op) {
			case 'add': $content = $this->add(); break;
			case 'edit': $content = $this->edit(); break;
			case 'delete': $content = $this->delete(); break;
			default: $content = $this->all_phrases(); break;
		}

		return $content;
	}
}