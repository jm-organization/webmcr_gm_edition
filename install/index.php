<?php

if (file_exists(__DIR__ . "/../init-krumo.php")) require_once(__DIR__ . "/../init-krumo.php");

define('MCR', '');

session_save_path(dirname(dirname(__FILE__)) . '/data/tmp');
if (!session_start()) {
	session_start();
}

include __DIR__ . '/../bootstrap/constants.php';
include __DIR__ . '/../bootstrap/autoloader.php';

use mcr\config;


$configs = new config();

function config($namespace)
{
	global $configs;

	$namespace = explode('::', $namespace);

	if (count($namespace) == 2) {
		$config_root = $namespace[0];
		$config_param = $namespace[1];

		$config = @$configs->$config_root;

		if (!empty($config)) {
			$config_param_items = explode('.', $config_param);

			foreach ($config_param_items as $item) {
				if (array_key_exists($item, $config)) {
					$config = $config[$item];
				}
			}

			return $config;
		}
	} else {
		$property = $namespace[0];

		return @$configs->$property;
	}

	return null;
}


$install = new \install\install();

$step = $install->init_step();

?>

<!DOCTYPE HTML>
<html>
	<head>
		<meta charset="utf-8">
		<title><?=config('main::s_name')?> — <?=$install->title?></title>

		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<meta name="description" content="<?=config('main::s_about')?>">
		<meta name="keywords" content="<?=config('main::s_keywords')?>">
		<meta name="author" content="NC22 &amp; Qexy.org">

		<base href="<?=URL_ROOT?>">

		<link rel="stylesheet" type="text/css" href="/install/theme/css/global.css">
		<link rel="stylesheet" type="text/css" href="/install/theme/css/global-responsive.css">

		<script type="text/javascript" src="/install/theme/js/jquery.min.js"></script>
		<script type="text/javascript" src="/install/theme/js/global.js"></script>

		<?=$install->header?>
	</head>

	<body>

		<div class="header">
			<div class="container">
				<div class="title"><?=config('main::s_name'); ?></div>
				<div class="text"><?=config('main::s_about'); ?></div>
				<div class="cake"></div>
			</div>
		</div>

		<div class="container">

			<?php

				echo $step;

			?>

		</div>

		<div class="footer">
			<div class="container">
				<div class="block-left"><?=$install->lng['copy_pre']; ?> <?=config('main::s_name'); ?> <?=$install->lng['copy_app']; ?></div>
				<div class="block-right"><?=FEEDBACK?> | <?=VERSION?></div>
			</div>
		</div>

		<?php

			echo $install->get_notify();

		?>

	</body>

</html>

<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////