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
 * @e-mail: admin@jm-org.net
 * @Author: Magicmen
 *
 * @Date  : 08.09.2018
 * @Time  : 18:19
 */

namespace modules\magicmen\magicmcr\admin\l10n;


use mcr\core\application\application;
use mcr\database\db;
use mcr\html\breadcrumbs;
use mcr\http\request;
use modules\magicmen\magicmcr\admin\admin;
use mcr\http\module;

class phrases extends admin implements module
{
	public $name = 'modules.admin.l10n.phrases';

	public function boot(application $app)
	{
		parent::boot($app);

		breadcrumbs::add(
			url('admin.l10n.phrases'),
			translate('phrases')
		);
	}

	/**
	 * Обрабатывает запрос к модулю.
	 *
	 * @param request $request
	 *
	 * @return \mcr\http\response|\mcr\http\redirect_response|string
	 * @throws \mcr\database\db_exception
	 */
	public function index(request $request)
	{
		$_languages = db::table('l10n_languages')->select('settings', 'id')->get();
		$languages = ["<span style='color: #ff4545'><i class='ru flag'></i> Русский (ru-RU) - master</span>"];

		$current_language_id = $request->id;
		if ($current_language_id == null) {
			$current_language_id = 0 ;
		}

		foreach ($_languages as $_language) {
			$language_settings = json_decode($_language['settings']);

			$lang_short_code = explode('-', $language_settings->locale)[0];
			$languages[$_language['id']] = "<span><i class='$lang_short_code flag'></i> {$language_settings->title} ({$language_settings->locale})</span>";
		}

		$language = $languages[$current_language_id];

		return tmpl('modules.admin.l10n.phrases.index', compact('languages', 'language'));
	}

	/**
	 * @param request $request
	 *
	 * @return \mcr\http\response
	 * @throws \mcr\database\db_exception
	 */
	public function get_phrases(request $request)
	{
		$language_id = $request->id ? $request->id : 0;

		if ($language_id === 0) {
			$phrases = db::table('l10n_phrases')->pluck( 'phrase_value', 'phrase_key');
			$phrases = json_encode($phrases, JSON_UNESCAPED_UNICODE);
		} else {
			$language = db::table('l10n_languages')->select('phrases')->where('id', $language_id)->first();
			$phrases = $language['phrases'];
		}

		return response()->header('Content-Type', 'application/json')->content($phrases, 200);
	}
}