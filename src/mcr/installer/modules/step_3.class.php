<?php

namespace mcr\installer\modules;


use mcr\installer\install;

if (!defined("MCR")) exit("Hacking Attempt!");

class step_3 extends install_step
{
	public function save_settings()
	{

	}

	public function settings_form()
	{
		install::$page_title = translate('mod_name') . ' — ' . translate('step_3');

		return tmpl('steps.step_3');
	}

}