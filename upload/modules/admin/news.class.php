<?php
/*
 * Изменения разработчиками JM Organization
 *
 * @contact: admin@jm-org.net
 * @web-site: www.jm-org.net
 *
 * @supplier: Magicmen
 * @script_author: Qexy
 *
 **/

if(!defined("MCR")){ exit("Hacking Attempt!"); }

require_once MCR_LIBS_PATH.'htmLawed/htmLawed.php';

class submodule{
	private $core, $db, $cfg, $user, $lng;

	public function __construct($core){
		$this->core = $core;
		$this->db	= $core->db;
		$this->cfg	= $core->cfg;
		$this->user	= $core->user;
		$this->lng	= $core->lng_m;

		if(!$this->core->is_access('sys_adm_news')){ $this->core->notify($this->core->lng['403'], $this->core->lng['e_403']); }

		$bc = array(
			$this->lng['mod_name'] => ADMIN_URL,
			$this->lng['news'] => ADMIN_URL."&do=news"
		);

		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/news/header.html");
	}

	private function news_array(){

		$start		= $this->core->pagination($this->cfg->pagin['adm_news'], 0, 0); // Set start pagination
		$end		= $this->cfg->pagin['adm_news']; // Set end pagination

		$where		= "";
		$sort		= "`n`.id";
		$sortby		= "DESC";

		if(isset($_GET['search']) && !empty($_GET['search'])){
			$search = $this->db->safesql(urldecode($_GET['search']));
			$where = "WHERE `n`.title LIKE '%$search%'";
		}

		if(isset($_GET['sort']) && !empty($_GET['sort'])){
			$expl = explode(' ', $_GET['sort']);

			$sortby = ($expl[0]=='asc') ? "ASC" : "DESC";

			switch(@$expl[1]){
				case 'title': $sort = "`n`.title"; break;
				case 'category': $sort = "`c`.title"; break;
			}
		}

		$query = $this->db->query("SELECT `n`.id, `n`.cid, `n`.title, `c`.title AS `category`
									FROM `mcr_news` AS `n`
									LEFT JOIN `mcr_news_cats` AS `c`
										ON `c`.id=`n`.cid
									$where
									ORDER BY $sort $sortby
									LIMIT $start, $end");

		

		if(!$query || $this->db->num_rows($query)<=0){ return $this->core->sp(MCR_THEME_MOD."admin/news/new-none.html"); }

		ob_start();

		while($ar = $this->db->fetch_assoc($query)){

			$page_data = array(
				"ID" => intval($ar['id']),
				"CID" => intval($ar['cid']),
				"TITLE" => $this->db->HSC($ar['title']),
				"CATEGORY" => $this->db->HSC($ar['category'])
			);
		
			echo $this->core->sp(MCR_THEME_MOD."admin/news/new-id.html", $page_data);
		}

		return ob_get_clean();
	}

	private function news_list(){

		$sql = "SELECT COUNT(*) FROM `mcr_news`";
		$page = "?mode=admin&do=news";

		if(isset($_GET['search']) && !empty($_GET['search'])){
			$search = $this->db->safesql(urldecode($_GET['search']));
			$sql = "SELECT COUNT(*) FROM `mcr_news` WHERE title LIKE '%$search%'";
			$search = $this->db->HSC(urldecode($_GET['search']));
			$page .= "&search=$search";
		}

		if(isset($_GET['sort']) && !empty($_GET['sort'])){
			$page .= '&sort='.$this->db->HSC(urlencode($_GET['sort']));
		}

		$query = $this->db->query($sql);

		$ar = @$this->db->fetch_array($query);

		$data = array(
			"PAGINATION" => $this->core->pagination($this->cfg->pagin['adm_news'], $page.'&pid=', $ar[0]),
			"NEWS" => $this->news_array()
		);

		return $this->core->sp(MCR_THEME_MOD."admin/news/new-list.html", $data);
	}

	private function delete(){
		if(!$this->core->is_access('sys_adm_news_delete')){ $this->core->notify($this->core->lng["e_msg"], $this->core->lng['e_403'], 2, '?mode=admin&do=news'); }

		if($_SERVER['REQUEST_METHOD']!='POST'){ $this->core->notify($this->core->lng["e_msg"], $this->core->lng['e_hack'], 2, '?mode=admin&do=news'); }
			
		$list = @$_POST['id'];

		if(empty($list)){ $this->core->notify($this->core->lng["e_msg"], $this->lng['news_not_selected'], 2, '?mode=admin&do=news'); }

		$list = $this->core->filter_int_array($list);

		$list = array_unique($list);

		$list = $this->db->safesql(implode(", ", $list));

		if(!$this->db->remove_fast("mcr_news", "id IN ($list)")){ $this->core->notify($this->core->lng["e_msg"], $this->core->lng["e_sql_critical"], 2, '?mode=admin&do=news'); }

		$count1 = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);

		// Лог действия
		$this->db->actlog($this->lng['log_del_news']." $list ".$this->lng['log_news'], $this->user->id);

		$this->core->notify($this->core->lng["e_success"], $this->lng['news_del_elements']." $count1", 3, '?mode=admin&do=news');

	}

