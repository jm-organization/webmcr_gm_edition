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
		$query = $this->db->query("
			SELECT `m`.id, `m`.title, 
 				`m`.`text`, `m`.`url`, `m`.`target`, 
 				`m`.`access`, `i`.img
			FROM `mcr_menu_adm` AS `m`
			
			LEFT JOIN `mcr_menu_adm_icons` AS `i`
				ON `i`.id=`m`.icon
			
			WHERE `m`.`fixed`='1'
				
			ORDER BY `priority` ASC
		");

		$items = '';

		if ($query && $this->db->num_rows($query) > 0) {
			while ($ar = $this->db->fetch_assoc($query)) {

				$item_data = array(
					"ID" => intval($ar['id']),
					"TITLE" => $this->db->HSC($ar['title']),
					"TEXT" => $this->db->HSC($ar['text']),
					"URL" => $this->db->HSC($ar['url']),
					"TARGET" => $this->db->HSC($ar['target']),
					"IMG" => $this->db->HSC($ar['img']),
				);

				$items .= $this->core->sp(MCR_THEME_MOD."admin/dashboard/modules/modules-id.phtml", $item_data);
			}

		}

		$data = array("CONTENT" => $items);

		return $this->core->sp(MCR_THEME_MOD."admin/dashboard/modules/modules-list.phtml", $data);
	}

	private function user_groups()
	{
		$results = [ 'xKeys' => [], 'yKeys' => [], 'colors' => [] ];

		$query = $this->db->query("SELECT `u`.`id`, `g`.`title` FROM `mcr_groups` AS `g` LEFT JOIN `mcr_users` AS `u` ON `u`.`gid`=`g`.`id`");

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
			// Извлекаем всех пользователей в один общий масив.
			// На основании этого мосива создаём два масиво - один, который совпадает с тем,
			// что хранится в БД, другой - на основании которого будет построен будущий график.
			$users_ = $users = $query->fetch_all(MYSQLI_ASSOC);

			// Перебираем наш псевдо масив чтобы заполнить пропуски в днях
			foreach ($users_ as $user) {
				$start = new DateTime($user['time_create']);
				$next = next($users_);

				// Если есть следующий день
				if ($next) {
					// берём его дату
					$end = new DateTime($next['time_create']);

					// вычисляем разницу между соседними днями
					$diff = $start->diff($end);

					// если эта разница больше одного дня, дополянем масив,
					// который используется для построения графика недостоющими датами.
					if ($diff->days > 1) {
						for ($d = 0; $d < $diff->days - 1; $d++) {
							array_push($users, [
								'id' => null,
								'time_create' => $start->modify("+1 day")->format('Y-m-d H:i:s.u'),
							]);
						}
					}
				}
			}

			$grouped_users = array_group_by($users, function($user) {
				return $this->l10n->localize(strtotime($user['time_create']), 'unixtime', '%d/%m/%y');
			});

			// сортируем по дате
			ksort($grouped_users);

			$grouped_users = array_map(function($users) {
				$counted = [];

				// если за день не было пользователей (id => null), не учитываем их при подсчёте
				foreach ($users as $user) {
					if (!empty($user['id'])) {
						$counted[] = $user['id'];
					}
				}
				// в итоге получаем пустой масив в тот день, когда не было регистраций

				return count($counted);
			}, $grouped_users);

			// разделяем масив на ключи и значения для графика
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

		$items = '';

		foreach ($scan as $key => $value) {
			if($value=='.' || $value=='..' || !is_dir(MCR_ROOT.'themes/'.$value)) continue;
			if(!file_exists(MCR_ROOT.'themes/'.$value.'/theme.php')) continue;

			require(MCR_ROOT.'themes/'.$value.'/theme.php');
			$theme['img_path'] = '/themes/'.$value.'/';

			$item_data = array(
				"CODE" => $this->db->HSC($theme['ThemeCode']),
				"NAME" => $this->db->HSC($theme['ThemeName']),
				"ABOUT" => $this->db->HSC($theme['About']),
				"ABOUT_FULL" => $this->db->HSC($theme['MoreAbout']),
				"SCREENSHOT" => $this->db->HSC($theme['Screenshots'][0]),
				"ACTIVE" => $this->cfg->main['s_theme'] == $value,
				"VERSION" => $this->db->HSC($theme['Version']),
				"DATE_CREATE" => $this->db->HSC($theme['DateCreate']),
				"DATE_RELEASE" => $this->db->HSC($theme['DateOfRelease']),
			);

			$items .= $this->core->sp(MCR_THEME_MOD."admin/dashboard/modules/themes-id.phtml", $item_data);

		}

		$data = array("CONTENT" => $items);

		return $this->core->sp(MCR_THEME_MOD."admin/dashboard/modules/themes-list.phtml", $data);
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