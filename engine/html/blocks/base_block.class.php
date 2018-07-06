<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 05.07.2018
 * @Time         : 23:32
 *
 * @Documentation:
 */

namespace mcr\html\blocks;


/**
 * Interface base_block
 *
 * @package mcr\html\blocks
 *
 * Основная директория блока \themes\__THEME__\blocks\__BLOCK__\
 *
 * @property string $data		  - Содержит данные, которые необходимо вывести по шаблону
 * @property string $styles		  - Содержит короткое имя файласо стилями блока
 * @property string $head_scripts -	Содержит короткое имя скриптов блока, которые необходимо загрузить в head
 * @property string $body_scripts -	-- " -- , которые необходимо загрузить в body
 * @property string $tmpl		  - Содержит шаблон блока
 */
interface base_block
{
	/**
	 * base_block constructor.
	 */
	public function __construct();

	/**
	 * Инициализатор блока.
	 * Принимает конфиги блока.
	 *
	 * @param array $configs - конфиги блока, которые необходимы для его работы.
	 *
	 * @return base_block
	 */
	public function init(array $configs);
}