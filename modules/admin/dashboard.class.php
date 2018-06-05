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

	private function modules()
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

	private function user_groups()
	{
		$results = [ 'xKeys' => [], 'yKeys' => [], 'colors' => [] ];

		$query = $this->db->query("SELECT  `g`.`id`, `g`.`title` FROM `mcr_groups` AS `g` LEFT JOIN `mcr_users` AS `u` ON `u`.`gid`=`g`.`id` GROUP BY `g`.`id`");

		if ($query && $this->db->num_rows($query) > 0) {
			$groups = [];
			while ($ar = $this->db->fetch_assoc($query)) { $groups[] = $ar; }

			$grouped_groups = array_group_by($groups, 'title');

			$grouped_groups = array_map(function($users) {
				return count($users);
			}, $grouped_groups);

			//var_dump($grouped_groups);
			$results['xKeys'] = array_keys($grouped_groups);
			$results['yKeys'] = array_values($grouped_groups);
			$results['colors'] = ['#dc3545','#ffc107','#28a745','#17a2b8','#6c757d'];
		}

		return $results;
	}

	private function users_on_datereg()
	{
		$results = [ 'xKeys' => [], 'yKeys' => [] ];

		$now = new DateTime();
		$three_mouth_back = $now->modify('-3 months')->format('Y-m-d H:i:s.u');

		/**/
		// Выбираем зарегистрированных пользователей за период: "последние три месяца".
		// TODO: сделать настройку периодов (?)
		$query = $this->db->query("SELECT `u`.`id`, `u`.`time_create` FROM `mcr_users` AS `u` WHERE `u`.`time_create` >= '$three_mouth_back' AND `u`.`time_create` <= NOW()");

		if ($query && $this->db->num_rows($query) > 0) {
			$users = $grouped_users = [];

			//  Извлекаем всех пользователей в один общий масив.
			while ($ar = $this->db->fetch_assoc($query)) { $users[] = $ar; }

			$grouped_users = array_group_by($users, function($user) {
				return $this->l10n->localize(strtotime($user['time_create']), 'unixtime', '%d/%m/%y');
			});

			$grouped_users = array_map(function($users) {
				return count($users);
			}, $grouped_users);

			//var_dump($grouped_users);
			$results['xKeys'] = array_keys($grouped_users);
			$results['yKeys'] = array_values($grouped_users);
		}

//		var_dump($results);

		return $results;
	}

	private function users_count()
	{
		$query = $this->db->query("SELECT COUNT(`id`) as `count` FROM `mcr_users`");

		if ($query && $this->db->num_rows($query) > 0) {
			return $this->db->fetch_assoc($query)['count'];
		}

		return 0;
	}

	private function users()
	{
		$results = [];

		$results['date-regs'] = $this->users_on_datereg();
		$results['groups'] =  $this->user_groups();
		$results['count'] =  $this->users_count();

		return $this->core->sp(MCR_THEME_MOD."admin/dashboard/modules/users-statistic.phtml", $results);
	}

	private function themes($select='')
	{
		$scan = scandir(MCR_ROOT.'themes/');
		$results = [];

		foreach ($scan as $key => $value) {
			if($value=='.' || $value=='..' || !is_dir(MCR_ROOT.'themes/'.$value)) continue;
			if(!file_exists(MCR_ROOT.'themes/'.$value.'/theme.php')) continue;

			require(MCR_ROOT.'themes/'.$value.'/theme.php');
			$theme['img'] = '/themes/'.$value.'/about-bg.png';
			$theme['active'] = $this->cfg->main['s_theme'] == $value;

			$results[] = $theme;
		}

		return $this->core->sp(MCR_THEME_MOD."admin/dashboard/modules/themes-list.phtml", $results);
	}

	public function content()
	{
		$content = [];

		# Добавление админ-блоков в админ-панель
		$content['MODULES'] = $this->modules();
		$content['THEMES'] = $this->themes();

		$content['USERS'] = $this->users();

		return $this->core->sp(MCR_THEME_MOD."admin/dashboard/dashboard.phtml", $content);
	}
}