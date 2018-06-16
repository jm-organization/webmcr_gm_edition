<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class submodule
{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct($core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		$bc = [
			$this->l10n->gettext('module_search') => BASE_URL."?mode=search",
			$this->l10n->gettext('by_news') => BASE_URL."?mode=search&type=news"
		];
		$this->core->bc = $this->core->gen_bc($bc);
	}

	private function results_array($value)
	{
		$start = $this->core->pagination($this->cfg->pagin['search_news'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['search_news']; // Set end pagination

		$query = $this->db->query(
			"SELECT 
				`n`.id, 
				`n`.title, 
				`n`.text_html
			FROM `mcr_news` AS `n`
			
			LEFT JOIN `mcr_news_cats` AS `c`
				ON `c`.id=`n`.cid
				
			WHERE `n`.title LIKE '%$value%' OR `n`.text_html LIKE '%$value%'
			
			LIMIT $start, $end"
		);
		if (!$query || $this->db->num_rows($query) <= 0) return null;

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			//$title = $this->db->HSC($ar['title']);
			$text = trim(str_replace('{READMORE}', '', $ar['text_html']));
			//$text = $this->db->HSC($text);
			$title = preg_replace("/$value/iu", '<span class="search-selected">$0</span>', $ar['title']);
			$text = preg_replace("/$value/iu", '<span class="search-selected">$0</span>', $text);

			$data = [
				"ID" => intval($ar['id']),
				"TITLE" => $title,
				//"CID"		=> intval($ar['cid']),
				//"CATEGORY"	=> $this->db->HSC($ar['category']),
				"TEXT" => $text
			];

			echo $this->core->sp(MCR_THEME_MOD."search/news/news-id.html", $data);
		}

		return ob_get_clean();
	}

	public function results()
	{
		if (!$this->core->is_access('sys_search_news')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 1, "?mode=403");
		}

		$value = (isset($_GET['value'])) ? $_GET['value'] : '';
		$value = trim($value);

		if (empty($value)) {
			$this->core->notify($this->l10n->gettext('error_404'), $this->l10n->gettext('empty_query'), 2, "?mode=403");
		}

		$safe_value = $this->db->safesql($value);
		$html_value = $this->db->HSC($value);
		$sql = "SELECT COUNT(*) FROM `mcr_news` WHERE title LIKE '%$safe_value%' OR mcr_news.text_html LIKE '%$safe_value%'";
		$query = $this->db->query($sql);

		if (!$query) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2);
		}

		$ar = $this->db->fetch_array($query);
		$page = "?mode=search&type=news&value=$html_value&pid=";

		$data = [
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['search_news'], $page, $ar[0]),
			"RESULT" => $this->results_array($safe_value),
			"QUERY" => $html_value,
			"QUERY_COUNT" => intval($ar[0])
		];

		return $this->core->sp(MCR_THEME_MOD."search/results.html", $data);
	}
}