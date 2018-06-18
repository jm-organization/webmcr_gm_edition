<?php

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class module
{
	private $core, $db, $cfg_m, $user, $l10n;

	public function __construct(core $core)
	{
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg_m;
		$this->user = $core->user;
		$this->l10n = $core->l10n;

		$bc = [
			$this->l10n->gettext('users') => BASE_URL."?mode=users"
		];

		$this->core->bc = $this->core->gen_bc($bc);
	}

	private function user_array($search = '', $gid = '')
	{
		$end = $this->cfg['users_on_page'];
		$start = $this->core->pagination($end, 0, 0); // Set start pagination
		$where = "";

		if (!empty($gid)) {
			$gid2 = intval($gid);
			$where .= " WHERE `u`.`gid`='$gid2'";
		}

		if (!empty($search)) {
			$searchstr = $this->db->safesql($search);
			if (!preg_match("/[а-яА-ЯёЁ]+/iu", $searchstr)) {
				$where .= (!empty($gid)) ? " AND " : " WHERE ";
				$where .= "`u`.`login` LIKE '%$searchstr%'";
			}
		}

		$query = $this->db->query("
			SELECT 
				`u`.`gid`,  
				`u`.`login`, 
				`u`.`is_skin`,
				`u`.`is_cloak`, 
				`u`.`time_create`, 
				`u`.`gender`,
				
				`g`.`title` AS `group`, 
				`g`.`color` AS `gcolor`
			FROM `mcr_users` AS `u`
			
			LEFT JOIN `mcr_groups` AS `g`
				ON `g`.`id`=`u`.`gid`
				
			$where
			
			ORDER BY `u`.`id` DESC
			
			LIMIT $start, $end
		");
		if (!$query || $this->db->num_rows($query) <= 0) {
			return $this->core->sp(MCR_THEME_MOD."users/user-none.phtml").$this->db->error();
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$color = $this->db->HSC($ar['gcolor']);

			$login = $this->db->HSC($ar['login']);
			$group = $this->db->HSC($ar['group']);

			$delimeter = $this->l10n->gettext('in');
			$date_reg = $this->l10n->parse_date(strtotime(@$ar['time_create']));

			$gender = (intval($ar['gender']) == 1) ? $this->l10n->gettext('gender_w') : $this->l10n->gettext('gender_m');

			$is_girl = (intval($ar['gender']) == 1) ? 'default_mini_female.png' : 'default_mini.png';

			$avatar = (intval($ar['is_skin']) == 1) ? $login.'_mini.png' : $is_girl;

			$url = BASE_URL.'?mode=users&uid='.$login;
			$gurl = BASE_URL.'?mode=users&gid='.intval($ar['group']);

			$data = [
				'AVATAR' => UPLOAD_URL.'skins/interface/'.$avatar.'?'.mt_rand(1000, 9999),
				'LOGIN' => $this->core->colorize($login, $color, '<a href="'.$url.'" style="color: {COLOR};">{STRING}</a>'),
				'GROUP' => $this->core->colorize($group, $color, '<a href="'.$gurl.'" style="color: {COLOR};">{STRING}</a>'),
				'URL' => $url,
				'REGISTERED' => $date_reg,
				'GENDER' => $gender,
			];

			echo $this->core->sp(MCR_THEME_MOD."users/user-id.phtml", $data);
		}

		return ob_get_clean();
	}

	private function user_list($search = '', $gid = '')
	{
		if (!$this->core->is_access('mod_users_list')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('t_403'), 2, "?mode=403");
		}

		$page = '?mode=users'; // for sorting
		$sql = "SELECT COUNT(*) FROM `mcr_users`"; // for sorting

		if (!empty($gid)) {
			$gid2 = intval($gid);
			$page .= '&gid='.$gid2;
			$sql .= " WHERE `gid`='$gid2'";
		}

		if (!empty($search)) {
			if (!preg_match("/[а-яА-ЯёЁ]+/iu", $search)) {
				$page .= '&search='.$this->db->HSC($search);
				$searchstr = $this->db->safesql($search);
				$sql .= (!empty($gid)) ? " AND " : " WHERE ";
				$sql .= "`login` LIKE '%$searchstr%'";
			}
		}

		$query = $this->db->query($sql);

		if ($query) {
			$ar = $this->db->fetch_array($query);

			$data = [
				"PAGINATION" => $this->core->pagination($this->cfg['users_on_page'], $page.'&pid=', $ar[0]),
				"USERS" => $this->user_array($search, $gid)
			];

			return $this->core->sp(MCR_THEME_MOD."users/user-list.phtml", $data);
		}

		exit("SQL Error");
	}

	private function user_full()
	{
		if (!$this->core->is_access('mod_users_full')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('t_403'), 2, "?mode=403");
		}
		$bc = [
			$this->l10n->gettext('users') => BASE_URL."?mode=users",
			$this->l10n->gettext('user_profile') => ''
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$login = $this->db->safesql($_GET['uid']);

		$query = $this->db->query("
			SELECT 
				`u`.`id`, 
				`u`.`gid`,
				`u`.`login`, 
				`u`.`is_skin`,
				`u`.`is_cloak`, 
				`u`.`time_create`, 
				`u`.`time_last`, 
				`u`.`gender`,
				`g`.`title` AS `group`, 
				`g`.`color` AS `gcolor`,
				`i`.`money`, 
				`i`.`realmoney`
			FROM `mcr_users` AS `u`
			
			LEFT JOIN `mcr_groups` AS `g`
				ON `g`.`id`=`u`.`gid`
			
			LEFT JOIN `mcr_iconomy` AS `i`
				ON `i`.`login`=`u`.`login`
			
			WHERE `u`.`login`='$login' OR `u`.`id`='$login'
		");
		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_404'), $this->l10n->gettext('user_not_found'), 2, "?mode=users");
		}

		$ar = $this->db->fetch_assoc($query);

		$color = $this->db->HSC('gcolor');
		$group = $this->db->HSC('group');

		$date_reg = $this->l10n->parse_date(strtotime($this->user->time_create));
		$date_last = $this->l10n->parse_date(strtotime($this->user->time_last));

		$is_skin = (intval($ar['is_skin']) == 1) ? true : false;
		$is_cloak = (intval($ar['is_cloak']) == 1) ? true : false;

		$gender = (intval($ar['gender']) == 1) ? $this->l10n->gettext('gender_w') : $this->l10n->gettext('gender_m');

		$is_girl = (intval($ar['gender']) == 1) ? 'default_female' : 'default';

		$avatar = ($is_skin || $is_cloak) ? $this->db->HSC($login) : $is_girl;

		$data = [
			'LOGIN' => $this->core->colorize($ar['login'], $color),
			'GROUP' => $this->core->colorize($group, $color),
			'MONEY' => floatval(@$ar['money']),
			// @ because money can be null
			'REALMONEY' => floatval(@$ar['realmoney']),
			// @ because realmoney can be null
			'AVATAR' => UPLOAD_URL.'skins/interface/'.$avatar.'.png?'.mt_rand(1000, 9999),
			'DATE_REG' => $date_reg,
			'DATE_LAST' => $date_last,
			'GENDER' => $gender,
			'ADMIN' => '',
			'COMMENTS' => $this->comment_list($ar['id']),
		];

		return $this->core->sp(MCR_THEME_MOD."users/user-full.phtml", $data);
	}

	private function comment_array($uid)
	{
		$end = $this->cfg['comments_on_page'];
		$start = $this->core->pagination($end, 0, 0); // Set start pagination

		$query = $this->db->query("
			SELECT 
				`c`.id, 
				`c`.`from`, 
				`c`.text_html, 
				`c`.`data`,
				`u`.`login`, 
				`g`.`color` AS `gcolor`
			FROM `mcr_users_comments` AS `c`
			
			LEFT JOIN `mcr_users` AS `u` 
				ON `u`.`id`=`c`.`from`
				
			LEFT JOIN `mcr_groups` AS `g`
				ON `g`.`id`=`u`.`gid`
				
			WHERE `c`.uid='$uid'
			
			ORDER BY `c`.id DESC
			
			LIMIT $start, $end
		");
		if (!$query || $this->db->num_rows($query) <= 0) {
			return null;
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$json = json_decode($ar['data'], true);
			$color = $this->db->HSC($ar['gcolor']);
			$admin = '';

			if ($this->core->is_access('mod_users_comment_del') || $this->core->is_access('mod_users_comment_del_all')) {
				$admin = $this->core->sp(MCR_THEME_MOD."users/comments/comment-admin.phtml");
			}

			$data = [
				'ID' => intval($ar['id']),
				'LOGIN' => $this->core->colorize($this->db->HSC($ar['login']), $color),
				'TEXT' => $ar['text_html'],
				'DATE_CREATE' => date('d.m.Y '.$this->l10n->gettext('in').' H:i', @$json['date_create']),
				'ADMIN' => $admin,
			];

			if ($this->user->id == intval($ar['from'])) {
				echo $this->core->sp(MCR_THEME_MOD."users/comments/comment-id-self.phtml", $data);
			} else {
				echo $this->core->sp(MCR_THEME_MOD."users/comments/comment-id.phtml", $data);
			}
		}

		return ob_get_clean();
	}

	private function comment_form()
	{
		if (!$this->cfg['enable_comments'] || !$this->core->is_access('mod_users_comment_add')) {
			return null;
		}

		$bb = $this->core->load_bb_class();

		$data['BB_PANEL'] = $bb->bb_panel('bb-comments');

		return $this->core->sp(MCR_THEME_MOD."users/comments/comment-form.phtml", $data);
	}

	private function comment_list($uid)
	{
		if (!$this->core->is_access('mod_users_comments')) {
			return null;
		}

		$uid = intval($uid);

		$query = $this->db->query("SELECT COUNT(*) FROM `mcr_users_comments` WHERE uid='$uid'");

		if ($query) {
			$ar = $this->db->fetch_array($query);

			$page = '?mode=users&uid='.$this->db->HSC($_GET['uid']).'&pid=';

			$data = [
				"PAGINATION" => $this->core->pagination($this->cfg['comments_on_page'], $page, $ar[0]),
				"COMMENTS" => $this->comment_array($uid),
				"COMMENT_FORM" => $this->comment_form(),
			];

			return $this->core->sp(MCR_THEME_MOD."users/comments/comment-list.phtml", $data);
		}

		exit("SQL Error");
	}

	public function content()
	{
		if ($this->cfg['install']) {
			if (!$this->core->is_access('sys_adm_main')) {
				$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('t_403'), 2, "?mode=403");
			}

			$this->core->notify($this->l10n->gettext('error_attention'), $this->l10n->gettext('need_install'), 4, 'install_us/');
		}

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."users/header.phtml");

		if (isset($_GET['uid'])) {
			return $this->user_full();
		}

		$search = (isset($_GET['search'])) ? $_GET['search'] : '';
		$gid = (isset($_GET['gid'])) ? $_GET['gid'] : '';

		return $this->user_list($search, $gid);
	}
}