<?php


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

require_once MCR_LIBS_PATH.'array_group_by.php';

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

	public function user_groups()
	{
		$query = $this->db->query(
			"SELECT 
				`g`.`id`, `g`.`title`, COUNT(`u`.`id`) AS `count`
			FROM `mcr_groups` AS `g`
			
			LEFT JOIN `mcr_users` AS `u`
				ON `u`.`gid`=`g`.`id`
				
			GROUP BY `g`.`id`"
		);
		$results = [];

		if ($query && $this->db->num_rows($query) > 0) {
			while ($ar = $this->db->fetch_assoc($query)) {
				switch (intval($ar['id'])) {
					case 0:
						$color = '#dc3545';
						break;
					case 1:
						$color = '#ffc107';
						break;
					case 2:
						$color = '#28a745';
						break;
					case 3:
						$color = '#17a2b8';
						break;

					default:
						$color = '#6c757d';
						break;
				}

				$data = [
					"COLOR" => $color,
					"TITLE" => $this->db->HSC($ar['title']),
					"COUNT" => intval($ar['count'])
				];

				$results[] = $data;
			}
		}

		return $results;
	}

	public function users_on_datereg()
	{
		$results = [];

		$now = new DateTime();
		$three_mouth_back = $now->modify('-3 months')->format('Y-m-d H:i:s.u');

		/**/

		$query = $this->db->query(
			"SELECT
			  COUNT(`u`.`id`) as `count`,
			  DATE_FORMAT(`u`.`time_create`, '%d %m %Y'), `u`.`time_create` as `date`
			FROM `mcr_users` AS `u`
			
			WHERE `u`.`time_create` >= '$three_mouth_back' AND `u`.`time_create` <= NOW()
			
			GROUP BY DATE_FORMAT(`u`.`time_create`, '%d %m %Y')"
		);

		if ($query && $this->db->num_rows($query) > 0) {
			while ($ar = $this->db->fetch_assoc($query)) {
				$results[] = [
					'user-date-reg' => $ar['count'],
					'date' => $ar['date']
				];
			}
		}

//		var_dump($results);

		return $results;
	}

	public function users_count()
	{
		$query = $this->db->query("SELECT COUNT(`id`) as `count` FROM `mcr_users`");

		if ($query && $this->db->num_rows($query) > 0) {
			return $this->db->fetch_assoc($query)['count'];
		}

		return 0;
	}

	public function users()
	{
		$results = [];

		$results['date-regs'] = $this->users_on_datereg();
		$results['groups'] =  $this->user_groups();
		$results['count'] =  $this->users_count();

		return $this->core->sp(MCR_THEME_MOD."admin/dashboard/modules/users-statistic.phtml", $results);
	}

	public function content()
	{
		$content = [];

		# Добавление админ-блоков в админ-панель
		$content['MODULES'] = $this->modules();
		$content['USERS'] = $this->users();

		return $this->core->sp(MCR_THEME_MOD."admin/dashboard/dashboard.phtml", $content);
	}
}