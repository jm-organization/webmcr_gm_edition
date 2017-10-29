<?php

/**
 * @Created in JM Organization.
 * @Author: Magicmen
 *
 * @Date: 16.10.2017
 * @Time: 12:45
 *
 * @documentation:
 */

require_once MCR_LIBS_PATH.'htmLawed/htmLawed.php';

class submodule {
	private $core, $db, $l10n, $user;

	public function __construct($core) {
        $this->core = $core;
        $this->db = $core->db;
        $this->l10n = $core->l10n;
        $this->user = $core->user;

        if(!$this->core->is_access('sys_adm_l10n')){ $this->core->notify('403'); }

        $bc = array(
            $this->l10n->gettext('module_admin-panel') => ADMIN_URL,
            $this->l10n->gettext('phrases') => ADMIN_URL."&do=l10n_phrases"
        );
        $this->core->bc = $this->core->gen_bc($bc);

        $this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/header.html");
    }

    protected function phrases_list($phrases) {
        ob_start();

        while ($phrase = $this->core->db->fetch_assoc($phrases)) {
            $language_title = json_decode($phrase['language_settings'])->title;

            $data = array(
                "ID" => $phrase['id'],
                "PHRASE" => $phrase['phrase_key'],
                "PHRASE_VALUE" => $phrase['phrase_value']
            );

            echo $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/phrase.html", $data);
        }

        return ob_get_clean();
	}

	protected function all_phrases() {
		$phrases = $this->l10n->get_phrases();
		$phrases_list = (isset($phrases))?$this->phrases_list($phrases):'';

		$data = array(
			"LANGUAGE_LOCALE" => $this->l10n->get_locale()->locale,
                        "PHRASES_LIST" => $phrases_list
		);

		return $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/phrases.html", $data);
	}
    
    protected function languages($select_language = 0) {
        $languages = $this->l10n->get_languages();
		
        ob_start();

		while ($language = $this->core->db->fetch_assoc($languages)) {
            
            $data = array(
                "ID" => $language['id'],
                "LANGUAGE" => json_decode($language['settings'])->title.' ('.$language['language'].')',
                "SELECT" => ($select_language == $language['id'])?'selected':'',
            );

            echo $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/languages.html", $data);
        }

        return ob_get_clean();
    }

    protected function add() {
        $bc = array(
            $this->l10n->gettext('module_admin-panel') => ADMIN_URL,
            $this->l10n->gettext('phrases') => ADMIN_URL."&do=l10n_phrases",
            $this->l10n->gettext('add-phrase') => ADMIN_URL."&do=l10n_phrases&op=add"
        );
        $this->core->bc = $this->core->gen_bc($bc);

        $validphrase = "/[a-zA-Z0-9_-]*/";

        if ($_SERVER['REQUEST_METHOD']=='POST') {
            $post = array(
                'language' => (intval(@$_POST['language']) >= 1)?intval(@$_POST['language']):false,
                'phrase_value' => htmLawed(@$_POST['phrase_value']),
                'phrase_key' => (preg_match($validphrase, @$_POST['phrase_key']))?@$_POST['phrase_key']:false,
            );

            if (!$post['phrase_key'] || !$post['language']) {
                $this->core->notify(
                    $this->l10n->gettext('error_message'),
                    $this->l10n->gettext('invalid_data'),
                    2,
                    '?mode=admin&do=l10n_phrases&op=add'
                );
            }

            $language = $this->db->safesql($post['language']);
            $phrase_key = $this->db->safesql($post['phrase_key']);
            $phrase_value = $this->db->safesql($post['phrase_value']);
            $add_phrase = "
                INSERT INTO `mcr_l10n_phrases`
                    (`language_id`, `phrase_key`, `phrase_value`)
                VALUES
                    ('{$language}', '{$phrase_key}', '{$phrase_value}')
            ";
            if (!$this->db->query($add_phrase)) { $this->core->notify(
                $this->l10n->gettext('error_message'),
                $this->l10n->gettext('error_sql_critical'),
                2,
                '?mode=admin&do=l10n_phrases&op=add'
            ); }

            $id = $this->db->insert_id();

            // Последнее обновление пользователя
            $this->db->update_user($this->user);
            // Лог действия
            $this->db->actlog(
                $this->l10n->gettext('log_add_phrase')." #$id ".$this->l10n->gettext('log_phrase'),
                $this->user->id
            );
            $this->core->notify(
                $this->l10n->gettext('success'),
                $this->l10n->gettext('success_add_phrase'),
                3,
                '?mode=admin&do=l10n_phrases'
            );
        }

        $data = array(
            'LANGUAGES' => $this->languages(),
            'PHRASE_KEY' => '',
            'PHRASE_VALUE' => '',
        );

        return $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/form-phrases.html", $data);
	}

