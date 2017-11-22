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

    private $settings;

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

    protected function phrases_list($rows, $master = true) {
        ob_start();

        if ($master) {
           while ($phrase = $this->db->fetch_assoc($rows)) {
                $language_title = json_decode($phrase['language_settings'])->title;

                $data = array(
                    "ID" => $phrase['id'],
                    "PHRASE" => $phrase['phrase_key'],
                    "PHRASE_VALUE" => $phrase['phrase_value'],
                    "LANGUAGE" => 0
                );

                echo $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/phrase.html", $data);
            } 
        } else {
            while ($language = $this->db->fetch_assoc($rows)) {
                $phrases = ($language['phrases'] != '' 
                    && !empty($language['phrases'])
                )?(is_array(json_decode($language['phrases'], true)))?json_decode($language['phrases'], true):array():array();
                
                $counter = 0;
                foreach ($phrases as $key => $value) {
                    $counter++;

                    $data = array(
                        "ID" => $counter,
                        "PHRASE" => $key,
                        "PHRASE_VALUE" => $value,
                        "LANGUAGE" => $language['id']
                    );

                    echo $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/phrase.html", $data);
                }
            }
        }
        

        return ob_get_clean();
	}

	protected function all_phrases($language) {
        $master = ($language == 0)?true:false;
        
        $phrases = ($master)?($this->l10n->get_phrases()):($this->l10n->get_languages($language, false));
        $phrases_list = ($phrases || $this->db->num_rows($phrases) > 0)?
            $this->phrases_list($phrases, $master)
        :'';
        
        $get_languages = "SELECT `id`, `settings` FROM `mcr_l10n_languages`";
        $languages_qr = $this->db->query($get_languages);

        ob_start();
            echo "<option value='0' style='color:#d9534f;' selected>Русский</option>";

        while ($item = $this->db->fetch_assoc($languages_qr)) {
            $settings = json_decode($item['settings']);

            if ($item['id'] == $language) {
                $this->settings = $settings;
            }

            $select = ($item['id'] == $language)?'selected':'';

            echo "<option value='{$item['id']}'$select>{$settings->title}</option>";
        }
        $languages_list = ob_get_clean();
        
        if (!$master) {
            $locale = $this->settings->locale;
        } else { $locale = '<span style="color:#d9534f;"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i>'.Locale::getDefault().' (master)</span>'; }
        
		$data = array(
			"LANGUAGE_LOCALE" => $locale,
            "LANGUAGES_LIST" => $languages_list,
            "PHRASES_LIST" => $phrases_list
		);

		return $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/phrases.html", $data);
	}
    
    protected function languages($select_language = -1) {
        $languages = $this->l10n->get_languages();
		$select = ($select_language==0)?'selected':0;

        ob_start();
            echo "<option value='0' style='color:#d9534f;'{$select}>Русский (master)</option>";

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

    protected function update_language($sql_to_get, $new_phrase, $action=false) {
        $data_lang = $this->db->query($sql_to_get);
        if (!$data_lang || $this->db->num_rows($data_lang) <=0) { return null; };

        foreach ($data_lang as $language) {
            $phrases = json_decode($language['phrases'], true);

            if ($action == 'add' && array_key_exists(strval($new_phrase['key']), $phrases)) {$this->core->notify(
                $this->l10n->gettext('error_message'),
                $this->l10n->gettext('phrase_is_exist', $new_phrase['key']),
                2,
                '?mode=admin&do=l10n_phrases&language='.$language['id'].(($action)?'&op='.$action:'')
            );}

            $phrase[strval($new_phrase['key'])] = $new_phrase['value'];

            $phrases = array_merge($phrases, $phrase);
            ksort($phrases);
            $phrases = $this->db->safesql(json_encode($phrases, JSON_UNESCAPED_UNICODE));

            $update_phrases = "
                UPDATE `mcr_l10n_languages` 
                SET `phrases`='{$phrases}'
                WHERE `id`='{$language['id']}'
            ";
            if (!$this->db->query($update_phrases)) { $this->core->notify(
                $this->l10n->gettext('error_message'),
                $this->l10n->gettext('error_sql_critical').".\n".$this->l10n->gettext('languages_update'),
                2,
                '?mode=admin&do=l10n_phrases&language='.$language['id'].($action)?'&op='.$action:''
            ); };

            $this->l10n->update_cache($language['language']);
        }
    }

    protected function update_child_languages($parent, $new_phrase, $action=false) {
        switch ($parent) {
            case 0: $languages = "SELECT `id`, `language`, `phrases` FROM `mcr_l10n_languages`"; break;
            default : $languages = "SELECT `id`, `language`, `phrases` FROM `mcr_l10n_languages` WHERE `parent_language`='{$parent}'"; break;
        }
        
        $this->update_language($languages, $new_phrase, $action);
    }

    protected function delete_phrases($children, $phrases) {
        $child_languages = $this->db->query($children);
        if (!$child_languages || $this->db->num_rows($child_languages) <=0) { return null; };

        while ($child = $this->db->fetch_Assoc($child_languages)) {
            $child_language_phrases = json_decode($child['phrases'], true);

            foreach ($child_language_phrases as $key => $value) {
                foreach ($phrases as $phrase) { if ($phrase == $key) {
                    unset($child_language_phrases[$key]);
                } }
            }

            $child_language_phrases = json_encode($child_language_phrases, JSON_UNESCAPED_UNICODE);

            $update_child = "
                UPDATE `mcr_l10n_languages`
                SET `phrases`='{$child_language_phrases}'
                WHERE `id`='{$child['id']}'
            ";
            $this->db->query($update_child);

            $this->l10n->update_cache($child['language']);
        }
    }

    protected function delete_child_phrases($parent, $phrases) {
        switch ($parent) {
            case 0: $children = "SELECT `id`, `phrases`, `language` FROM `mcr_l10n_languages`"; break;
            default: $children = "SELECT `id`, `phrases`, `language` FROM `mcr_l10n_languages` WHERE `parent_language`='{$parent}'"; break;
        }

        $this->delete_phrases($children, $phrases);
    }

    protected function add() {
        $validphrase = "/[a-zA-Z0-9_-]*/";

        if ($_SERVER['REQUEST_METHOD']=='POST') {
            $post = array(
                'language' => intval(@$_POST['language']),
                'phrase_value' => htmLawed(@$_POST['phrase_value']),
                'phrase_key' => (preg_match($validphrase,trim( @$_POST['phrase_key'])) == 1)?trim( @$_POST['phrase_key']):false,
            );

            if (!$post['phrase_key']) {
                $this->core->notify(
                    $this->l10n->gettext('error_message'),
                    $this->l10n->gettext('invalid_data'),
                    2,
                    '?mode=admin&do=l10n_phrases&op=add'
                );
            }
            
            $closer = "_%s_";
            $phrase_key = $this->db->safesql((ctype_digit($post['phrase_key']))?sprintf($closer, $post['phrase_key']):$post['phrase_key']);
            $phrase_value = $this->db->safesql($post['phrase_value']);
            
            switch ($post['language']) {
                case 0:
                    $add_master_phrase = "
                        INSERT 
                        INTO `mcr_l10n_phrases` (`phrase_key`, `phrase_value`)
                        VALUE ('{$phrase_key}', '{$phrase_value}')
                    ";
                    if (!$this->db->query($add_master_phrase)) { $this->core->notify(
                        $this->l10n->gettext('error_message'),
                        $this->l10n->gettext('error_sql_critical').'. '.$this->l10n->gettext('save_phrase'),
                        2,
                        '?mode=admin&do=l10n_phrases&op=add'
                    ); }
                        
                    $new_phrase = array('key'=>$phrase_key,'value'=>$phrase_value);
                    $this->update_child_languages(0, $new_phrase, 'add');
                    break;
                default: 
                    $language = "SELECT `id`, `language`, `phrases` FROM `mcr_l10n_languages` WHERE `id`='{$post['language']}'";
                    $new_phrase = array('key'=>$phrase_key,'value'=>$phrase_value);
                    
                    $this->update_language($language, $new_phrase, 'add');
                    
                    $this->update_child_languages($post['language'], $new_phrase, 'add');
                    break;
            }
            
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
                '?mode=admin&do=l10n_phrases&language='.$post['language']
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
        $validphrase = "/[a-zA-Z0-9_-]*/";

        $language = intval(@$_GET['language']);
        $phrase = (preg_match($validphrase, trim(@$_GET['phrase'])) == 1)?trim(@$_GET['phrase']):false;

        if (!$phrase) { $this->core->notify(
            $this->l10n->gettext('error_message'),
            $this->l10n->gettext('invalid_data').': `phrase_key`.',
            2,
            '?mode=admin&do=l10n_phrases&language='.$language
        ); }

        switch ($language) {
            case 0:
                $languages = "
                    SELECT `phrase_key`, `phrase_value` 
                    FROM `mcr_l10n_phrases` 
                    WHERE `phrase_key`='$phrase'
                ";
                $languages = $this->db->query($languages);
                if (!$languages || $this->db->num_rows($languages) <= 0) {
                    $this->core->notify(
                        $this->l10n->gettext('error_message'),
                        $this->l10n->gettext('invalid_data'),
                        2,
                        '?mode=admin&do=l10n_phrases&language='.$language
                    );
                }
                $current_language = $this->db->fetch_assoc($languages);
                $phrases[$current_language['phrase_key']] = $current_language['phrase_value'];
                break;
            default:
                $languages = "
                    SELECT `id`, `phrases` 
                    FROM `mcr_l10n_languages` 
                    WHERE `id`='$language' 
                    OR `language`='$language'
                ";
                $languages = $this->db->query($languages);
                if (!$languages || $this->db->num_rows($languages) <= 0) {
                    $this->core->notify(
                        $this->l10n->gettext('error_message'),
                        $this->l10n->gettext('invalid_data'),
                        2,
                        '?mode=admin&do=l10n_phrases&language='.$language
                    );
                }
                $current_language = $this->db->fetch_assoc($languages);
                $phrases = json_decode($current_language['phrases'], true);
                break;
        }

        if ($_SERVER['REQUEST_METHOD']=='POST') {
            $post = array(
                'language' => intval(@$_POST['language']),
                'phrase_value' => htmLawed(@$_POST['phrase_value']),
                'phrase_key' => (strlen(trim(@$_POST['phrase_key'])) > 1)?(
                    (preg_match($validphrase,trim( @$_POST['phrase_key'])) == 1)?trim(@$_POST['phrase_key']):false
                ):(false),
            );

            if (!$post['phrase_key']) {
                $this->core->notify(
                    $this->l10n->gettext('error_message'),
                    $this->l10n->gettext('invalid_data'),
                    2,
                    '?mode=admin&do=l10n_phrases&op=edit&language='.$post['language']
                );
            }

            $closer = "_%s_";
            $phrase_key = $this->db->safesql((ctype_digit($post['phrase_key']))?sprintf($closer, $post['phrase_key']):$post['phrase_key']);
            $phrase_value = $this->db->safesql($post['phrase_value']);

            switch ($post['language']) {
                case 0:
                    $add_master_phrase = "
                        UPDATE  `mcr_l10n_phrases` 
                        SET 
                          `phrase_key`='{$phrase_key}', 
                          `phrase_value`='{$phrase_value}'
                        WHERE `phrase_key`='{$phrase_key}'
                    ";
                    if (!$this->db->query($add_master_phrase)) { $this->core->notify(
                        $this->l10n->gettext('error_message'),
                        $this->l10n->gettext('error_sql_critical').'. '.$this->l10n->gettext('save_phrase'),
                        2,
                        '?mode=admin&do=l10n_phrases&op=edit&language='.$post['language']
                    ); }

                    $new_phrase = array('key'=>$phrase_key,'value'=>$phrase_value);
                    $this->update_child_languages(0, $new_phrase, 'edit');
                    break;
                default:
                    $language = "SELECT `id`, `language`, `phrases` FROM `mcr_l10n_languages` WHERE `id`='{$post['language']}'";
                    $new_phrase = array('key'=>$phrase_key,'value'=>$phrase_value);

                    $this->update_language($language, $new_phrase, 'edit');

                    $this->update_child_languages($post['language'], $new_phrase, 'edit');
                    break;
            }

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
                '?mode=admin&do=l10n_phrases&language='.$post['language']
            );
        }
        
        $data = array(
            'LANGUAGES' => $this->languages($language),
            'PHRASE_KEY' => $phrase,
            'PHRASE_VALUE' => $phrases[$phrase],
        );

        return $this->core->sp(MCR_THEME_MOD."admin/l10n/phrases/form-phrases.html", $data);

	}

	protected function delete() {
        $validphrase = "/[a-zA-Z0-9_-]*/";

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $phrases[] = (strlen(@$_GET['phrase']) > 1)?(
                (preg_match($validphrase, trim(@$_GET['phrase'])) == 1)?trim(@$_GET['phrase']):false
            ):(false);
            $language = (isset($_GET['language']))?$_GET['language']:0;

            if (!$phrases[0]) {
                $this->core->notify(
                    $this->l10n->gettext('error_message'),
                    $this->l10n->gettext('error_not_found'),
                    2,
                    '?mode=admin&do=l10n_phrases&language='.$language
                );
            }
        } 
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $phrases = $_POST['phrases_id'];
            $language = $_POST['language'];

            if (empty($phrases)) {
                $this->core->notify(
                    $this->l10n->gettext('error_message'),
                    $this->l10n->gettext('phrases_not_selected'),
                    2,
                    '?mode=admin&do=l10n_phrases&language='.$language
                );
            }

            $phrases = array_unique($phrases);
            foreach ($phrases as $phrase) {
                if (preg_match($validphrase, $phrase) != 1) {
                    $this->core->notify(
                        $this->l10n->gettext('error_message'),
                        $this->l10n->gettext('error_not_found'),
                        2,
                        '?mode=admin&do=l10n_phrases&language='.$language
                    );
                }
            }
        }

        /**
         * @var submodule $language
         * @var submodule $phrases
         */

        if ($language < 0) { $this->core->notify(
            $this->l10n->gettext('error_message'),
            $this->l10n->gettext('unknown_language'),
            2,
            '?mode=admin&do=l10n_phrases'
        ); }

        switch ($language) {
            case 0:
                $this->delete_child_phrases(0, $phrases);

                $phrases = "'".implode("', '", $phrases)."'";

                $this->db->remove_fast("mcr_l10n_phrases", "`phrase_key` IN ($phrases)");
                break;
            default:
                $parent = "SELECT `id`, `language`, `phrases` FROM `mcr_l10n_languages` WHERE `id`='{$language}'";

                $this->delete_phrases($parent, $phrases);

                $this->delete_child_phrases($language, $phrases);
                break;
        }

        $count1 = $this->db->affected_rows();
        $phrases = (is_array($phrases))?"'".implode("', '", $phrases)."'":$phrases;

        // Последнее обновление пользователя
        $this->db->update_user($this->user);
        // Лог действия
        $this->db->actlog($this->l10n->gettext('log_del_phrase')." $phrases ".$this->l10n->gettext('log_phrases'), $this->user->id);
        $this->core->notify(
            $this->l10n->gettext('success'),
            $this->l10n->gettext('success_delete_phrases')." $count1",
            3,
            '?mode=admin&do=l10n_phrases&language='.$language
        );
    }

	public function content() {
        $op = (isset($_GET['op'])) ? $_GET['op'] : '';
        $language = (isset($_GET['language'])) ? intval($_GET['language']) : 0;

        switch ($op) {
            case 'add': 
                $bc = array(
                    $this->l10n->gettext('module_admin-panel') => ADMIN_URL,
                    $this->l10n->gettext('phrases') => ADMIN_URL."&do=l10n_phrases",
                    $this->l10n->gettext('add-phrase') => ADMIN_URL."&do=l10n_phrases&op=add"
                );
                $this->core->bc = $this->core->gen_bc($bc);

                $content = $this->add(); 
                break;
            case 'edit': 
                $bc = array(
                    $this->l10n->gettext('module_admin-panel') => ADMIN_URL,
                    $this->l10n->gettext('phrases') => ADMIN_URL."&do=l10n_phrases",
                    $this->l10n->gettext('edit-phrase') => ADMIN_URL."&do=l10n_phrases&op=edit"
                );
                $this->core->bc = $this->core->gen_bc($bc);

                $content = $this->edit(); 
                break;
            case 'delete': $this->delete(); break;

            default:
                if ($language != 0) {
                    $bc = array(
                        $this->l10n->gettext('module_admin-panel') => ADMIN_URL,
                        $this->l10n->gettext('languages') => ADMIN_URL."&do=l10n_languages",
                        $this->l10n->gettext('phrases') => ADMIN_URL."&do=l10n_phrases&language=$language",
                    );
                    $this->core->bc = $this->core->gen_bc($bc);
                }
                
                $content = $this->all_phrases($language); 
                break;
        }

        return $content;
	}
}