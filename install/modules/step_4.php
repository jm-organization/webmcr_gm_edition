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

class module{
	private $install, $cfg, $lng;

	public function __construct($install){
		$this->install = $install;
		$this->cfg = $install->cfg;
		$this->lng = $install->lng;

		$this->install->title = $this->lng['mod_name'].' — '.$this->lng['step_4'];
	}

	public function content() {
		if(!isset($_SESSION['step_3'])){ $this->install->notify('', '', 'install/?do=step_3'); }
		if(isset($_SESSION['step_4'])){ $this->install->notify('', '', 'install/?do=step_5'); }

		if($_SERVER['REQUEST_METHOD']=='POST'){

			$this->cfg['modules']['users']['enable_comments'] = (intval(@$_POST['use_comments'])==1) ? true : false;
			$this->cfg['modules']['users']['users_on_page'] = (intval(@$_POST['rop_users'])<1) ? 1 : intval(@$_POST['rop_users']);
			$this->cfg['modules']['users']['comments_on_page'] = (intval(@$_POST['rop_comments'])<1) ? 1 : intval(@$_POST['rop_comments']);

			if(!$this->install->savecfg($this->cfg['modules']['users'], 'modules/users.php', 'cfg')){
				$this->install->notify($this->lng['e_msg'], $this->lng['e_settings'], 'install/?mode=step_4');
			}

			$_SESSION['step_4'] = true;

			$this->install->notify($this->lng['mod_name'], $this->lng['step_4'], 'install/?mode=step_5');
		}

		$data = array(
			"COMMENTS" => ($this->cfg['modules']['users']['enable_comments']) ? 'selected' : '',
		);

		return $this->install->sp('step_4.phtml', $data);
	}
}

?>