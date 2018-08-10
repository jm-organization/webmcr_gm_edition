<?php

namespace mcr\installer\modules;


use mcr\installer\install;

if (!defined("MCR")) exit("Hacking Attempt!");

class finish extends install_step
{
	public function done_installing()
	{
		install::$page_title = translate('mod_name') . ' — ' . translate('finish');

		return tmpl('steps.finish');
	}
}