	private function categories($selected=1){
		$selected = intval($selected);
		$query = $this->db->query("SELECT id, title FROM `mcr_news_cats` ORDER BY title ASC");

		if(!$query || $this->db->num_rows($query)<=0){

			$data = array(
				"ID" => 1,
				"TITLE" => $this->lng['news_wo_cats'],
				"SELECTED" => 'selected disabled',
			);

			return $this->core->sp(MCR_THEME_MOD."admin/news/cid-list-id.html", $data);
		}

		ob_start();

		while($ar = $this->db->fetch_assoc($query)){
			$data = array(
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"SELECTED" => ($selected==intval($ar['id'])) ? 'selected' : '',
			);

			echo $this->core->sp(MCR_THEME_MOD."admin/news/cid-list-id.html", $data);
		}

		return ob_get_clean();
	}

	private function is_fill_title($title) {
		if (!$title) {
			$this->core->notify(
				$this->core->lng["e_msg"], 
				$this->lng['news_title_is_null'],
				2
			);
		}
	}

	private function get_preview($title='', $text='', $category='', $cid=0, $vote=0){
		$data = array(
			"TITLE" => $this->db->HSC($title),
			"TEXT" => $text,
			"CATEGORY" => $this->db->HSC($category),
			"CID" => intval($cid),
			"LIKES" => (!$vote) ? '' : $this->core->sp(MCR_THEME_MOD."admin/news/new-preview-likes.html"),
		);

		return $this->core->sp(MCR_THEME_MOD."admin/news/new-preview.html", $data);
	}

	private function publish_time() {
		$time = new DateTime();

		return $time->format('d.m.Y H:i:s');
	}

	function checktime($hour, $min, $sec) {
		if ($hour < 0 || $hour > 23 || !is_numeric($hour)) {
			return false;
		}
		if ($min < 0 || $min > 59 || !is_numeric($min)) {
			return false;
		}
		if ($sec < 0 || $sec > 59 || !is_numeric($sec)) {
			return false;
		}
		return true;
	}

