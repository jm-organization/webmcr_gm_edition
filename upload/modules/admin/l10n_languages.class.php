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
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('languages') => ADMIN_URL."&do=news"
		);
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/l10n/languages/header.html");
	}

	public function languages_list($languages) {
		ob_start();

		while ($language = $this->core->db->fetch_assoc($languages)) {
			$title = json_decode($language['settings'])->title;

			$data = array(
				"ID" => $language['id'],
				"TITLE" => $title,
				"LOCALE" => $language['language']
			);

			echo $this->core->sp(MCR_THEME_MOD."admin/l10n/languages/language.html", $data);
		}

		return ob_get_clean();
	}
	
	public function all_languages() {
		$languages = $this->l10n->get_languages();
		$languages_list = (isset($languages))?$this->languages_list($languages):'';

		$data = array(
			"LANGUAGES_LIST" => $languages_list,
		);

		return $this->core->sp(MCR_THEME_MOD."admin/l10n/languages/languages.html", $data);
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
			default: $content = $this->all_languages(); break;
		}

		return $content;
	}
}