<?php

/**
 * @Created in JM Organization.
 * @Author: Magicmen
 *
 * @Date: 15.10.2017
 * @Time: 21:20
 *
 * @documentation:
 */
class l10n {
	public $core;

	public function __construct($core) {
		$this->core = $core;
	}

	public function translate($phrase) {
		$query = $this->get_language('ru_RU', false);

		while ($ar = $this->core->db->fetch_assoc($query)) {
			//$settings = json_decode($ar['settings'], true);
			$phrases = json_decode($ar['phrases'], true);

			foreach ($phrases as $key => $value) {
				if ($phrase == $key) return $value;
			}
		}

		return $phrase;
	}

	public function get_language($language = 'ru_RU', $is_all = true) {
		if ($is_all) {
			$sql = "SELECT `id`, `language`, `settings` FROM `mcr_l10n_languages`";
		} else {
			$sql = "
				SELECT 
					`id`, 
					`language`, 
					`settings`,
					`phrases`
				FROM `mcr_l10n_languages`
				WHERE `language`= '$language'
			";
		}

		return $this->core->db->query($sql);
	}

	public function get_phrase($phrase = '', $is_all = true) {
		// TODO: Showing all Phrases with values in admin panel.
	}
}