<?php
	if (!(file_exists("config.ini.php")) {echo "<meta http-equiv=refresh content=\"0; URL=files/settings.php\">";} 
	$ini = parse_ini_file("config.ini.php");
	try {
		$db = new PDO('mysql:dbname='.$ini["dbname"].';host='.$ini["host"], $ini["username"], $ini["password"]);
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
?>
