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

    public function __construct(core $core) {
		$this->core = $core;
		$this->db	= $core->db;
		$this->l10n = $core->l10n;
		$this->user = $core->user;

		if(!$this->core->is_access('sys_adm_l10n')){ $this->core->notify('403'); }

		$bc = array(
			$this->l10n->gettext('module_admin-panel') => ADMIN_URL,
			$this->l10n->gettext('languages') => ADMIN_URL."&do=news"
		);
		$this->core->bc = $this->core->gen_bc($bc);

		$this->core->header .= $this->core->sp(MCR_THEME_MOD."admin/l10n/languages/header.html");
	}
    
    protected function parent_languages_list($lng='') {
        $get_languages = "
            SELECT `id`, `settings` 
            FROM `mcr_l10n_languages` 
            WHERE `parent_language` IS NULL OR `parent_language`=`id`
        ";
        $languages = $this->db->query($get_languages);
        
        ob_start();

		while ($language = $this->db->fetch_assoc($languages)) {
            $language_info = json_decode($language['settings']);
            $l = array(
                'locale' => $language_info->locale,
                'title' => $language_info->title
            );
            
            $select = ($lng == $language['id'])?'selected':'';
            
			echo "<option value='{$language['id']}'$select>".$l['title'].': ('.explode('-', $l['locale'])[1].")</option>";
        }

		return ob_get_clean();
    }

    protected function locales_list($l='') {
        ob_start();

		foreach ($this->l10n->locales as $locale) {
            $locale_language = Locale::getDisplayLanguage($locale, $this->l10n->get_config_locale());
            $locale_title = mb_convert_case($locale_language, MB_CASE_TITLE, "UTF-8").' ('.explode('-', $locale)[1].')';
            
            $select = ($locale == $l)?'selected':'';
            
            echo "<option value='$locale'$select>".$locale_title."</option>";
        }

		return ob_get_clean();
    }
    
    protected function date_formats($df='') {
        ob_start();

        $counter = 0;
		foreach ($this->l10n->date_formats as $key => $value) {
            $counter++;
            
            $date = array(
                'ID' => $counter,
                'FORMAT' => $key,
                'EXAMPLE' => $this->l10n->localize('31.07.2000', 'datetime', $value),
                'DT' => 'date',
                'SELECT' => ($key == $df)?'checked':'',
            );
            
            echo $this->core->sp(MCR_THEME_MOD."admin/l10n/languages/datetime_format.html", $date);
        }

		return ob_get_clean();
    }
    
    protected function time_formats($tf='') {
        ob_start();

        $counter = 0;
		foreach ($this->l10n->time_formats as $key => $value) {
            $counter++;
            
            $time = new DateTime('18:30');
            $example_time = $time->format($key);
            
            $date = array(
                'ID' => $counter,
                'FORMAT' => $key,
                'EXAMPLE' => $example_time,
                'DT' => 'time',
                'SELECT' => ($key == $tf)?'checked':'',
            );
            
            echo $this->core->sp(MCR_THEME_MOD."admin/l10n/languages/datetime_format.html", $date);
        }

		return ob_get_clean();
    }

    protected function languages_list($languages) {
		ob_start();

        while ($language = $this->db->fetch_assoc($languages)) {
			$title = json_decode($language['settings'])->title;

			$data = array(
				"ID" => $language['id'],
				"TITLE" => $title,
				"LOCALE" => $language['language']
			);

			echo $this->core->sp(MCR_THEME_MOD."admin/l10n/languages/language.html", $data);
		}

		return ob_get_clean();
	}
	
	protected function all_languages() {
		$languages = $this->l10n->get_languages();
		$languages_list = (isset($languages))?$this->languages_list($languages):'';

		$data = array(
			"LANGUAGES_LIST" => $languages_list,
		);

		return $this->core->sp(MCR_THEME_MOD."admin/l10n/languages/languages.html", $data);
	}
    
    protected function show_phrases() {
        $id = intval($_GET['language']);
        
        $this->core->redirect(ADMIN_URL."&do=l10n_phrases&language=$id");
    }

    public function get_phrases_from($language_id) {
        switch($language_id) {
            case 0:   
                $phrases = "SELECT `phrase_key`, `phrase_value` FROM mcr_l10n_phrases";
                $query = $this->db->query($phrases);
                
                if (!$query || $this->db->num_rows($query) <= 0) {
                    $this->core->notify(
                        $this->l10n->gettext('error_message'),
                        $this->l10n->gettext('error_sql_critical'),
                        2,
                        '?mode=admin&do=l10n_languages&op=add'
                    );
                }
                    
				$result = '{';	
					
                while ($phrase = $this->db->fetch_assoc($query)) {
					$result .= '"'.$phrase['phrase_key'].'":"'.mb_ereg_replace('\r\n', '<br>', str_replace('"', '\"', $phrase['phrase_value'])).'",';
				}
				
				$result = substr($result, 0, -1).'}';
				
				return $result;
                break;
            default: 
                $phrases = "SELECT `phrases` FROM mcr_l10n_languages WHERE `id`='$language_id' OR `language`='$language_id'";
                $query = $this->db->query($phrases);
                
                if (!$query || $this->db->num_rows($query) <= 0) {
                    $this->core->notify(
                        $this->l10n->gettext('error_message'),
                        $this->l10n->gettext('error_sql_critical'),
                        2,
                        '?mode=admin&do=l10n_languages&op=add'
                    );
                }
                
                $phrases = $this->db->fetch_assoc($query)['phrases'];
                
                return $phrases;
                break;
        }
    }
    
    protected function add() {
        if ($_SERVER['REQUEST_METHOD']=='POST') {
        	$post = array(
                'language_title' => $_POST['language_title'],
                'language_parent' => ($_POST['language_parent'])?$_POST['language_parent']:0,
                'locale' => $_POST['locale'],
                'text_direction' => (preg_match("/(ltr|rtl)/", $_POST['text_direction']))?(
                    $_POST['text_direction']
                ):('ltr'),
                'date_format' => $_POST['date_format'],
                'time_format' => $_POST['time_format']
            );
            
            if ($post['language_parent'] != 0) {
                $check_language = $this->db->query("SELECT `language` FROM `mcr_l10n_languages` WHERE `id`='{$post['language_parent']}'");
                
                if (!$check_language || $this->db->num_rows($check_language) <= 0) {
                    $this->core->notify(
                        $this->l10n->gettext('error_message'),
                        $this->l10n->gettext('invalid_parent_language'),
                        2
                    );
                }
            }
            
            $post_settings = array(
                'title' => $post['language_title'],
                'locale' => $post['locale'],
                'text_direction' => $post['text_direction'],
                'date_format' => $post['date_format'],
                'time_format' => $post['time_format']
            );
            
            $language_parent = ($post['language_parent']==0)?"NULL":"'{$post['language_parent']}'";
            $locale = $this->db->safesql($post['locale']);
            $settings = $this->db->safesql(json_encode($post_settings, JSON_UNESCAPED_UNICODE));
            $phrases = $this->db->safesql($this->get_phrases_from($post['language_parent']));
            $add_language = "
                INSERT INTO `mcr_l10n_languages`
                    (`parent_language`, `language`, `settings`, `phrases`)
                VALUE
                    ($language_parent, '{$locale}', '{$settings}', '{$phrases}')
            ";
            if (!$this->db->query($add_language)) { $this->core->notify(
                $this->l10n->gettext('error_message'),
                $this->l10n->gettext('error_sql_critical'),
                2,
                '?mode=admin&do=l10n_languages&op=add'
            ); }

            $id = $this->db->insert_id();
            
            // Последнее обновление пользователя
            $this->db->update_user($this->user);
            // Лог действия
            $this->db->actlog(
                $this->l10n->gettext('log_add_language')." #$id ".$this->l10n->gettext('log_language'),
                $this->user->id
            );
            $this->l10n->set_cache($locale);
            $this->core->notify(
                $this->l10n->gettext('success'),
                $this->l10n->gettext('success_add_language'),
                3,
                '?mode=admin&do=l10n_languages'
            );
        }
        
        $data = array(
            'LANGUAGE_TITLE' => '',
            'PARENT_LANGUAGES' => $this->parent_languages_list(),
            'LOCALES' => $this->locales_list(),
            'DATE_FORMATS' => $this->date_formats(),
            'TIME_FORMATS' => $this->time_formats(),
            'TEXT_DIRECTION' => ''
        );
        
        return $this->core->sp(MCR_THEME_MOD."admin/l10n/languages/form-languages.html", $data);
	}

	protected function edit() {
        $language_id = (intval(@$_GET['language']) >= 1)?intval(@$_GET['language']):false;
        if (!$language_id) {
            $this->core->notify(
                $this->l10n->gettext('error_message'),
                $this->l10n->gettext('invalid_data'),
                2,
                '?mode=admin&do=l10n_languages'
            );
        }
        $language = "
            SELECT
                `parent_language`, 
                `settings`
            FROM `mcr_l10n_languages`
            WHERE `id`='$language_id'
        ";
        $language = $this->db->query($language);
        
        $parent_language = $locale = $title = $date_format = $time_format = $text_direction = '';
        
        if ($this->db->num_rows($language) == 1) {
            $p = $this->db->fetch_assoc($language);
            
            $parent_language = $p['parent_language'];
            $settings = json_decode($p['settings'], true);
            
            $locale = $settings['locale'];
            $date_format = $settings['date_format'];
            $time_format = $settings['time_format'];
            $title = $settings['title'];
            $text_direction = $settings['text_direction'];
        }
        
        if ($_SERVER['REQUEST_METHOD']=='POST') {
            $post = array(
                'language_title' => $_POST['language_title'],
                'language_parent' => ($_POST['language_parent'])?$_POST['language_parent']:0,
                'locale' => $_POST['locale'],
                'text_direction' => (preg_match("/(ltr|rtl)/", $_POST['text_direction']))?(
                    $_POST['text_direction']
                ):('ltr'),
                'date_format' => $_POST['date_format'],
                'time_format' => $_POST['time_format']
            );
            
            if ($post['language_parent'] != 0) {
                $check_language = $this->db->query("SELECT `language` FROM `mcr_l10n_languages` WHERE `id`='{$post['language_parent']}'");
                
                if (!$check_language || $this->db->num_rows($check_language) <= 0) {
                    
                    $this->core->notify(
                        $this->l10n->gettext('error_message'),
                        $this->l10n->gettext('invalid_parent_language'),
                        2
                    );
                }
            }
            
            $post_settings = array(
                'title' => $post['language_title'],
                'locale' => $post['locale'],
                'text_direction' => $post['text_direction'],
                'date_format' => $post['date_format'],
                'time_format' => $post['time_format']
            );
            
            $language_parent = ($post['language_parent']==0)?"NULL":"'{$post['language_parent']}'";
            $locale = $this->db->safesql($post['locale']);
            $settings = $this->db->safesql(json_encode($post_settings, JSON_UNESCAPED_UNICODE));
            $phrases = $this->db->safesql($this->get_phrases_from($post['language_parent']));
            $update_language = "
                UPDATE `mcr_l10n_languages`
                SET 
                    `parent_language`=$language_parent, 
                    `language`='{$locale}', 
                    `settings`='{$settings}', 
                    `phrases`='{$phrases}'
                WHERE `id`='$language_id'
            ";
            if (!$this->db->query($update_language)) { $this->core->notify(
                $this->l10n->gettext('error_message'),
                $this->l10n->gettext('error_sql_critical'),
                2,
                '?mode=admin&do=l10n_languages&op=edit'
            ); }

            $id = $this->db->insert_id();
            
            // Последнее обновление пользователя
            $this->db->update_user($this->user);
            // Лог действия
            $this->db->actlog(
                $this->l10n->gettext('log_edit_language')." #$id ".$this->l10n->gettext('log_language'),
                $this->user->id
            );
            $this->l10n->update_cache($locale, '?mode=admin&do=l10n_languages');
            $this->core->notify(
                $this->l10n->gettext('success'),
                $this->l10n->gettext('success_edit_language'),
                3,
                '?mode=admin&do=l10n_languages'
            );
        }
        
        $data = array(
            'LANGUAGE_TITLE' => $title,
            'PARENT_LANGUAGES' => $this->parent_languages_list($parent_language),
            'LOCALES' => $this->locales_list($locale),
            'DATE_FORMATS' => $this->date_formats($date_format),
            'TIME_FORMATS' => $this->time_formats($time_format),
            'TEXT_DIRECTION' => $text_direction
        );
        
        return $this->core->sp(MCR_THEME_MOD."admin/l10n/languages/form-languages.html", $data);
	}

	protected function delete() {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') { 
			$pattern = '/([a-z]{2})-([A-Z]{2})/';
            $languages = (preg_match($pattern, @$_GET['language']) == 1)?"'".@$_GET['language']."'":false; 
        
            if (!$languages) {
                $this->core->notify(
                    $this->l10n->gettext('error_message'),
                    $this->l10n->gettext('error_not_found'),
                    2,
                    '?mode=admin&do=l10n_languages'
                );
            }
        } 
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $languages = $_POST['language_id'];
            
            if (empty($languages)) {
                $this->core->notify(
                    $this->l10n->gettext('error_message'),
                    $this->l10n->gettext('phrases_not_selected'),
                    2,
                    '?mode=admin&do=l10n_languages'
                );
            }
            
            foreach ($languages as $language) {
                $pattern = '/([a-z]{2})-([A-Z]{2})/';
                if (preg_match($pattern, $language) != 1) {
                        $this->core->notify(
                        $this->l10n->gettext('error_message'),
                        $this->l10n->gettext('error_not_found'),
                        2,
                        '?mode=admin&do=l10n_languages'
                    );
                }
            }
            
            $languages = array_unique($languages);
            $languages = "'".implode("', '", $languages)."'";
        }

        /** @var submodule $languages */

        if (!$this->db->remove_fast("mcr_l10n_languages", "`language` IN ($languages)")) {
            $this->core->notify(
                $this->l10n->gettext('error_message'),
                $this->l10n->gettext('error_sql_critical'),
                2,
                '?mode=admin&do=l10n_languages'
            );
        }
        $this->l10n->delete_cache($languages);

        $count1 = $this->db->affected_rows();

        // Последнее обновление пользователя
        $this->db->update_user($this->user);
        // Лог действия
        $this->db->actlog($this->l10n->gettext('log_del_languages')." $languages ".$this->l10n->gettext('log_languages'), $this->user->id);
        $this->core->notify(
            $this->l10n->gettext('success'),
            $this->l10n->gettext('success_delete_languages')." $count1",
            3,
            '?mode=admin&do=l10n_languages'
        );
	}

	public function content() {
		$op = (isset($_GET['op'])) ? $_GET['op'] : 'list';

		switch ($op) {
			case 'add': 
                $bc = array(
                    $this->l10n->gettext('module_admin-panel') => ADMIN_URL,
                    $this->l10n->gettext('languages') => ADMIN_URL."&do=l10n_languages",
                    $this->l10n->gettext('add_language') => ADMIN_URL."&do=l10n_languages&op=add"
                );
                $this->core->bc = $this->core->gen_bc($bc);

                $content = $this->add(); 
                break;
			case 'edit': 
                $bc = array(
                    $this->l10n->gettext('module_admin-panel') => ADMIN_URL,
                    $this->l10n->gettext('languages') => ADMIN_URL."&do=l10n_languages",
                    $this->l10n->gettext('language_edit') => ADMIN_URL."&do=l10n_languages&op=edit"
                );
                $this->core->bc = $this->core->gen_bc($bc);

                $content = $this->edit(); 
                break;
			case 'delete': $content = ''; $this->delete(); break;
            
			case 'phrases': $content = '';  $this->show_phrases(); break;
            
			default: $content = $this->all_languages(); break;
		}
        
        return $content;
	}
}