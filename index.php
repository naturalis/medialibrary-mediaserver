<?php
date_default_timezone_set('Europe/Amsterdam');
set_include_path('.');
define('APPLICATION_PATH', __DIR__);

use nl\naturalis\medialib\server\MediaServer;

try {
	
	include 'autoload.php';
	
	$mediaServer = new MediaServer();
	$mediaServer->handleRequest();
	
}
catch (\Exception $e) {
	header('Content-Type:text/plain');
	echo "\n" . $e->getTraceAsString();
	echo "\n" . basename($e->getFile()) . ' (' . $e->getLine() . '): ' . $e->getMessage();
}
