<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $db, $user, $cfg, $l10n;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->user = $core->user;
		$this->cfg = $core->cfg;
		$this->l10n = $core->l10n;

		if (!$this->core->is_access('sys_monitoring')) {
			$this->core->js_notify($this->l10n->gettext('error_403'));
		}
	}

	public function content()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->core->js_notify($this->l10n->gettext('error_method'));
		}

		$time = time();

		$query = $this->db->query("SELECT id, ip, `port`, last_update, updater, `type` FROM `mcr_monitoring`");
		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->js_notify($this->l10n->gettext('mon_empty'), '', true);
		}

		$array = $data = [];

		while ($r = $this->db->fetch_assoc($query)) {
			$last = intval($r['last_update']);
			$next = $last + intval($r['updater']);

			if ($next > $time) {
				continue;
			}

			$array[] = [
				'id' => intval($r['id']),
				'ip' => $r['ip'],
				'port' => intval($r['port']),
				'type' => $r['type'],
			];
		}

		if (empty($array)) {
			$this->core->js_notify($this->l10n->gettext('mon_empty'), '', true);
		}

		require_once(MCR_TOOL_PATH . 'monitoring.class.php');

		$m = new monitoring();

		foreach ($array as $key => $ar) {
			if (isset($m->loaded[$ar['type']])) {
				$mon = $m->loaded[$ar['type']];
			} else {
				$mon = $m->loading($ar['type']);
			}

			$mon->connect($ar['ip'], $ar['port']);

			$id = intval($ar['id']);

			$version = $this->db->safesql($mon->version);
			$players = $this->db->safesql($mon->players);
			$motd = $this->db->safesql($mon->motd);
			$plugins = $this->db->safesql($mon->plugins);
			$map = $this->db->safesql($mon->map);
			$error = $this->db->safesql($mon->error);
			$online = intval($mon->online);
			$status = intval($mon->status);
			$slots = intval($mon->slots);

			if (!$this->db->query(
				"UPDATE `mcr_monitoring`
				SET `status`='$status', 
					`version`='$version', 
					online='$online',
					slots='$slots', 
					players='$players', 
					motd='$motd', 
					map='$map',
					plugins='$plugins', 
					last_error='$error', 
					last_update='$time'
				WHERE id='$id'"
			)
			) continue;

			$data[] = [
				'id' => $id,
				'online' => $mon->online,
				'slots' => $mon->slots,
				'progress' => ($mon->online <= 0)
					? 0
					: ceil(100 / ($mon->slots / $mon->online)),
				'status' => $mon->status
			];

			if ($mon->status != 1) {
				$data['progress'] = 100;
			}
		}

		$this->core->js_notify($this->l10n->gettext('mn_success'), $this->l10n->gettext('error_success'), true, $data);
	}
}