<?php
require '../../../../zb_system/function/c_system_base.php';
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {$zbp->ShowError(6);die();}
if (!$zbp->CheckPlugin('KODExplorer')) {$zbp->ShowError(48);die();}
	ob_start();
	header("Content-Security-Policy: default-src 'self' data: blob:; img-src * data: blob:; media-src * data: blob:; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; worker-src blob:");
	include ('config/config.php');
	$app = new Application();
	init_config();
	$app->run();
?>
