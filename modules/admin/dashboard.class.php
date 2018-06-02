<?php


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_PATH."modules/admin/dashboard/header.phtml");
	}

	public function modules()
	{
		// Получаем список модулей с базы
		$query = $this->db->query(
			"SELECT
 				`m`.id, `m`.title, 
 				`m`.`text`, `m`.`url`, `m`.`target`, 
 				`m`.`access`, 
 				
 				`i`.img
			FROM `mcr_menu_adm` AS `m`
			
			LEFT JOIN `mcr_menu_adm_icons` AS `i`
				ON `i`.id=`m`.icon
				
			ORDER BY `priority` ASC"
		);
		$results = [];

		if ($query && $this->db->num_rows($query) > 0) {
			while ($ar = $this->db->fetch_assoc($query)) {
				$results[] = $ar;
			}

			return $this->core->sp(MCR_THEME_MOD."admin/dashboard/modules/modules-list.phtml", $results);
		}

		return null;
	}

	public function content()
	{
		$content = [];

		# Добавление админ-блоков в админ-панель
		$content['MODULES'] = $this->modules();
//		$content['USERS'] = '<div class="col-md-4"></div>';

		return $this->core->sp(MCR_THEME_MOD."admin/dashboard/dashboard.phtml", $content);
	}
}