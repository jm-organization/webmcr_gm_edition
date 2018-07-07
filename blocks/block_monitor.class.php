<?php
namespace blocks;

use mcr\database\db;
use mcr\html\blocks\base_block;
use mcr\html\blocks\standard_block;

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class block_monitor implements base_block
{
	use standard_block;

	/**
	 * Сервера
	 *
	 * @var array|null
	 */
	public $data = null;

	public function __construct() { }

	/**
	 * Инициализатор блока.
	 * Принимает конфиги блока.
	 *
	 * @param array $configs - конфиги блока, которые необходимы для его работы.
	 *
	 * @return base_block
	 * @throws \mcr\database\db_exception
	 */
	public function init(array $configs)
	{
		/*if (!$this->core->is_access(@$this->core->cfg_b['PERMISSIONS'])) {
			return null;
		}*/

		$this->data = [
			'servers' => $this->server_array()
		];

		return $this;
	}

	/**
	 * @return string
	 * @throws \mcr\database\db_exception
	 */
	private function server_array()
	{
		$query = db::query("SELECT id, title, `text`, ip, `port`, `status`, online, slots, players FROM `mcr_monitoring`");

		if ($query->result() && $query->num_rows > 0) {
			$servers = $query->fetch_all(MYSQLI_ASSOC);
			// сервера, которые будут выведены
			$_servers = '';

			foreach ($servers as $server) {
				$status = (intval($server['status']) === 1) ? 'progress-info' : 'progress-danger';
				$slots = intval($server['slots']);
				$online = intval($server['online']);

				$progress = ($online <= 0) ? 0 : ceil(100 / ($slots / $online));

				if (intval($server['status']) != 1) {
					$progress = 100;
				}

				$data = [
					'id' => intval($server['id']),
					'title' => htmlspecialchars($server['title']),
					'text' => htmlspecialchars($server['text']),
					'ip' => htmlspecialchars($server['ip']),
					'port' => intval($server['port']),
					'progress' => $progress,
					'status' => $status,
					'stats' => (intval($server['status']) === 1) ? $online . ' / ' . $slots : translate('offline'),
				];

				$_servers .= tmpl('blocks.block_monitor.monitor-id', $data);
			}

			return $_servers;
		}

		return translate('mn_empty');
	}
}