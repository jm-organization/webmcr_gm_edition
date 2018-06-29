<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 27.06.2018
 * @Time         : 22:58
 *
 * @Documentation:
 */

namespace mcr;


class document
{
	/*
	 * Constructor for document
	 */
	public function __constructor($document = '') {
	    //
	}
	
	public function render()
	{

	}

	public static function template($file, array $data = [])
	{
		ob_start();

		load_if_exist($file);

		return ob_get_clean();
	}
}