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

		if (!$this->core->is_access('sys_adm_blocks')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'));
		}

		$bc = [
			$this->l10n->gettext('mod_name') => ADMIN_URL,
			$this->l10n->gettext('blocks') => ADMIN_URL."&do=blocks"
		];

		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/blocks/header.phtml");
	}

	private function block_array()
	{
		$list = $this->filter_folder(scandir(MCR_CONF_PATH.'blocks'));

		if (!is_array($list) || count($list) <= 0) {
			return $this->core->sp(MCR_THEME_MOD."admin/blocks/block-none.phtml");
		}

		ob_start();

		foreach ($list as $id => $name) {

			include(MCR_CONF_PATH.'blocks/'.$name.'.php');

			$page_data = [
				"STATUS" => (@$cfg['ENABLE'])
					? 'icon_status_on'
					: 'icon_status_off',
				"NAME" => $this->db->HSC($name),
				"TITLE" => $this->db->HSC(@$cfg['TITLE']),
				"AUTHOR" => $this->db->HSC(@$cfg['AUTHOR']),
				"VERSION" => $this->db->HSC(@$cfg['VERSION']),
			];

			echo $this->core->sp(MCR_THEME_MOD."admin/blocks/block-id.phtml", $page_data);
		}

		return ob_get_clean();
	}

	private function filter_folder($array)
	{
		$filtered = [];

		foreach ($array as $key => $value) {
			if ($value == '..' || $value == '.') {
				continue;
			}
			if (is_dir(MCR_SIDE_PATH.$value)) {
				continue;
			}
			if (!file_exists(MCR_CONF_PATH.'blocks/'.$value)) {
				continue;
			}

			$expl = explode('.', $value);

			if (count($expl) != 2 || !isset($expl[1]) || $expl[1] != 'php') {
				continue;
			}

			$filtered[] = $expl[0];
		}

		return $filtered;
	}

	private function block_list()
	{
		$data = [
			"BLOCKS" => $this->block_array()
		];

		return $this->core->sp(MCR_THEME_MOD."admin/blocks/block-list.phtml", $data);
	}

	private function check_update($cfg)
	{
		if (empty($cfg['UPDATER'])) {
			return $this->l10n->gettext('block_not_check');
		}

		$json = file_get_contents($cfg['UPDATER']);

		if (!$json) {
			return $this->l10n->gettext('block_not_check');
		}

		if (!isset($json['version'])) {
			return $this->l10n->gettext('block_not_check');
		}
		if (!isset($json['url'])) {
			return $this->l10n->gettext('block_not_check');
		}

		if ($cfg['VERSION'] == $json['version']) {
			return $this->l10n->gettext('block_last_version');
		}

		return '(<a href="'.$this->db->HSC($json['url']).'" target="_blank" title="'.$this->l10n->gettext('block_download').'">'.$this->l10n->gettext('block_new_version').' '.$this->db->HSC($json['version']).'</a>)';
	}

	private function edit()
	{
		if (!$this->core->is_access('sys_adm_blocks_edit')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('error_403'), 2, '?mode=admin&do=blocks');
		}

		$name = @$_GET['id'];

		if (!file_exists(MCR_CONF_PATH.'blocks/'.$name.'.php')) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('block_cfg_not_found'), 2, '?mode=admin&do=blocks');
		}

		require(MCR_CONF_PATH.'blocks/'.$name.'.php');

		if (!$this->core->check_cfg_block($cfg)) {
			$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('block_cfg_incorrect'), 2, '?mode=admin&do=blocks');
		}

		$bc = [
			$this->l10n->gettext('mod_name') => ADMIN_URL."",
			$this->l10n->gettext('blocks') => ADMIN_URL."&do=blocks",
			$this->l10n->gettext('block_edit') => ADMIN_URL."&do=blocks&op=edit&id=$name"
		];

		$this->core->bc = $this->core->gen_bc($bc);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$cfg['ENABLE'] = (intval(@$_POST['status']) == 1)
				? true
				: false;
			$cfg['UPDATES'] = (intval(@$_POST['updates']) == 1)
				? true
				: false;
			$cfg['UPDATER'] = $this->core->safestr(@$_POST['updater']);
			$cfg['POSITION'] = intval(@$_POST['position']);

			if (!$this->cfg->savecfg($cfg, 'blocks/'.$name.'.php', 'cfg')) {
				$this->core->notify($this->l10n->gettext('error_message'), $this->l10n->gettext('block_cfg_unsave'), 3, '?mode=admin&do=blocks');
			}

			// Лог действия
			$this->db->actlog($this->l10n->gettext('log_edit_block')." #$name ".$this->l10n->gettext('log_mod'), $this->user->id);

			$this->core->notify($this->l10n->gettext('error_success'), $this->l10n->gettext('block_edit_success'), 3, '?mode=admin&do=blocks');
		}

		$update_result = $this->check_update($cfg);

		$data = [
			"PAGE" => $this->l10n->gettext('block_edit_page_name'),
			"STATUS" => ($cfg['ENABLE']) ? 'selected' : '',
			"TITLE" => $this->db->HSC($cfg['TITLE']),
			"POSITION" => intval($cfg['POSITION']),
			"DESC" => $this->db->HSC($cfg['DESC']),
			"AUTHOR" => $this->db->HSC($cfg['AUTHOR']),
			"SITE" => $this->db->HSC($cfg['SITE']),
			"EMAIL" => $this->db->HSC($cfg['EMAIL']),
			"VERSION" => $this->db->HSC($cfg['VERSION']),
			"UPDATE_URL" => $this->db->HSC($cfg['UPDATER']),
			"UPDATE_CHECK" => ($cfg['UPDATES']) ? 'selected' : '',
			"UPDATE_RESULT" => $update_result,
			"BUTTON" => $this->l10n->gettext('block_edit_btn')
		];

		return $this->core->sp(MCR_THEME_MOD."admin/blocks/block-form.phtml", $data);
	}

	public function content()
	{
		$op = (isset($_GET['op'])) ? $_GET['op'] : 'list';

		switch ($op) {
			case 'edit':
				$content = $this->edit();
				break;

			default:
				$content = $this->block_list();
				break;
		}

		return $content;
	}
}

?>