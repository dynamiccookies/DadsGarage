<?php
	// Do not allow a direct connection to this file
	if(!defined('included')) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}

	// If the 'config.ini.php' file does not exist, redirect to the 'settings.php' page so it can be created
	if (!(file_exists($files . 'config.ini.php'))) {echo "<meta http-equiv=refresh content=\"0; URL=" . $files . "settings.php\">";} 

	// Store the contents of 'config.ini.php' into the $ini variable
	$ini = parse_ini_file($files . 'config.ini.php');

	// Try to create a connection to the database with the username and password stored in the 'config.ini.php' file
	try {$db = new PDO('mysql:dbname=' . $ini['dbname'] . ';host=' . $ini['host'], $ini['username'], $ini['password']);} 
	catch (Exception $e) {echo 'Caught exception: ',  $e->getMessage(), '\n';}
?>
