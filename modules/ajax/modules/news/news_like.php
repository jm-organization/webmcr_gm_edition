<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class submodule{
	private $core, $db, $cfg, $user, $l10n;

	public function __construct(core $core){
		$this->core	= $core;
		$this->db = $core->db;
		$this->cfg = $core->cfg;
		$this->user = $core->user;
		$this->l10n = $core->l10n;
	}

	public function content(){
		if ($_SERVER['REQUEST_METHOD']!='POST') { $this->core->js_notify($this->l10n->gettext('error_hack')); }
		if (!$this->core->is_access('sys_news_like')) { $this->core->js_notify($this->l10n->gettext('com_vote_perm')); }

		$nid = intval(@$_POST['nid']);
		$value = intval(@$_POST['value']);

		if ($value < 0 || $value > 1) { $this->core->js_notify($this->l10n->gettext('error_hack')); }

		$ar = $this->get_news_votes($nid);
		if (intval($ar['vote']) <= 0) { $this->core->js_notify($this->l10n->gettext('com_vote_disabled')); }

		$data = json_decode($ar['data'], true);

		if (isset($data['votes'])) {
			$votes = $data['votes'];
		} else {
			$votes = [ "likes" => 0, "dislikes" => 0 ];
		}

		$likes = intval($votes['likes']);
		$dislikes = intval($votes['dislikes']);
		$uid = (!$this->user->is_auth)?-1:$this->user->id;
		$old_value = isset($ar['value'])?$ar['value']:null;
		$time = time();

		if (is_null($old_value)) {
			if (!$this->db->query(
				"INSERT INTO `mcr_news_votes` (nid, uid, `value`, ip, `time`)
				VALUES ('$nid', '$uid', '$value', '{$this->user->ip}', $time)"
			)) $this->core->js_notify($this->l10n->gettext('error_sql_critical'));

			$likes = ($value === 1)?$likes+1:$likes;
			$dislikes = ($value === 0)?$dislikes+1:$dislikes;
		} elseif ($old_value != $value) {
			if (!$this->db->query(
				"UPDATE `mcr_news_votes`
				SET uid='$uid', `value`='$value', `time`=$time
				WHERE nid='$nid' AND uid='{$this->user->id}'
				LIMIT 1"
			)) $this->core->js_notify($this->l10n->gettext('error_sql_critical'));

			if ($value === 1) {
				$likes = (intval($old_value) === 1)?$likes:$likes+1;
				$dislikes = (intval($old_value) === 1)?$dislikes:$dislikes-1;
			} elseif ($value === 0) {
				$likes = (intval($old_value) === 0)?$likes:$likes-1;
				$dislikes = (intval($old_value) === 0)?$dislikes:$dislikes+1;
			}
		} else {
			if (!$this->db->query(
				"DELETE FROM `mcr_news_votes` WHERE `value`='$value' AND (nid='$nid' AND uid='{$this->user->id}')"
			)) $this->core->js_notify($this->l10n->gettext('error_sql_critical'));

			if ($value === 1) {
				$likes = (intval($old_value) === 1)?$likes-1:$likes+1;
				$dislikes = (intval($old_value) === 1)?$dislikes:$dislikes-1;
			} elseif ($value === 0) {
				$likes = (intval($old_value) === 0)?$likes:$likes-1;
				$dislikes = (intval($old_value) === 0)?$dislikes-1:$dislikes+1;
			}

			$data = json_decode($ar['data'], true);
			$data = array_merge($data, array('votes' => array(
				'likes' => $likes,
				'dislikes' => $dislikes
			)));
			$data = json_encode($data);
			if (!$this->db->query(
				"UPDATE `mcr_news` SET `data`='$data' WHERE `id`='$nid'"
			)) $this->core->js_notify($this->l10n->gettext('error_sql_critical'));
		}

		$news_votes = $this->get_news_votes($nid);

		$data = json_decode($news_votes['data'], true);
		$data = array_merge($data, array('votes' => array(
			'likes' => $likes,
			'dislikes' => $dislikes
		)));
		$data = json_encode($data);
		if (!$this->db->query(
			"UPDATE `mcr_news` SET `data`='$data' WHERE `id`='$nid'"
		)) $this->core->js_notify($this->l10n->gettext('error_sql_critical'));

		// Последнее обновление пользователя
		$this->db->update_user($this->user);
		// Лог действия
		$this->db->actlog($this->l10n->gettext('log_com_vote')." #$nid", $this->user->id);

		$data = array(
			'likes' => $likes,
			'dislikes' => $dislikes
		);
		$this->core->js_notify($this->l10n->gettext('com_vote_success'), $this->l10n->gettext('error_success'), true, $data);
	}

	private function get_news_votes($news_id) {
		$query = $this->db->query(
			"SELECT 
				`n`.`vote`, `n`.`data`, 
				
				`v`.`value`
			FROM `mcr_news` AS `n`
				
			LEFT JOIN `mcr_news_votes` AS `v`
				ON `v`.nid=`n`.id AND `v`.uid='{$this->user->id}'
				
			WHERE `n`.id='$news_id' AND (`v`.nid='$news_id' AND `v`.uid='{$this->user->id}')
			
			GROUP BY `v`.`value`"
		);

		if (!$query || $this->db->num_rows($query) <= 0) {
			$query = $this->db->query("SELECT `vote`, `data` FROM `mcr_news` WHERE `id`='$news_id'");

			return $this->db->fetch_assoc($query);
		}

		return $this->db->fetch_assoc($query);
	}
}