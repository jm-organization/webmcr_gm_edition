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
			$this->l10n->gettext('module_search') => BASE_URL . "?mode=search",
			$this->l10n->gettext('by_comments') => BASE_URL . "?mode=search&type=comments"
		];
		$this->core->bc = $this->core->gen_bc($bc);
	}

	public function results()
	{
		if (!$this->core->is_access('sys_search_comments')) {
			$this->core->notify($this->l10n->gettext('error_403'), $this->l10n->gettext('access_denied_com'), 1, "?mode=403");
		}

		$value = (isset($_GET['value'])) ? $_GET['value'] : '';
		$value = trim($value);

		if (empty($value)) {
			$this->core->notify($this->l10n->gettext('error_404'), $this->l10n->gettext('empty_query'), 2, "?mode=403");
		}

		$safe_value = $this->db->safesql($value);
		$html_value = $this->db->HSC($value);
		$sql = "SELECT COUNT(*) FROM `mcr_users_comments` WHERE text_bb LIKE '%$safe_value%'";
		$query = $this->db->query($sql);

		if (!$query) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2);
		}

		$ar = $this->db->fetch_array($query);
		$page = "?mode=search&type=comments&value=$html_value&pid=";

		$data = [
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['search_comments'], $page, $ar[0]),
			"RESULT" => $this->results_array($safe_value),
			"QUERY" => $html_value,
			"QUERY_COUNT" => intval($ar[0])
		];

		return $this->core->sp(MCR_THEME_MOD . "search/results.phtml", $data);
	}

	private function results_array($value)
	{
		$start = $this->core->pagination($this->cfg->pagin['search_comments'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['search_comments']; // Set end pagination

		$query = $this->db->query(
			"SELECT 
				`c`.id, 
				`c`.nid, 
				`c`.uid, 
				`c`.text_html, 
				`c`.`data`, 
				`n`.title, 
				`u`.`login`
			FROM `mcr_users_comments` AS `c`
			
			LEFT JOIN `mcr_news` AS `n`
				ON `n`.id=`c`.nid
				
			LEFT JOIN `mcr_users` AS `u`
				ON `u`.`id`=`c`.uid
				
			WHERE `c`.text_bb LIKE '%$value%'
			
			LIMIT $start, $end"
		);
		if (!$query || $this->db->num_rows($query) <= 0) return null;

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$text = trim(strip_tags($ar['text_html']));
			$text = $this->db->HSC($text);
			$text = preg_replace("/$value/iu", '<span class="search-selected">$0</span>', $text);

			$data = json_decode($ar['data']);

			$data = [
				"ID" => intval($ar['id']),
				"NID" => intval($ar['nid']),
				"UID" => intval($ar['uid']),
				"TITLE" => $this->db->HSC($ar['title']),
				"LOGIN" => $this->db->HSC($ar['login']),
				"TIME_CREATE" => date("d.m.Y в H:i", $data->time_create),
				//"CID"		=> intval($ar['cid']),
				//"CATEGORY"	=> $this->db->HSC($ar['category']),
				"TEXT" => $text
			];

			echo $this->core->sp(MCR_THEME_MOD . "search/comments/comment-id.phtml", $data);
		}

		return ob_get_clean();
	}
}