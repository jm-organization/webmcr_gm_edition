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
use mcr\http\routing\router;

/**
 * Created in JM Organization.
 *
 * @e-mail: admin@jm-org.net
 * @Author: Magicmen
 *
 * @Date  : 29.07.2018
 * @Time  : 15:05
 */

if (file_exists(__DIR__ . "/../init-krumo.php")) require_once(__DIR__ . "/../init-krumo.php");

define('MCR', '');

include __DIR__ . '/../bootstrap/constants.php';

define('DIR_INSTALL', __DIR__ . '/');
define('DIR_INSTALL_LAYOUTS', DIR_INSTALL . 'insertions/');

session_save_path(MCR_ROOT . 'data/tmp');
if (!session_start()) { session_start(); }

include __DIR__ . '/../src/mcr/installer/install_initializer.php';
include __DIR__ . '/../src/mcr/installer/install_helpers.php';

$installer->run_installation();