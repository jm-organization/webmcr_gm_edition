<?php
/**
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 27.06.2018
 * @Time         : 22:30
 *
 * @Documentation:
 */

namespace modules\magicmen\magicmcr;


use mcr\core\application\application;
use mcr\database\db;
use mcr\html\document;

abstract class base_module
{
	public $layout = 'global';

	public $name = '';

	/**
	 * Метод, который вызывается при загрузке модуля.
	 * Принимает экземпляр ядра.
	 *
	 * @param application $app
	 *
	 * @return void
	 */
	public function boot(application $app)
	{
		$advices = $this->get_advices(config('functions::advice'));

		if (is_array($advices)) {
			$advices_count = count($advices);

			if ($advices_count == 0) {
				$advice = translate('e_advice_found');
			} else {
				$advice = $advices[rand(0, $advices_count - 1)];
			}

			document::$variables['advice'] = tmpl('advice', [ 'advice' => $advice ]);
		}
	}

	/**
	 * Делает лог-запись в таблице действия пользователей.
	 *
	 * @param $msg
	 * @param $uid
	 *
	 * @return bool
	 * @throws \mcr\database\db_exception
	 */
	public function actlog($msg, $uid)
	{
		if (!empty(config('db'))) {
			if (!config('db::log')) {
				return false;
			}

			$uid = intval($uid);
			$msg = db::escape_string($msg);

			$date = time();

			$result = db::query("INSERT INTO `mcr_logs` (`uid`, `message`, `date`) VALUES ('$uid', '$msg', $date)")->result();

			if (!$result) return false;

			return true;
		}

		return false;
	}

	private function get_advices($enabled)
	{
		if ($enabled) {
			$advices = file(MCR_ROOT . "data/advices.txt");

			return (count($advices) <= 0) ? [] : $advices;
		}

		return null;
	}
}