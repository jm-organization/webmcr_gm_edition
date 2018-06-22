<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class block_monitor
{
	private $core, $db, $user, $l10n;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->user = $core->user;
		$this->l10n = $core->l10n;
	}

	public function content()
	{
		if (!$this->core->is_access(@$this->core->cfg_b['PERMISSIONS'])) {
			return null;
		}

		$this->core->header .= $this->core->sp(MCR_THEME_PATH . "blocks/monitor/header.phtml");

		$data = [
			'CONTENT' => $this->server_array(),
		];

		return $this->core->sp(MCR_THEME_PATH . "blocks/monitor/main.phtml", $data);
	}

	private function server_array()
	{
		$query = $this->db->query("SELECT id, title, `text`, ip, `port`, `status`, online, slots, players FROM `mcr_monitoring`");

		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->l10n->gettext('mn_empty');
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$status = (intval($ar['status']) === 1) ? 'progress-info' : 'progress-danger';
			$slots = intval($ar['slots']);
			$online = intval($ar['online']);

			$progress = ($online <= 0) ? 0 : ceil(100 / ($slots / $online));

			if (intval($ar['status']) != 1) {
				$progress = 100;
			}

			$data = [
				'ID' => intval($ar['id']),
				'TITLE' => $this->db->HSC($ar['title']),
				'TEXT' => $this->db->HSC($ar['text']),
				'IP' => $this->db->HSC($ar['ip']),
				'PORT' => intval($ar['port']),
				'PROGRESS' => $progress,
				'STATUS' => $status,
				'STATS' => (intval($ar['status']) === 1) ? $online . ' / ' . $slots : $this->l10n->gettext('offline'),
			];

			echo $this->core->sp(MCR_THEME_PATH . "blocks/monitor/monitor-id.phtml", $data);
		}

		return ob_get_clean();
	}
}

?>