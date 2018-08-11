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
 * @Time  : 11:11
 */

namespace mcr\core\registry;


use mcr\core\core_v2;

/**
 * Interface component
 *
 * Интерфейс которій описвает компонент регистра.
 * Если ваш класс реализует данный интерфейс,
 * то он может быть добавлен в регитр.
 *
 * @package mcr\core\registry
 */
interface component
{
	/**
	 * Мотод должен возвращать строковое
	 * абстрактное имя комопнента.
	 *
	 * @return string
	 */
	public function get_abstract_name();

	/**
	 * Вызывается, когда происходит
	 * инициализация - добовление компонента
	 * в реестр.
	 *
	 * Должен возвращать экземпляр класса component
	 *
	 * @return component
	 */
	public function boot();
}