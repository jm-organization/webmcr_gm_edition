<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct($core){
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		$bc = array(
			$this->l10n->gettext('module_news') => BASE_URL."?mode=news"
		);
		$this->core->bc = $this->core->gen_bc($bc);
	}

	private function get_likes($vote, $id, $data){
		if (intval($vote) === 0) { return null; }
		$data = array(
			"ID" => intval($id),
			"LIKES" => empty($data)?0:intval($data['likes']),
			"DISLIKES" => empty($data)?0:intval($data['dislikes'])
		);

		return $this->core->sp(MCR_THEME_MOD."news/new-like.html", $data);
	}

	private function get_admin($id, $attach){
		if(!$this->core->is_access('sys_adm_news')){ return null; }

		$data = array(
			'ID' => $id,
			'ATTACH' => ($attach==1)
				? $this->l10n->gettext('unattach')
				: $this->l10n->gettext('attach')
		);

		return $this->core->sp(MCR_THEME_MOD."news/new-admin.html", $data);
	}

	private function news_array($cid=false){
		$start = $this->core->pagination($this->cfg->pagin['news'], 0, 0);
		$end = $this->cfg->pagin['news'];
		$time = time();

		$standart = "`n`.`hidden` = 0 AND `n`.`date` < $time";
		$category = "`n`.cid='$cid' AND `c`.`hidden` = 0";
		$where = ($cid != false)?"($category) OR ($standart)":$standart;

		$query = $this->db->query(
			"SELECT 
				`n`.id, `n`.cid, `n`.title, `n`.text_html, 
				`n`.`vote`, `n`.`discus`, `n`.`uid`, `n`.`date`, 
				`n`.`attach`, `n`.`data`,
				
				`c`.title AS `category`
			FROM `mcr_news` AS `n`
			
			LEFT JOIN `mcr_news_cats` AS `c`
				ON `c`.id=`n`.cid
				
			WHERE $where	
			
			GROUP BY `n`.`id`		
			ORDER BY `n`.`attach` DESC, `n`.id DESC
			
			LIMIT $start, $end"
		);

		ob_start();

		if (!$query || $this->db->num_rows($query) <= 0) {
			echo $this->core->sp(MCR_THEME_MOD."news/new-none.html");
			return ob_get_clean();
		}

		while ($ar = $this->db->fetch_assoc($query)) {
			$id = intval($ar['id']);
			$attach = intval($ar['attach']);
			$data = json_decode($ar['data'], true);
			
			$text_with_pagebreaker = $ar['text_html'];
			$text_pos = mb_strpos($text_with_pagebreaker, '{READMORE}', 0, 'UTF-8');
			$text = ($text_pos !== false)?mb_substr($text_with_pagebreaker, 0, $text_pos, "UTF-8"):$text_with_pagebreaker;

			$date = '<div class="date" rel="tooltip" title="'.$this->l10n->gettext('date_create').'">'.$this->l10n->localize($ar['date'], 'timestamp', $this->l10n->get_date_format()).'</div>';
			$time = '<div class="time" rel="tooltip" title="'.$this->l10n->gettext('time_create').'">'.$this->l10n->localize($ar['date'], 'timestamp', $this->l10n->get_time_format()).'</div>';

			$votes = isset($data['votes'])?$data['votes']:array();

			$new_data = array(
				"ID" => $id,
				"CID" => intval($ar['cid']),
				"TITLE" => $this->db->HSC($ar['title']),
				"CATEGORY" => $this->db->HSC($ar['category']),
				"TEXT" => $text,
				"UID" => intval($ar['uid']),
				"COMMENTS" => '',
				"VIEWS" => isset($data['views'])?$data['views']:0,
				"DATE" => $date.$time,
				"LIKES" => $this->get_likes($ar['vote'], $id, $votes),
				"ADMIN" => $this->get_admin($id, $attach),
			);

			$attached = ($attach==1) ? '-attached' : '';

			echo $this->core->sp(MCR_THEME_MOD."news/new-id".$attached.".html", $new_data);
		}

		if ($cid !== false) {
			/** @var module $new_data */
			$bc = array(
				$this->l10n->gettext('mod_name') => BASE_URL."?mode=news",
				$new_data['CATEGORY'] => BASE_URL."?mode=news&cid=$cid"
			);

			$this->core->bc = $this->core->gen_bc($bc);
		}

		return ob_get_clean();
	}

	private function news_list($cid=false){
		if (!$this->core->is_access('sys_news_list')) { $this->core->notify(
			$this->l10n->gettext('error_403'),
			$this->l10n->gettext('news_access_denied'),
			2,
			"?mode=403"
		); }

		$sql = "SELECT COUNT(*) FROM `mcr_news`";
		$page = "?mode=news&pid=";

		if($cid!==false){
			$cid = intval($cid);
			$sql = "SELECT COUNT(*) FROM `mcr_news` WHERE cid='$cid'";
			$page = "?mode=news&cid=$cid&pid=";
		}

		$query = $this->db->query($sql);

		if(!$query){ exit("SQL Error"); }

		$ar = $this->db->fetch_array($query);

		$data = array(
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['news'], $page, $ar[0]),
			"NEWS" => $this->news_array($cid)
		);

		return $this->core->sp(MCR_THEME_MOD."news/new-list.html", $data);

	}

	private function comments_array($nid=1){
		if(!$this->core->is_access('sys_comment_list')){
			return $this->core->sp(MCR_THEME_MOD."news/comments/comment-access.html");
		}

		$start = $this->core->pagination($this->cfg->pagin['comments'], 0, 0); // Set start pagination
		$end = $this->cfg->pagin['comments']; // Set end pagination

		$ctables = $this->cfg->db['tables'];
		$ug_f = $ctables['ugroups']['fields'];
		$us_f = $ctables['users']['fields'];

		$query = $this->db->query(
			"SELECT 
				`c`.id, `c`.text_html, `c`.uid, 
				`c`.`data`,
				
				`u`.`{$us_f['login']}`, 
				
				`g`.`{$ug_f['color']}` AS `gcolor`
			FROM `mcr_news_comments` AS `c`
			
			LEFT JOIN `{$this->cfg->tabname('users')}` AS `u`
				ON `u`.`{$us_f['id']}`=`c`.uid
				
			LEFT JOIN `{$this->cfg->tabname('ugroups')}` AS `g`
				ON `g`.`{$ug_f['id']}`=`u`.`{$us_f['group']}`
				
			WHERE `c`.nid='$nid'
			
			ORDER BY `c`.id DESC
			
			LIMIT $start, $end"
		);
		if(!$query || $this->db->num_rows($query)<=0){ return $this->core->sp(MCR_THEME_MOD."news/comments/comment-none.html"); }

		ob_start();
		
		while ($ar = $this->db->fetch_assoc($query)) {
			$act_del = $act_edt = $act_get = '';
			$id = intval($ar['id']);

			$data = array(
				"ID" => $id,
				"LNG" => $this->lng
			);

			if($this->core->is_access('sys_comment_del') || $this->core->is_access('sys_comment_del_all')){
				$act_del = $this->core->sp(MCR_THEME_MOD."news/comments/comment-act-del.html", $data);
			}
			if($this->core->is_access('sys_comment_edt') || $this->core->is_access('sys_comment_edt_all')){
				$act_edt = $this->core->sp(MCR_THEME_MOD."news/comments/comment-act-edt.html", $data);
			}
			if($this->user->is_auth){
				$act_get = $this->core->sp(MCR_THEME_MOD."news/comments/comment-act-get.html", $data);
			}

			$login = (is_null($ar[$us_f['login']])) ? 'Пользователь удален' : $this->db->HSC($ar[$us_f['login']]);
			$color = $this->db->HSC($ar['gcolor']);

			$com_data = array(
				"ID" => $id,
				"NID" => $nid,
				"TEXT" => $ar['text_html'],
				"UID" => intval($ar['uid']),
				"DATA" => json_decode($ar['data'], true),
				"LOGIN" => $this->core->colorize($login, $color),
				"ACTION_DELETE"	=> $act_del,
				"ACTION_EDIT" => $act_edt,
				"ACTION_QUOTE" => $act_get
			);

			echo $this->core->sp(MCR_THEME_MOD."news/comments/comment-id.html", $com_data);
		}

		return ob_get_clean();
	
	}

	private function get_comment_form() {
		if(!$this->core->is_access('sys_comment_add')){ return null; }

		$bb = $this->core->load_bb_class();

		$data['BB_PANEL'] = $bb->bb_panel('bb-comments');

		return $this->core->sp(MCR_THEME_MOD."news/comments/comment-form.html", $data);
	}

	private function comments_list($nid=1) {
		$sql = "SELECT COUNT(*) FROM `mcr_news_comments` WHERE nid='$nid'";
		$page = "?mode=news&id=$nid&pid=";

		$query = $this->db->query($sql);
		if (!$query) { return null; }

		$ar = $this->db->fetch_array($query);

		$data = array(
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['comments'], $page, intval($ar[0])),
			"COMMENTS" => $this->comments_array($nid),
			"COUNT" => $ar[0],
			"COMMENTS_FORM"	=> $this->get_comment_form()
		);

		return $this->core->sp(MCR_THEME_MOD."news/comments/comment-list.html", $data);
	}

	private function update_views($nid){
		$query = $this->db->query(
			"SELECT COUNT(*)
			FROM `mcr_news_views`
			WHERE nid='$nid' AND (uid='{$this->user->id}' OR ip='{$this->user->ip}')"
		);
		if (!$query) { $this->core->notify($this->core->l10n->gettext('error_sql_critical')); }

		$ar = $this->db->fetch_array($query);

		if (intval($ar[0]) > 0) { return false; }

		$uid = ($this->user->id <= 0)?1:$this->user->id;
		$time = time();

		$insert = $this->db->query(
			"INSERT INTO `mcr_news_views` (nid, uid, ip, `time`)
			VALUES ('$nid', '$uid', '{$this->user->ip}', $time)"
		);
		if (!$insert) { $this->core->notify($this->core->l10n->gettext('error_sql_critical')); }
		$views = $this->db->query("SELECT COUNT(*) FROM `mcr_news_views` WHERE `nid`='$nid'");
		$views = $this->db->fetch_assoc($views);

		$data = $this->db->query("SELECT `data` FROM `mcr_news` WHERE `id`='$nid'");
		$data = $this->db->fetch_assoc($data);

		$data = json_decode($data['data'], true);
		$data = array_merge($data, array('views' => intval($views)));
		$data = json_encode($data);

		if (!$this->db->query(
			"UPDATE `mcr_news`
			SET `data`='{$data}'
			WHERE `id`='$nid'"
		)) die($this->core->l10n->gettext('error_sql_critical'));

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		$_SESSION['views-new-'.$nid] = true;

		return true;
	}

	private function news_full(){
		if (!$this->core->is_access('sys_news_full')) {
			$this->core->notify(
				$this->l10n->gettext('error_403'),
				$this->l10n->gettext('news_access_denied'),
				2,
				"?mode=403"
			);
		}

		$id = intval($_GET['id']);

		$query = $this->db->query("
			SELECT 
				`n`.id, `n`.cid, `n`.title, 
				`n`.text_html, `n`.vote, `n`.discus, 
				`n`.uid, `n`.`date`, `n`.`attach`,
				`n`.`data`,
				
				`c`.title AS `category`
			FROM `mcr_news` AS `n`
			
			LEFT JOIN `mcr_news_cats` AS `c`
				ON `c`.id=`n`.cid
				
			WHERE `n`.id='$id'
			GROUP BY `n`.`id`
		");
		

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify(
				$this->l10n->gettext('error_403'),
				$this->l10n->gettext('news_not_found')
			);
		}

		$ar = $this->db->fetch_assoc($query);

		if (!isset($_SESSION['views-new-'.$id])) {
			$this->update_views($id);
		}

		$comments = (intval($ar['discus']) == 1)?$this->comments_list($id):$this->core->sp(MCR_THEME_MOD."news/comments/comment-closed.html");

		$format = $this->l10n->get_date_format().' '.$this->l10n->gettext('in').' '.$this->l10n->get_time_format();
		$data = json_decode($ar['data'], true);
		$votes = isset($data['votes'])?$data['votes']:array();

		$new_data = array(
			"ID" => $id,
			"CID" => intval($ar['cid']),
			"TITLE" => $this->db->HSC($ar['title']),
			"TEXT" => str_replace('{READMORE}', '', $ar['text_html']),
			"UID" => intval($ar['uid']),
			"DATE" => $this->l10n->localize($ar['date'], 'timestamp', $format),
			"CATEGORY" => $this->db->HSC($ar['category']),
			"VIEWS"	=> isset($data['views'])?$data['views']:0,
			"COMMENTS" => $comments,
			"LIKES"	=> $this->get_likes($ar['vote'], $id, $votes),
			"ADMIN"	=> $this->get_admin($id, intval($ar['attach'])),
		);

		$bc = array(
			$this->l10n->gettext('module_news') => BASE_URL."?mode=news",
			$new_data["CATEGORY"] => BASE_URL."?mode=news&cid=".$new_data["CID"],
			$new_data["TITLE"] => ""
		);
		
		$this->core->bc = $this->core->gen_bc($bc);

		return $this->core->sp(MCR_THEME_MOD."news/new-full.html", $new_data);
	}

	public function content(){

		if (isset($_GET['id'])) {
			$this->core->header .= $this->core->sp(MCR_THEME_MOD."news/header-full.html");

			$content = $this->news_full();

		} elseif (isset($_GET['cid'])){
			$this->core->header .= $this->core->sp(MCR_THEME_MOD."news/header-list.html");

			$content = $this->news_list($_GET['cid']);

		} else {
			$this->core->header .= $this->core->sp(MCR_THEME_MOD."news/header-list.html");

			$content = $this->news_list();

		}

		if($this->core->is_access('sys_adm_news')){
			$this->core->header .= $this->core->sp(MCR_THEME_MOD."news/header-admin.html");
		}

		return $content;
	}
}