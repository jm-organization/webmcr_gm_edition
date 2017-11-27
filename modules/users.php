<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class module{
	private $core, $db, $cfg_m, $user, $lng;

	public function __construct($core){
		$this->core = $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg_m;
		$this->user	= $core->user;
		$this->lng = $core->lng_m;

		$bc = array(
			$this->lng['mod_name'] => BASE_URL."?mode=users"
		);

		$this->core->bc = $this->core->gen_bc($bc);
	}

	private function user_array($search='', $gid=''){
		$end = $this->cfg['users_on_page'];
		$start = $this->core->pagination($end, 0, 0); // Set start pagination
		$where = "";

		$ctables = $this->core->cfg->db['tables'];
		$ug_f = $ctables['ugroups']['fields'];
		$us_f = $ctables['users']['fields'];

		if(!empty($gid)){
			$gid2 = intval($gid);
			$where .= " WHERE `u`.`{$us_f['group']}`='$gid2'";
		}

		if(!empty($search)){
			$searchstr = $this->db->safesql(urldecode($search));
			if(!preg_match("/[а-яА-ЯёЁ]+/iu", $searchstr)){
				$where .= (!empty($gid)) ? " AND " : " WHERE ";
				$where .= "`u`.`{$us_f['login']}` LIKE '%$searchstr%'";
			}
		}

		$query = $this->db->query("
			SELECT 
				`u`.`{$us_f['group']}`,  
				`u`.`{$us_f['login']}`, 
				`u`.`{$us_f['is_skin']}`,
				`u`.`{$us_f['is_cloak']}`, 
				`u`.`{$us_f['date_reg']}`, 
				`u`.`{$us_f['gender']}`,
				`g`.`{$ug_f['title']}` AS `group`, 
				`g`.`{$ug_f['color']}` AS `gcolor`
			FROM `{$this->core->cfg->tabname('users')}` AS `u`
			LEFT JOIN `{$this->core->cfg->tabname('ugroups')}` AS `g`
			ON `g`.`{$ug_f['id']}`=`u`.`{$us_f['group']}`
			$where
			ORDER BY `u`.`{$us_f['id']}` DESC
			LIMIT $start, $end
		");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->core->sp(MCR_THEME_MOD."users/user-none.html").$this->db->error(); }

		ob_start();

		while($ar = $this->db->fetch_assoc($query)){
			$color = $this->db->HSC($ar['gcolor']);

			$login = $this->db->HSC($ar[$us_f['login']]);
			$group = $this->db->HSC($ar['group']);

			$date_reg = date('d.m.Y '.$this->lng['in'].' H:i', intval(@$ar[$us_f['date_reg']]));

			$gender = (intval($ar[$us_f['gender']])==1) ? $this->core->lng['gender_w'] : $this->core->lng['gender_m'];

			$is_girl = (intval($ar[$us_f['gender']])==1) ? 'default_mini_female.png' : 'default_mini.png';

			$avatar = (intval($ar[$us_f['is_skin']])==1) ? $login.'_mini.png' : $is_girl;

			$url = BASE_URL.'?mode=users&uid='.$login;
			$gurl = BASE_URL.'?mode=users&gid='.intval($ar[$us_f['group']]);

			$data = array(
				'AVATAR' => UPLOAD_URL.'skins/interface/'.$avatar.'?'.mt_rand(1000,9999),
				'LOGIN' => $this->core->colorize($login, $color, '<a href="'.$url.'" style="color: {COLOR};">{STRING}</a>'),
				'GROUP' => $this->core->colorize($group, $color, '<a href="'.$gurl.'" style="color: {COLOR};">{STRING}</a>'),
				'URL' => $url,
				'REGISTERED' => $date_reg,
				'GENDER' => $gender,
			);

			echo $this->core->sp(MCR_THEME_MOD."users/user-id.html", $data);
		}

		return ob_get_clean();
	}

	private function user_list($search='', $gid=''){
		if(!$this->core->is_access('mod_users_list')){ $this->core->notify($this->core->lng['403'], $this->core->lng['t_403'], 2, "?mode=403"); }

		$page = '?mode=users'; // for sorting
		$sql = "SELECT COUNT(*) FROM `{$this->core->cfg->tabname('users')}`"; // for sorting

		$ctables = $this->core->cfg->db['tables'];
		$us_f = $ctables['users']['fields'];

		if(!empty($gid)){
			$gid2 = intval($gid);
			$page .= '&gid='.$gid2;
			$sql .= " WHERE `{$us_f['group']}`='$gid2'";
		}

		if(!empty($search)){
			$srch = urldecode($search);
			if(!preg_match("/[а-яА-ЯёЁ]+/iu", $srch)){
				$page .= '&search='.$this->db->HSC($srch);
				$searchstr = $this->db->safesql($srch);
				$sql .= (!empty($gid)) ? " AND " : " WHERE ";
				$sql .= "`{$us_f['login']}` LIKE '%$searchstr%'";
			}
		}

		$query = $this->db->query($sql);

		if(!$query){ exit("SQL Error"); }

		$ar = $this->db->fetch_array($query);

		$data = array(
			"PAGINATION" => $this->core->pagination($this->cfg['users_on_page'], $page.'&pid=', $ar[0]),
			"USERS" => $this->user_array($search, $gid)
		);

		return $this->core->sp(MCR_THEME_MOD."users/user-list.html", $data);
	}

	private function user_full(){
		if(!$this->core->is_access('mod_users_full')){ $this->core->notify($this->core->lng['403'], $this->core->lng['t_403'], 2, "?mode=403"); }

		$ctables = $this->core->cfg->db['tables'];
		$ug_f = $ctables['ugroups']['fields'];
		$us_f = $ctables['users']['fields'];
		$ui_f = $ctables['iconomy']['fields'];

		$bc = array(
			$this->lng['mod_name'] => BASE_URL."?mode=users",
			$this->lng['user_profile'] => ''
		);

		$this->core->bc = $this->core->gen_bc($bc);

		$login = $this->db->safesql($_GET['uid']);

		$query = $this->db->query("
			SELECT 
				`u`.`{$us_f['id']}`, 
				`u`.`{$us_f['group']}`,
				`u`.`{$us_f['login']}`, 
				`u`.`{$us_f['is_skin']}`,
				`u`.`{$us_f['is_cloak']}`, 
				`u`.`{$us_f['date_reg']}`, 
				`u`.`{$us_f['date_last']}`, 
				`u`.`{$us_f['gender']}`,
				`g`.`{$ug_f['title']}` AS `group`, 
				`g`.`{$ug_f['color']}` AS `gcolor`,
				`i`.`{$ui_f['money']}`, 
				`i`.`{$ui_f['rm']}`
			FROM `{$this->core->cfg->tabname('users')}` AS `u`
			LEFT JOIN `{$this->core->cfg->tabname('ugroups')}` AS `g`
			ON `g`.`{$ug_f['id']}`=`u`.`{$us_f['group']}`
			LEFT JOIN `{$this->core->cfg->tabname('iconomy')}` AS `i`
			ON `i`.`{$ui_f['login']}`=`u`.`{$us_f['login']}`
			WHERE `u`.`{$us_f['login']}`='$login' OR `u`.`{$us_f['id']}`='$login'
		");

		if(!$query || $this->db->num_rows($query)<=0){ $this->core->notify($this->core->lng['404'], $this->lng['user_not_found'], 2, "?mode=users"); }

		$ar = $this->db->fetch_assoc($query);

		$color = $this->db->HSC($ar['gcolor']);
		$group = $this->db->HSC($ar['group']);

		$date_reg = date('d.m.Y '.$this->lng['in'].' H:i', intval(@$ar[$us_f['date_reg']]));
		$date_last = date('d.m.Y '.$this->lng['in'].' H:i', intval(@$ar[$us_f['date_last']]));

		$is_skin = (intval($ar[$us_f['is_skin']])==1) ? true : false;
		$is_cloak = (intval($ar[$us_f['is_cloak']])==1) ? true : false;

		$gender = (intval($ar[$us_f['gender']])==1) ? $this->core->lng['gender_w'] : $this->core->lng['gender_m'];

		$is_girl = (intval($ar[$us_f['gender']])==1) ? 'default_female' : 'default';

		$avatar = ($is_skin || $is_cloak) ? $this->db->HSC($login) : $is_girl;

		$data = array(
			'LOGIN' => $this->core->colorize($ar['login'], $color),
			'GROUP' => $this->core->colorize($group, $color),
			'MONEY' => floatval(@$ar[$ui_f['money']]), // @ because money can be null
			'REALMONEY' => floatval(@$ar[$ui_f['rm']]), // @ because realmoney can be null
			'AVATAR' => UPLOAD_URL.'skins/interface/'.$avatar.'.png?'.mt_rand(1000,9999),
			'DATE_REG' => $date_reg,
			'DATE_LAST' => $date_last,
			'GENDER' => $gender,
			'ADMIN' => '',
			'COMMENTS' => $this->comment_list($ar[$us_f['id']]),
		);

		return $this->core->sp(MCR_THEME_MOD."users/user-full.html", $data);
	}

	private function comment_array($uid){
		$end = $this->cfg['comments_on_page'];
		$start = $this->core->pagination($end, 0, 0); // Set start pagination

		$ctables = $this->core->cfg->db['tables'];
		$ug_f = $ctables['ugroups']['fields'];
		$us_f = $ctables['users']['fields'];

		$query = $this->db->query("
			SELECT 
				`c`.id, 
				`c`.`from`, 
				`c`.text_html, 
				`c`.`data`,
				`u`.`{$us_f['login']}`, 
				`g`.`{$ug_f['color']}` AS `gcolor`
			FROM `mod_users_comments` AS `c`
			LEFT JOIN `{$this->core->cfg->tabname('users')}` AS `u` 
				ON `u`.`{$us_f['id']}`=`c`.`from`
			LEFT JOIN `{$this->core->cfg->tabname('ugroups')}` AS `g`
				ON `g`.`{$ug_f['id']}`=`u`.`{$us_f['group']}`
			WHERE `c`.uid='$uid'
			ORDER BY `c`.id DESC
			LIMIT $start, $end
		");

		if(!$query || $this->db->num_rows($query)<=0){ return null; }

		ob_start();

		while($ar = $this->db->fetch_assoc($query)){
			$json = json_decode($ar['data'], true);
			$color = $this->db->HSC($ar['gcolor']);
			$admin = '';

			if($this->core->is_access('mod_users_comment_del') || $this->core->is_access('mod_users_comment_del_all')){
				$admin = $this->core->sp(MCR_THEME_MOD."users/comments/comment-admin.html");
			}

			$data = array(
				'ID' => intval($ar['id']),
				'LOGIN' => $this->core->colorize($this->db->HSC($ar[$us_f['login']]), $color),
				'TEXT' => $ar['text_html'],
				'DATE_CREATE' => date('d.m.Y '.$this->lng['in'].' H:i', @$json['date_create']),
				'ADMIN' => $admin,
			);

			if($this->user->id == intval($ar['from'])){
				echo $this->core->sp(MCR_THEME_MOD."users/comments/comment-id-self.html", $data);
			}else{
				echo $this->core->sp(MCR_THEME_MOD."users/comments/comment-id.html", $data);
			}
		}

		return ob_get_clean();
	}

	private function comment_form(){
		if(!$this->cfg['enable_comments'] || !$this->core->is_access('mod_users_comment_add')){ return null; }

		$bb = $this->core->load_bb_class();

		$data['BB_PANEL'] = $bb->bb_panel('bb-comments');

		return $this->core->sp(MCR_THEME_MOD."users/comments/comment-form.html", $data);
	}

	private function comment_list($uid){
		if(!$this->core->is_access('mod_users_comments')){ return null; }

		$uid = intval($uid);

		$query = $this->db->query("SELECT COUNT(*) FROM `mcr_users_comments` WHERE uid='$uid'");

		if(!$query){ exit("SQL Error"); }

		$ar = $this->db->fetch_array($query);

		$page = '?mode=users&uid='.$this->db->HSC($_GET['uid']).'&pid=';

		$data = array(
			"PAGINATION" => $this->core->pagination($this->cfg['comments_on_page'], $page, $ar[0]),
			"COMMENTS" => $this->comment_array($uid),
			"COMMENT_FORM" => $this->comment_form(),
		);

		return $this->core->sp(MCR_THEME_MOD."users/comments/comment-list.html", $data);
	}

	public function content(){

		if($this->cfg['install']){

			if(!$this->core->is_access('sys_adm_main')){ $this->core->notify($this->core->lng['403'], $this->core->lng['t_403'], 2, "?mode=403"); }
			$this->core->notify($this->core->lng['e_attention'], $this->lng['need_install'], 4, 'install_us/');
		}

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."users/header.html");

		if(isset($_GET['uid'])){
			return $this->user_full();
		}

		$search = (isset($_GET['search'])) ? $_GET['search'] : '';
		$gid = (isset($_GET['gid'])) ? $_GET['gid'] : '';

		return $this->user_list($search, $gid);
	}
}

?>