	protected function edit() {
        $bc = array(
            $this->l10n->gettext('module_admin-panel') => ADMIN_URL,
            $this->l10n->gettext('phrases') => ADMIN_URL."&do=l10n_phrases",
            $this->l10n->gettext('edit-phrase') => ADMIN_URL."&do=l10n_phrases&op=edit"
        );
        $this->core->bc = $this->core->gen_bc($bc);

        $languages = $phrase_key = $phrase_value = '';
        $validphrase = "/[a-zA-Z0-9_-]*/";

        $phrase_id = (intval(@$_GET['phrase']) >= 1)?intval(@$_GET['phrase']):false;
        if (!$phrase_id) {
            $this->core->notify(
                $this->l10n->gettext('error_message'),
                $this->l10n->gettext('invalid_data'),
                2,
                '?mode=admin&do=l10n_phrases'
            );
        }
        $phrase = $this->l10n->get_phrases($phrase_id, false);
        
        if ($this->core->db->num_rows($phrase) == 1) {
            $p = $this->core->db->fetch_assoc($phrase);
            
            $languages = $this->languages($p['language_id']);
            $phrase_key = $p['phrase_key'];
            $phrase_value = $p['phrase_value'];
        }
        
        if ($_SERVER['REQUEST_METHOD']=='POST') {
            $post = array(
                'language' => (intval(@$_POST['language']) >= 1)?intval(@$_POST['language']):false,
                'phrase_value' => htmLawed(@$_POST['phrase_value']),
                'phrase_key' => (preg_match($validphrase, @$_POST['phrase_key']))?@$_POST['phrase_key']:false,
            );

            if (!$post['phrase_key'] || !$post['language']) {
                $this->core->notify(
                    $this->l10n->gettext('error_message'),
                    $this->l10n->gettext('invalid_data'),
                    2,
                    '?mode=admin&do=l10n_phrases&op=add'
                );
            }
            
            $language = $this->db->safesql($post['language']);
            $phrase_key = $this->db->safesql($post['phrase_key']);
            $phrase_value = $this->db->safesql($post['phrase_value']);
            $update_phrase = "
                UPDATE
                    `mcr_l10n_phrases`
                SET
                    `language_id`='{$language}',
                    `phrase_key`='{$phrase_key}',
                    `phrase_value`='{$phrase_value}'
                WHERE
                    `id`='$phrase_id'
            ";
            if (!$this->db->query($update_phrase)) { $this->core->notify(
                $this->l10n->gettext('error_message'),
                $this->l10n->gettext('error_sql_critical'),
                2,
                '?mode=admin&do=l10n_phrases&op=edit'
            ); }

            $id = $this->db->insert_id();

            // Последнее обновление пользователя
            $this->db->update_user($this->user);
            // Лог действия
            $this->db->actlog(
                $this->l10n->gettext('log_edit_phrase')." #$id ".$this->l10n->gettext('log_phrase'),
                $this->user->id
            );
            $this->core->notify(
                $this->l10n->gettext('success'),
                $this->l10n->gettext('success_edit_phrase'),
                3,
                '?mode=admin&do=l10n_phrases'
            );
        }
        
        $data = array(
            'LANGUAGES' => $languages,
            'PHRASE_KEY' => $phrase_key,
            'PHRASE_VALUE' => $phrase_value,
        );

        return $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/form-phrases.html", $data);

	}

	protected function delete() {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') { 
            $phrases = (intval(@$_GET['phrase']) >= 1)?$this->db->safesql(intval(@$_GET['phrase'])):false; 
        
            if (!$phrases) {
                $this->core->notify(
                    $this->l10n->gettext('error_message'),
                    $this->l10n->gettext('error_not_found'),
                    2,
                    '?mode=admin&do=l10n_phrases'
                );
            }
        } 
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $phrases = $_POST['phrases_id'];
            
            if (empty($phrases)) {
                $this->core->notify(
                    $this->l10n->gettext('error_message'),
                    $this->l10n->gettext('phrases_not_selected'),
                    2,
                    '?mode=admin&do=l10n_phrases'
                );
            }
            
            $phrases = $this->core->filter_int_array($phrases);
            $phrases = array_unique($phrases);
            $phrases = $this->db->safesql(implode(", ", $phrases));
        }
        
        if (!$this->db->remove_fast("mcr_l10n_phrases", "`id` IN ($phrases)")) {
            $this->core->notify(
                $this->l10n->gettext('error_message'),
                $this->l10n->gettext('error_sql_critical'),
                2,
                '?mode=admin&do=l10n_phrases'
            );
        }

        $count1 = $this->db->affected_rows();

        // Последнее обновление пользователя
        $this->db->update_user($this->user);
        // Лог действия
        $this->db->actlog($this->l10n->gettext('log_del_phrase')." $phrases ".$this->l10n->gettext('log_news'), $this->user->id);
        $this->core->notify(
            $this->l10n->gettext('success'),
            $this->l10n->gettext('success_delete_phrases')." $count1",
            3,
            '?mode=admin&do=l10n_phrases'
        );
    }

	public function content() {
        $op = (isset($_GET['op'])) ? $_GET['op'] : 'list';

        switch ($op) {
            case 'add': $content = $this->add(); break;
            case 'edit': $content = $this->edit(); break;
            case 'delete': $this->delete(); break;

            default: $content = $this->all_phrases(); break;
        }

        return $content;
	}
}