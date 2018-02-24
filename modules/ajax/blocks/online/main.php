<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $user, $db, $cfg;

	public function __construct($core)
	{
		$this->core = $core;
		$this->user = $core->user;
		$this->db = $core->db;
		$this->cfg = $core->cfg;

		include_once MCR_CONF_PATH.'blocks/online.php';
		$this->core->cfg_b = $cfg;
	}

	public function content()
	{
		$result = [
			'guests' => 0,
			'users' => 0,
			'all' => 0,
			'list' => [],
		];

		if (!$this->core->is_access(@$this->core->cfg_b['PERMISSIONS'])) {
			$this->core->js_notify('', '', true, $result);
		}

		$time = time();

		$expire = $time - $this->core->cfg_b['TIMEOUT'];

		$query = $this->db->query(
			"SELECT 
				`o`.online, 
				
				`u`.`login`, `u_id`.`login` AS `uidlogin`,
				
				`g`.`color` AS `gcolor`, `g_id`.`color` AS `guidcolor`
			FROM `mcr_online` AS `o`
			
			LEFT JOIN `mcr_users` AS `u`
				ON `u`.`ip_last`=`o`.`ip`
				
			LEFT JOIN `mcr_users` AS `u_id`
				ON `u_id`.`id`=`o`.`ip`
				
			LEFT JOIN `mcr_groups` AS `g`
				ON `g`.`id`=`u`.`gid`
				
			LEFT JOIN `mcr_groups` AS `g_id`
				ON `g_id`.`id`=`u_id`.`gid`
				
			WHERE `o`.`date_update`>='$expire'
			
			GROUP BY `o`.id, `u`.`login`"
		);

		if (!$query) {
			$this->core->js_notify($this->core->l10n->gettext('error_sql_critical').' '.$this->db->error());
		}

		if ($this->db->num_rows($query) <= 0) {
			$this->core->js_notify($this->core->l10n->gettext('error_success'), $this->core->l10n->gettext('error_success'), true, $result);
		}

		while ($ar = $this->db->fetch_assoc($query)) {
			$result['all']++;
			if (intval($ar['online']) == 0) {
				$result['guests']++;
				continue;
			}

			$result['users']++;

			$color = $this->db->HSC($ar['gcolor']);
			$color = (!empty($ar['guidcolor']))
				? $this->db->HSC($ar['guidcolor'])
				: $color;

			$login = (!is_null($ar['uidlogin']))
				? $this->db->HSC($ar['uidlogin'])
				: $this->db->HSC($ar['login']);

			$result['list'][] = $this->core->colorize($login, $color);
		}

		if (empty($result['list'])) {
			$result['list'] = 'Нет зарегистрированных пользователей';
		}

		$this->core->js_notify($this->core->l10n->gettext('error_success'), $this->core->l10n->gettext('error_success'), true, $result);
	}
}