	private function add(){
		if(!$this->core->is_access('sys_adm_news_add')){ $this->core->notify($this->core->lng["e_msg"], $this->core->lng['e_403'], 2, '?mode=admin&do=news'); }

		$bc = array(
			$this->lng['mod_name'] => ADMIN_URL."",
			$this->lng['news'] => ADMIN_URL."&do=news",
			$this->lng['news_add'] => ADMIN_URL."&do=news&op=add",
		);

		$this->core->bc = $this->core->gen_bc($bc);
		$categories	= $this->categories();
		// TODO: News image
		$title = $text = $votes = $discuses = $attached = $img = $preview = $hidden = '';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			// TITLE
			$title = (empty(trim(@$_POST['title'])))?false:$this->db->safesql(@$_POST['title']);
			$this->is_fill_title( $title );

			$category_id = intval(@$_POST['cid']);
			$check_cid = $this->db->query("SELECT title FROM `mcr_news_cats` WHERE id='$category_id'");
			if (!$check_cid || $this->db->num_rows($check_cid) <= 0) {
				$this->core->notify($this->core->lng["e_msg"], $this->lng['news_e_cat_not_exist'], 2);
			}

			$vote = (intval(@$_POST['vote']) == 1)?true:false;
			$discus	= (intval(@$_POST['discus']) == 1)?true:false;
			$attach	= (intval(@$_POST['attach']) == 1)?true:false;

			// NEWS CONTENT
			$text = $this->db->safesql(htmLawed(trim(@$_POST['text'])));
			// NEWS IS HIDDEN
			$hidden	= (intval(@$_POST['hidden'])===1)?true:false;

			if (isset($_POST['preview'])) {
				$cid_ar	= $this->db->fetch_assoc($check_cid);
				$title_preview = $this->db->HSC(trim(@$_POST['title']));
				$text_preview = $this->db->HSC(trim(@$_POST['text']));

				$preview = $this->get_preview($title_preview, $text_preview, $cid_ar['title'], $category_id, $vote);
			} else {
				$new_data = array(
					"planed_news" => (@$_POST['planed_publish']=='on')?true:false,
					"time_last" => time(),
					"uid_last" => $this->user->id
				);
				$data = $this->db->safesql(json_encode($new_data));

				$publish_date = @$_POST['publish_time'];
				list($date, $time) = explode(' ', $publish_date);
				list($day, $mouth, $year) = explode('.', $date);
				list($hour, $minute, $second) = explode(':', $time);
				$time = new DateTime();

				$n = array(
					'category_id' => $category_id,
					'title' => $title,
					'news_text' => $text,
					'vote' => (!$vote)?0:1,
					'discus' => (!$discus)?0:1,
					'attach' => (!$attach)?0:1,
					'img' => $img,
					'user_id' => $this->user->id,
					'date' => (@$_POST['planed_publish']=='on')?(
						(checkdate($mouth, $day, $year)
						 && $this->checktime($hour, $minute, $second))?(
							$year.'-'.$mouth.'-'.$day.' '.$hour.':'.$minute.':'.$second
						):(false)
					):(
						$time->format('Y-m-d H:i:s')
					),
					'data' => $data,
					'hidden' => (!$hidden)?0:1
				);

				if (!$n['date']) {
					$this->core->notify(
						$this->core->lng["e_msg"],
						$this->lng['news_add_time_error'],
						2
					);
				}

				$create_news = "
					INSERT INTO `mcr_news`
						(`cid`, `title`, `text_html`, `vote`, `discus`, `attach`, `date`, `img`, `uid`, `data`, `hidden`)
					VALUES
						('{$n['category_id']}', '{$n['title']}', '{$n['news_text']}', '{$n['vote']}', '{$n['discus']}', '{$n['attach']}', '{$n['date']}', '{$n['img']}', '{$n['user_id']}', '{$n['data']}', '{$n['hidden']}')
				";
				if (!$this->db->query($create_news)) {$this->core->notify(
					$this->core->lng["e_msg"],
					$this->core->lng["e_sql_critical"],
					2
				); }

				$id = $this->db->insert_id();

				// Последнее обновление пользователя
				$this->db->update_user($this->user);
				// Лог действия
				$this->db->actlog(
					$this->lng['log_add_news']." #$id ".$this->lng['log_news'],
					$this->user->id
				);
				$this->core->notify(
					$this->core->lng["e_success"],
					$this->lng['news_add_success'],
					3,
					'?mode=admin&do=news'
				);
			}
		}

		$result = array(
			"PAGE" => $this->lng['news_add_page_name'],
			"TITLE" => $this->db->HSC($title),
			"CATEGORIES" => $categories,
			"DATE" => $this->publish_time(),
			"TEXT" => $text,
			"VOTE" => $votes,
			"DISCUS" => $discuses,
			"ATTACH" => $attached,
			"HIDDEN" => $hidden,
			"BUTTON" => $this->lng['news_add_btn'],
			"PREVIEW" => $preview,
		);

