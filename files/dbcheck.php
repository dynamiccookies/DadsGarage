<?php
	//The code on this page was borrowed and tweaked from: https://github.com/rconfig/rconfig/blob/master/www/install/lib/ajaxHandlers/ajaxDbTests.php
	ini_set('display_errors', 0);
	$server = $ini["host"];
	$port = 3306;
	$dbName = $ini["dbname"];
	$dbUsername = $ini["username"];
	$dbPassword = $ini["password"];
	$array = array();

	// check server connectivity
	try {
		$handle = fsockopen($server, $port);
		if ($handle) {$array['connTest'] = 'Pass';} 
		else {$array['connTest'] = 'Fail - Cannot connect to ' . $server . ':' . $port;}
		fclose($handle);
	}
	catch(PDOException $e){$array['connTest'] = 'Fail - Cannot connect to ' . $server . ':' . $port."'catch'";} 
	// check Username/Password 
	try {
		$conn = new PDO("mysql:host=".$server, $dbUsername, $dbPassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$array['credTest'] = 'Pass';
	}
	catch(PDOException $e){
		$array['credTest'] = 'Fail - Check '.($array['connTest']!='Pass'?'the host address, ':'').'your username & password.';
	}
	//check if DB exists
	if(isset($dbName)){
		$dsn = 'mysql:host='.$server.';dbname='.$dbName.';port='.$port;
		// Set options
		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);
		//Create a new PDO instance
		try {
			$conn = new PDO($dsn, $dbUsername, $dbPassword, $options);
			$stmt = $conn->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".$dbName."'");
			$db_selected = $stmt->fetchColumn();
		}
		// Catch any errors
		catch (PDOException $e) {
			$sqlError = $e->getMessage();
		}    
		if ($db_selected == 1) {$array['dbTest'] = 'Pass';} 
		elseif ($db_selected == 0 && $array['credTest'] == 'Pass') {$array['dbTest'] = 'Fail - '.$dbName.' does not exist.';}
		elseif ($db_selected == 0 && $array['credTest'] != 'Pass' && $array['connTest'] == 'Pass') {$array['dbTest'] = 'Fail - Check your username & password.';}
		elseif ($db_selected == 0 && $array['credTest'] != 'Pass') {$array['dbTest'] = 'Fail - Double check host address.';}
	} 
	$conn = null;
