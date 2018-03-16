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

if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

require_once MCR_LIBS_PATH.'htmLawed/htmLawed.php';

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

		if (!$this->core->is_access('sys_adm_news')) {
			$this->core->notify($this->l10n->gettext('403'), $this->l10n->gettext('e_403'));
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('news') => ADMIN_URL."&do=news"
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/news/header.html");
	}

	private function categories($selected = 1)
	{
		$selected = intval($selected);
		$query = $this->db->query("SELECT id, title FROM `mcr_news_cats` ORDER BY title ASC");

		if (!$query || $this->db->num_rows($query) <= 0) {
			$data = [
				"ID" => 1,
				"TITLE" => $this->l10n->gettext('news_wo_cats'),
				"SELECTED" => 'selected disabled',
			];

			return $this->core->sp(MCR_THEME_MOD."admin/news/select-options.html", $data);
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$data = [
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"SELECTED" => ($selected == intval($ar['id'])) ? 'selected' : '',
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/news/select-options.html", $data);
		}

		return ob_get_clean();
	}

	private function user_groups($user_group = -1)
	{
		$id_user_group = intval($user_group);
		$query = $this->db->query("SELECT `id`, `title` FROM `mcr_groups` ORDER BY `id` ASC");

		if (!$query || $this->db->num_rows($query) <= 0) {

			$data = [
				"ID" => -1,
				"TITLE" => $this->l10n->gettext('news_wo_cats'),
				"SELECTED" => 'selected disabled',
			];

			return $this->core->sp(MCR_THEME_MOD."admin/news/select-options.html", $data);
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$data = [
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"SELECTED" => ($id_user_group == intval($ar['id'])) ? 'selected' : '',
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/news/select-options.html", $data);
		}

		return ob_get_clean();
	}

	private function get_preview($title = '', $text = '', $category = '', $cid = 0, $vote = 0, $planed_publish = false)
	{
		$data = [
			"TITLE" => $this->db->HSC($title),
			"PLANED_PUBLISH" => $this->l10n->parse_date($planed_publish, ['date'=>'date_create','time'=>'time_create']),
			"TEXT" => $text,
			"CATEGORY" => $this->db->HSC($category),
			"CID" => intval($cid),
			"LIKES" => (!$vote)
				? ''
				: $this->core->sp(MCR_THEME_MOD."admin/news/new-preview-likes.html"),
		];

		return $this->core->sp(MCR_THEME_MOD."admin/news/new-preview.html", $data);
	}

	private function is_fill_title($title)
	{
		if (!$title) {
			$this->core->notify(
				$this->l10n->gettext('error_msg'),
				$this->l10n->gettext('news_title_is_null'),
				2
			);
		}
	}

	function checktime($hour, $min, $sec)
	{
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

	private function get_short_information($article)
	{
		$short_information = '';

		if ($article['date'] > time()) {
			$short_information .= '<i class="fa fa-calendar" data-toggle="tooltip" data-placement="top" title="'.$this->l10n->gettext('planed_article').'"></i>';
		}

		if ($article['hidden'] == 1) {
			$short_information .= '<i class="fa fa-eye-slash" data-toggle="tooltip" data-placement="top" title="'.$this->l10n->gettext('hidden_article').'"></i>';
		}

		if ($article['attach'] == 1) {
			$short_information .= '<i class="fa fa-paperclip" data-toggle="tooltip" data-placement="top" title="'.$this->l10n->gettext('fixed_article').'"></i>';
		}

		return $short_information;
	}

	private function get_avatar($user, $rank)
	{
		if (!is_array($user)) {
			return null;
		}

		$is_skin = (intval($user[$rank.'_skin']) == 1)
			? true
			: false;
		$is_cloak = (intval($user[$rank.'_cloak']) == 1)
			? true
			: false;

		$is_girl = (intval($user[$rank.'_gender']) == 1)
			? 'default_female'
			: 'default';

		$avatar = ($is_skin || $is_cloak)
			? $this->db->HSC($user[$rank.'_login'])
			: $is_girl;

		return UPLOAD_URL.'skins/interface/'.$avatar.'_mini.png?'.mt_rand(1000, 9999);
	}

	private function news_array()
	{
		$query = $this->db->query("
			SELECT 
				`n`.`id`, `n`.`cid`, `n`.`title`,
				`n`.`date`, `n`.`hidden`, `n`.`attach`,
				`n`.`data`, `n`.`uid`, `n`.`img`,
				
				`c`.`title` AS `category`,
				`c`.`description` AS `category_description`,
				
				`u`.`login` AS `author_login`, `u`.`is_skin` AS `author_skin`, 
				`u`.`is_cloak` AS `author_cloak`, `u`.`gender` AS `author_gender`,
				
				`l`.`date` AS `edit_date`,
				
				`ue`.`login` AS `editor_login`, `ue`.`is_skin` AS `editor_skin`, 
				`ue`.`is_cloak` AS `editor_cloak`, `ue`.`gender` AS `editor_gender`
			FROM `mcr_news` AS `n`
			LEFT JOIN `mcr_news_cats` AS `c` 
				ON `c`.id=`n`.`cid` 
			LEFT JOIN `mcr_users` AS `u` 
				ON `u`.id=`n`.`uid`
			LEFT JOIN `mcr_logs_of_edit` AS `l` 
				ON `l`.`things`=`n`.`id` AND `l`.`table`='mcr_news'
			LEFT JOIN `mcr_users` AS `ue` 
				ON `ue`.id=`l`.`editor`
		");
		if (!$query || $this->db->num_rows($query) <= 0) {
			return null;
		}

		ob_start();

		while ($ar = $this->db->fetch_assoc($query)) {
			$author = $this->l10n->gettext('article_writer').": <a href='/?mode=users&uid={$ar['author_login']}'>{$ar['author_login']}</a>";
			$editor = $this->l10n->gettext('article_editor').": <a href='/?mode=users&uid={$ar['editor_login']}'>{$ar['editor_login']}</a>";

			$author_avatar = $this->get_avatar($ar, 'author');
			$editor_avatar = $this->get_avatar($ar, 'editor');

			$avatars = "
				<img class='user_a-news_avatar' width='18px' src='$author_avatar' data-toggle='tooltip' data-placement='top' title='{$this->l10n->gettext('article_writer')}'>
			".(($ar['editor_login'])
					? ("
				<img class='user_e-news_avatar' width='18px' src='$editor_avatar' data-toggle='tooltip' data-placement='top' title='{$this->l10n->gettext('article_editor')}'>
			")
					: null);

			$page_data = [
				"ID" => intval($ar['id']),
				"CID" => intval($ar['cid']),
				"TITLE" => $this->db->HSC($ar['title']),
				"CATEGORY" => $this->db->HSC($ar['category']),
				"DESCRIPTION" => $this->db->HSC($ar['category_description']),
				"DATE" => ($this->l10n->localize($ar['date'], 'unixtime', $this->l10n->get_date_format())." {$this->l10n->gettext('in')} ".$this->l10n->localize($ar['date'], 'unixtime', $this->l10n->get_time_format())),
				"INFORMATION" => $this->get_short_information($ar),
				"IMG" => (trim($ar['img']))
					? trim($ar['img'])
					: '/themes/default/img/cacke.128.png',
				"AUTHORS" => "$author".(($ar['editor_login'])
						? (", $editor")
						: null),
				"USER_AVATARS" => $avatars,
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/news/new-id.html", $page_data);
		}

		return ob_get_clean();
	}

	private function news_list()
	{
		$data = [
			"NEWS" => $this->news_array()
		];

		return $this->core->sp(MCR_THEME_MOD."admin/news/new-list.html", $data);
	}

	private function add()
	{
		if (!$this->core->is_access('sys_adm_news_add')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=news');
		}

		$bc = [
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL."",
			$this->l10n->gettext('module_news') => ADMIN_URL."&do=news",
			$this->l10n->gettext('news_add') => ADMIN_URL."&do=news&op=add",
		];

		$this->core->bc = $this->core->gen_bc($bc);
		$categories = $this->categories();
		$user_groups = $this->user_groups();
		// TODO: News image
		$title = $text = $vote = $discus = $attach = $img = $preview = $hidden = $planed_publish = '';
		$n = [];
		$time = time();

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// TITLE
			$title = (empty(trim(@$_POST['title']))) ? false : $this->db->safesql(@$_POST['title']);
			$this->is_fill_title($title);

			$category_id = intval(@$_POST['cid']);
			$check_cid = $this->db->query("SELECT `title` FROM `mcr_news_cats` WHERE id='$category_id'");
			if (!$check_cid || $this->db->num_rows($check_cid) <= 0) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('news_e_cat_not_exist'), 2);
			}

			$vote = (intval(@$_POST['vote']) == 1) ? true : false;
			$discus = (intval(@$_POST['discus']) == 1) ? true : false;
			$attach = (intval(@$_POST['attach']) == 1) ? true : false;
			$hidden = (intval(@$_POST['hidden']) == 1) ? true : false;
			$planed_publish = (@$_POST['planed_publish'] == 1) ? 'checked' : '';
			$text = htmLawed(trim(@$_POST['text']));

			// NEWS CONTENT
			$news_text = $this->db->safesql($text);
			// NEWS DATA
			$new_data = [
				"planed_news" => (@$_POST['planed_publish'] == 1) ? true : false,
				"close_comments" => (@$_POST['closed_comments'] == 1) ? true : false,

				"time_when_close_comments" => (@$_POST['date_cs']) ? @$_POST['publish_time'] : false,
			];
			$data = $this->db->safesql(json_encode($new_data));
			$date = (@$_POST['planed_publish'] == 1)
				? (new DateTime(@$_POST['publish_time']))->getTimestamp()
				: $time;

			// Prepare data for create news
			$n = [
				'category_id' => $category_id,
				'title' => $title,
				'news_text' => $news_text,
				'vote' => (!$vote) ? 0 : 1,
				'discus' => (!$discus) ? 0 : 1,
				'attach' => (!$attach) ? 0 : 1,
				'img' => $img,
				'user_id' => $this->user->id,
				'date' => $date,
				'data' => $data,
				'hidden' => (!$hidden) ? 0 : 1
			];

			if (!$n['date']) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('news_add_time_error'), 2, '?mode=admin&do=news&op=add');
			}

			if (isset($_POST['preview'])) {
				$cid_ar = $this->db->fetch_assoc($check_cid);

				$preview = $this->get_preview($n['title'], str_replace('{READMORE}', '<hr>', $text), $cid_ar['title'], $category_id, $vote, $n['date']);
			} else {
				$create_news = "
					INSERT INTO `mcr_news`
						(`cid`, `title`, `text_html`, `vote`, `discus`, `attach`, `date`, `img`, `uid`, `data`, `hidden`)
					VALUES
						('{$n['category_id']}', '{$n['title']}', '{$n['news_text']}', '{$n['vote']}', '{$n['discus']}', '{$n['attach']}', '{$n['date']}', '{$n['img']}', '{$n['user_id']}', '{$n['data']}', '{$n['hidden']}')
				";
				if (!$this->db->query($create_news)) {
					$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=news&op=add');
				}

				$id = $this->db->insert_id();

				// Последнее обновление пользователя
				$this->db->update_user($this->user);
				// Лог действия
				$this->db->actlog($this->l10n->gettext('log_add_news')." #$id ".$this->l10n->gettext('log_news'), $this->user->id);
				$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('news_add_success'), 3, '?mode=admin&do=news');
			}
		}

		return $this->core->sp(MCR_THEME_MOD."admin/news/new-add.html", [
			"PAGE" => $this->l10n->gettext('news_add_page_name'),
			"TITLE" => $this->db->HSC($title),
			"CATEGORIES" => $categories,
			"USER_GROUPS" => $user_groups,
			"PLANED_PUBLISH" => $planed_publish,
			"DATE" => (empty($n)) ? date('d.m.Y H:i:s', $time) : date('d.m.Y H:i:s', $n['date']),
			"TEXT" => $text,
			"VOTE" => ($vote) ? 'checked' : '',
			"DISCUS" => ($discus) ? 'checked' : '',
			"ATTACH" => ($attach) ? 'checked' : '',
			"HIDDEN" => ($hidden) ? 'checked' : '',
			"BUTTON" => $this->l10n->gettext('news_add_btn'),
			"PREVIEW" => $preview,
		]);
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_news_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=news');
		}

		$id = intval($_GET['id']);
		$preview = '';

		$query = $this->db->query("
			SELECT `cid`, `title`, `text_html`, `vote`, `discus`, `attach`, `date`, `data`, `hidden`
			FROM `mcr_news`
			WHERE id='$id'
		");
		if (!$query || $this->db->num_rows($query) <= 0) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=news');
		}

		$ar = $this->db->fetch_assoc($query);

		// TODO: News image
		$categories = $this->categories($ar['cid']);
		$title = $this->db->HSC($ar['title']);
		$text = $this->db->HSC($ar['text_html']);
		$votes = (intval($ar['vote']) === 1) ? 'checked' : '';
		$discuses = (intval($ar['discus']) === 1) ? 'checked' : '';
		$attached = (intval($ar['attach']) === 1) ? 'checked': '';
		$hiddened = (intval($ar['hidden']) === 1) ? 'checked': '';
		$data = json_decode($ar['data'], true);

		$bc = [
			$this->l10n->gettext('mod_name') => ADMIN_URL."",
			$this->l10n->gettext('news') => ADMIN_URL."&do=news",
			$this->l10n->gettext('news_edit') => ADMIN_URL."&do=news&op=edit&id=$id"
		];
		$this->core->bc = $this->core->gen_bc($bc);

		$time = time();

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// TITLE
			$title = (empty(trim(@$_POST['title'])))
				? false
				: $this->db->safesql(@$_POST['title']);
			$this->is_fill_title($title);

			$category_id = intval(@$_POST['cid']);
			$check_cid = $this->db->query("SELECT title FROM `mcr_news_cats` WHERE id='$category_id'");
			if (!$check_cid || $this->db->num_rows($check_cid) <= 0) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('news_e_cat_not_exist'), 2);
			}

			$vote = (intval(@$_POST['vote']) === 1) ? 1 : 0;
			$discus = (intval(@$_POST['discus']) === 1) ? 1 : 0;
			$attach = (intval(@$_POST['attach']) === 1) ? 1 : 0;
			// NEWS IS HIDDEN
			$hidden = (intval(@$_POST['hidden']) === 1) ? 1 : 0;
			// NEWS CONTENT
			$updated_text = $this->db->safesql(htmLawed(trim(@$_POST['text'])));
			// NEWS PUBLISH DATE
			$publish_date = (!empty(@$_POST['planed_publish']) && @$_POST['planed_publish'] == 1)
				? (new DateTime(@$_POST['publish_time']))->getTimestamp()
				: $time;

			if (!$publish_date) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('news_add_time_error'), 2, '?mode=admin&do=news&op=edit&id='.$id);
			}

			if (isset($_POST['preview'])) {
				$cid_ar = $this->db->fetch_assoc($check_cid);

				$preview = $this->get_preview($title, str_replace('{READMORE}', '<hr>', htmLawed(trim(@$_POST['text']))), $cid_ar['title'], $category_id, $vote, $publish_date);
			} else {
				$new_data = [
					"planed_news" => (@$_POST['planed_publish'] == 1)
						? true
						: false,
					"close_comments" => (@$_POST['closed_comments'] == 1)
						? true
						: false,
					"time_when_close_comments" => (@$_POST['date_cs'])
						? ($this->check_datetime(@$_POST['date_cs']))
						: (false),
				];
				$new_data = array_merge($data, $new_data);
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
					$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=news&op=edit&id='.$id);
				}

				$loe_row = "SELECT `id` FROM `mcr_logs_of_edit` WHERE `things`='$id' AND `table`='mcr_news'";
				$loe_row = $this->db->query($loe_row);
				if (!$loe_row || $this->db->num_rows($loe_row) <= 0) {
					$loe = "
						INSERT INTO `mcr_logs_of_edit` (`editor`, `things`, `table`, `date`)
						VALUES ('{$this->user->id}', '{$id}', 'mcr_news', '$publish_date')
					";
				} else {
					$loe = "
						UPDATE `mcr_logs_of_edit` 
						SET `editor`='{$this->user->id}', `things`='{$id}', `table`='mcr_news', `date`='$publish_date'
						WHERE `things`='$id' AND `table`='mcr_news'
					";
				}
				$this->db->query($loe);

				// Последнее обновление пользователя
				$this->db->update_user($this->user);
				// Лог действия
				$this->db->actlog($this->l10n->gettext('log_edit_news')." #$id ".$this->l10n->gettext('log_news'), $this->user->id);
				$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('news_edit_success'), 3, '?mode=admin&do=news');
			}
		}
		$date = intval($ar['date']);

		$result = [
			"PAGE" => $this->l10n->gettext('news_edit_page_name'),
			"CATEGORIES" => $categories,
			"TITLE" => $title,
			"TEXT" => $text,
			"PLANED_PUBLISH" => (@$data['planed_news'] || @$_POST['planed_publish'] == 1)
				? 'checked'
				: '',
			"DATE" => date('d.m.Y H:i:s', $date),
			"VOTE" => $votes,
			"DISCUS" => $discuses,
			"ATTACH" => $attached,
			"HIDDEN" => $hiddened,
			"BUTTON" => $this->l10n->gettext('news_edit_btn'),
			"PREVIEW" => $preview,
		];

		return $this->core->sp(MCR_THEME_MOD."admin/news/new-add.html", $result);
	}

	private function delete()
	{
		if (!$this->core->is_access('sys_adm_news_delete')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=news');
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$news_list = @$_POST['id'];
		} else {
			$news_list[] = @$_GET['id'];
		}

		if (empty($news_list)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('news_not_selected'), 2, '?mode=admin&do=news');
		}

		$news_list = $this->core->filter_int_array($news_list);
		$news_list = array_unique($news_list);
		$news_list = $this->db->safesql(implode(", ", $news_list));

		if (!$this->db->remove_fast("mcr_news", "id IN ($news_list)")) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_sql_critical'), 2, '?mode=admin&do=news');
		}

		$count1 = $this->db->affected_rows();

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_del_news')." $news_list ".$this->l10n->gettext('log_news'), $this->user->id);
		$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('news_del_elements')." $count1", 3, '?mode=admin&do=news');
	}

	public function content()
	{
		$op = (isset($_GET['op']))
			? $_GET['op']
			: 'list';
		$content = '';

		switch ($op) {
			case 'add': $content = $this->add(); break;
			case 'edit': $content = $this->edit(); break;
			case 'delete': $this->delete(); break;

			default: $content = $this->news_list(); break;
		}

		return $content;
	}
}