		return $this->core->sp(MCR_THEME_MOD."admin/news/new-add.html", $result);
	}

	private function edit(){
		if(!$this->core->is_access('sys_adm_news_edit')){ $this->core->notify($this->core->lng["e_msg"], $this->core->lng['e_403'], 2, '?mode=admin&do=news'); }

		$id = intval($_GET['id']);
		$preview = '';

		$query = $this->db->query("
			SELECT `cid`, `title`, `text_html`, `vote`, `discus`, `attach`, `date`, `data`, `hidden`
			FROM `mcr_news`
			WHERE id='$id'
		");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify(
				$this->core->lng["e_msg"],
				$this->core->lng["e_sql_critical"],
				2,
				'?mode=admin&do=news'
			);
		}

		$ar = $this->db->fetch_assoc($query);

		$categories	= $this->categories($ar['cid']);
		$title = $this->db->HSC($ar['title']);
		$text = $this->db->HSC($ar['text_html']);
		$votes = (intval($ar['vote'])===1) ? 'checked' : '';
		$discuses = (intval($ar['discus'])===1)?'checked':'';
		$attached = (intval($ar['attach'])===1)?'checked':'';
		$hiddened = (intval($ar['hidden'])===1)?'checked':'';
		$date = new DateTime($ar['date']);
		$data = json_decode($ar['data']);

		$bc = array(
			$this->lng['mod_name'] => ADMIN_URL."",
			$this->lng['news'] => ADMIN_URL."&do=news",
			$this->lng['news_edit'] => ADMIN_URL."&do=news&op=edit&id=$id"
		);

		$this->core->bc = $this->core->gen_bc($bc);

		if($_SERVER['REQUEST_METHOD']=='POST'){
			// TITLE
			$title = (empty(trim(@$_POST['title'])))?false:$this->db->safesql(@$_POST['title']);
			$this->is_fill_title( $title );

			$category_id = intval(@$_POST['cid']);
			$check_cid = $this->db->query("SELECT title FROM `mcr_news_cats` WHERE id='$category_id'");
			if (!$check_cid || $this->db->num_rows($check_cid) <= 0) {
				$this->core->notify($this->core->lng["e_msg"], $this->lng['news_e_cat_not_exist'], 2);
			}

			$publish_date = @$_POST['publish_time'];
			list($date, $time) = explode(' ', $publish_date);
			list($day, $mouth, $year) = explode('.', $date);
			list($hour, $minute, $second) = explode(':', $time);
			$time = new DateTime();

			$vote = (intval(@$_POST['vote'])===1)?1:0;
			$discus	= (intval(@$_POST['discus'])===1)?1:0;
			$attach	= (intval(@$_POST['attach'])===1)?1:0;
			// NEWS IS HIDDEN
			$hidden	= (intval(@$_POST['hidden'])===1)?1:0;
			// NEWS CONTENT
			$updated_text = $this->db->safesql(htmLawed(trim(@$_POST['text'])));
			// NEWS PUBLISH DATE
			$publish_date = (@$_POST['planed_publish']=='on')?(
				(checkdate($mouth, $day, $year)
				 && $this->checktime($hour, $minute, $second))?(
					$year.'-'.$mouth.'-'.$day.' '.$hour.':'.$minute.':'.$second
				):(false)
			):(
				$time->format('Y-m-d H:i:s')
			);

			if (!$publish_date) {
				$this->core->notify(
					$this->core->lng["e_msg"],
					$this->lng['news_add_time_error'],
					2
				);
			}

			if (isset($_POST['preview'])) {
				$cid_ar = $this->db->fetch_assoc($check_cid);
				$preview = $this->get_preview($title, $text, $cid_ar['title'], $category_id, $vote);
			} else {
				$new_data = array(
					"planed_news" => (@$data->planed_news
                        || @$_POST['planed_publish']=='on')?true:false,
					"time_last" => time(),
					"uid_last" => $this->user->id
				);
				$new_data = $this->db->safesql(json_encode($new_data));

				$updated_news = "
					UPDATE `mcr_news`
					SET 
						`cid`='$category_id', 
						`title`='$title', 
						`text_html`='$updated_text',
						`vote`='$vote', 
						`discus`='$discus', 
						`attach`='$attach', 
						`hidden`='$hidden', 
						`date`='$publish_date',
						`data`='$new_data'
					WHERE 
						`id`='$id'
				";
				if (!$this->db->query($updated_news)) {
					$this->core->notify(
						$this->core->lng["e_msg"],
						$this->core->lng["e_sql_critical"].': '.mysqli_error($this->db->obj),
						2,
						'?mode=admin&do=news&op=edit&id='.$id
					);
				}

				// Последнее обновление пользователя
				$this->db->update_user($this->user);
				// Лог действия
				$this->db->actlog($this->lng['log_edit_news']." #$id ".$this->lng['log_news'], $this->user->id);
				$this->core->notify($this->core->lng["e_success"], $this->lng['news_edit_success'], 3, '?mode=admin&do=news');
			}
		}

		$result = array(
			"PAGE" => $this->lng['news_edit_page_name'],
			"CATEGORIES" => $categories,
			"TITLE" => $title,
			"TEXT" => $text,
			"DATE" => $date->format('d.m.Y H:i:s'),
			"VOTE" => $votes,
			"DISCUS" => $discuses,
			"ATTACH" => $attached,
			"HIDDEN" => $hiddened,
			"BUTTON" => $this->lng['news_edit_btn'],
			"PREVIEW" => $preview,
		);

		return $this->core->sp(MCR_THEME_MOD."admin/news/new-add.html", $result);
	}

	public function content(){

		$op = (isset($_GET['op'])) ? $_GET['op'] : 'list';
		$content = '';

		switch($op){
			case 'add':		$content = $this->add(); break;
			case 'edit':	$content = $this->edit(); break;
			case 'delete':	$this->delete(); break;

			default:		$content = $this->news_list(); break;
		}

		return $content;
	}
}

?>