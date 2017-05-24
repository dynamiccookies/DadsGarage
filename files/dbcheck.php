<?php
	//The code on this page was borrowed and tweaked from: https://github.com/rconfig/rconfig/blob/master/www/install/lib/ajaxHandlers/ajaxDbTests.php
	ini_set('display_errors', 1);
	error_reporting(E_ALL ^ E_NOTICE);
	$server = $ini["host"];
	$port = 3306;
	$dbName = $ini["dbname"];
	$dbUsername = $ini["username"];
	$dbPassword = $ini["password"];
	$array = array();

	// chech server connectivity
	$handle = fsockopen($server, $port);
	if ($handle) {$array['connTest'] = '<font style="font-weight:bold;color:green;">Pass </font>';} 
	else {$array['connTest'] = '<font style="font-weight:bold;color:red;">Fail - Cannot connect to ' . $server . ':' . $port . '</font>';}
	fclose($handle);
	// check Username/Password 
	try {
		$conn = new PDO("mysql:host=$server", $dbUsername, $dbPassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$link = true; 
	}
	catch(PDOException $e){$link = false;}
	if ($link) {
		$array['credTest'] = '<font style="font-weight:bold;color:green;">Pass</font>';
	} else {
		$array['credTest'] = '<font style="font-weight:bold;color:red;">Fail -  Could not connect to Database Server. Check your settings!</font>';
	}
	//check if DB exists
	if(isset($dbName)){
		$dsn = 'mysql:host=' . $server . ';dbname=' . $dbName . ';port=' . $port;
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
		if ($db_selected == 1) {
			$array['dbTest'] = '<font style="font-weight:bold;color:green;">Pass</font>';
		} elseif ($db_selected == 0) {
			$array['dbTest'] = '<font style="font-weight:bold;color:red;">Fail - '.$dbName.' does not exist</font>';
		}
	   
	} else {
		$array['dbTest'] = '<font style="font-weight:bold;color:red;">Fail - Database Name was not entered</font>';
	}
	if($sqlError && $e->getCode() != '1049' && $e->getCode() != '1045') {// here we expect the Count query above to fail, as a zero value should be returned. But we still want other errors to appear if needed. 
		$array['dbTest'] = '<font style="font-weight:bold;color:red;">Fail - '.$sqlError.'</font>';	
	}
	$conn = null;
