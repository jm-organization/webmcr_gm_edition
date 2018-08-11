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
 * @Date  : 11.08.2018
 * @Time  : 19:04
 */

namespace mcr\core\configs;


interface provider
{
	/**
	 * Мотод должен возвращать строковое
	 * абстрактное имя поставщика конфигов.
	 *
	 * @return string
	 */
	public function get_abstract_name();

	/**
	 * Вызывается, когда происходит
	 * инициализация - привязка поставщика,
	 * к оснеовному классу конифгов
	 * в реестр.
	 *
	 * @param config $configs
	 */
	public function boot(config $configs);
}