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
 * @Date: 26.06.2018 (rewrite old index file)
 * @Time: 19:33
 */

// Фиксируем время начала загрузки страници.
define("DEBUG_PLT", microtime(true));
define('MCR', '');

if (file_exists('./init-krumo.php')) require_once("./init-krumo.php");

// Загружаем файл, где определяются константы
require 'bootstrap/constants.php';

// Загружаем ядро
require 'src/mcr/filter.php';
require 'bootstrap/core.php';

require 'bootstrap/helpers.php';

$application->run();