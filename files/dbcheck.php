<?php
	session_start();
	if(!defined('included')) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}
	require('password.php');
	ini_set('display_errors', $debug);
	$server = $ini['host'];
	$port = 3306;
	$dbName = $ini['dbname'];
	$dbUsername = $ini['username'];
	$dbPassword = $ini['password'];
	$array = array();

/* //	Testing create_conn function - Future Release
	function create_conn($sql) {
		$dsn = 'mysql:host='.$GLOBALS['server'].';dbname='.$GLOBALS['dbName'].';port='.$GLOBALS['port'];
		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);
		$conn = new PDO($dsn, $dbUsername, $dbPassword, $options);
		$stmt = $conn->query($sql);
	}  */

	// check server connectivity
	try {
		$handle = fsockopen($server, $port);
		if ($handle) {$array['connTest'] = 'Pass';} 
		else {$array['connTest'] = 'Fail - Cannot connect to ' . $server . ':' . $port;}
		fclose($handle);
	}
	catch(PDOException $e){$array['connTest'] = 'Fail - Cannot connect to ' . $server . ':' . $port;} 
	// check Username/Password 
	try {
		$conn = new PDO('mysql:host='.$server, $dbUsername, $dbPassword);
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

	//check users exist
 	function usersExist() {
		$dsn = 'mysql:host='.$GLOBALS['server'].';dbname='.$GLOBALS['dbName'].';port='.$GLOBALS['port'];
		// Set options
		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);
		//Create a new PDO instance
		try {
			$conn = new PDO($dsn, $GLOBALS['dbUsername'], $GLOBALS['dbPassword'], $options);
			$temp = $conn->query("SELECT COUNT(*) FROM users WHERE isadmin = 1");
			$result = $temp->fetchColumn();
			if($result==0) {
 				$createUser = $conn->prepare("INSERT INTO `users` (`username`,`hash`,`fname`,`lname`,`isadmin`) VALUES ('admin','".password_hash('admin', PASSWORD_DEFAULT)."','System','Account',1)");
				$createUser->execute(); 
				return TRUE;
			} else {
   				$selectUsers = $conn->prepare("SELECT * FROM users WHERE username='admin'");
				$selectUsers->execute();
				$account = $selectUsers->fetchAll(PDO::FETCH_ASSOC);
				if(password_verify('admin',$account[0]['hash'])) {return TRUE;}
				else {return FALSE;}
 				return 'How did you get here?';
			}
		}
		catch (PDOException $e) {return 'There was a problem: '.$e;}
	}

	function tableExists($table) {	//https://stackoverflow.com/questions/1717495/check-if-a-database-table-exists-using-php-pdo
		$dsn = 'mysql:host='.$GLOBALS['server'].';dbname='.$GLOBALS['dbName'].';port='.$GLOBALS['port'];
		// Set options
		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);
		//Create a new PDO instance
		try {
			$conn = new PDO($dsn, $GLOBALS['dbUsername'], $GLOBALS['dbPassword'], $options);
			$temp = "SELECT 1 FROM ".$table." LIMIT 1";
			$temp = $conn->query($temp);
			return true;
		} 
		catch (PDOException $e) {
			return false;
		}
	}
	function create_tables() {
		$sql = "
		CREATE TABLE IF NOT EXISTS `expenses` (`id` bigint(11) AUTO_INCREMENT NOT NULL,`vehicle` int(11) NOT NULL,
		`date` date DEFAULT NULL,`description` mediumtext NOT NULL,`cost` decimal(10,2) NOT NULL,PRIMARY KEY (`id`));

		CREATE TABLE IF NOT EXISTS `customers` (`id` int(11) AUTO_INCREMENT NOT NULL,`username` varchar(255) NOT NULL,
		`hash` varchar(255) NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `username` (`username`));

		CREATE TABLE IF NOT EXISTS `files` (`id` mediumint(9) AUTO_INCREMENT NOT NULL,`vehicle` mediumint(9) NOT NULL,
		`filename` varchar(255) NOT NULL,`order` tinyint(4) NOT NULL,PRIMARY KEY (`id`));

		CREATE TABLE IF NOT EXISTS `owners` (`id` int(11) AUTO_INCREMENT NOT NULL,`name` varchar(255) NOT NULL,
		`email` varchar(255) DEFAULT NULL,`phone` varchar(14) DEFAULT NULL,PRIMARY KEY (`id`));

		CREATE TABLE IF NOT EXISTS `photos` (`id` mediumint(9) AUTO_INCREMENT NOT NULL,`vehicle` mediumint(9) NOT NULL,
		`filename` varchar(255) NOT NULL,`order` tinyint(4) NOT NULL,PRIMARY KEY (`id`));

		CREATE TABLE IF NOT EXISTS `users` (`id` int(11) AUTO_INCREMENT NOT NULL,`username` varchar(255) NOT NULL,
		`hash` varchar(255) NOT NULL,`fname` varchar(40) NOT NULL,`lname` varchar(40) NOT NULL,
		`isadmin` tinyint(1) NOT NULL DEFAULT 0,PRIMARY KEY (`id`),UNIQUE KEY `username` (`username`));

		CREATE TABLE IF NOT EXISTS `vehicles` (`id` int(11) AUTO_INCREMENT NOT NULL,`vin` varchar(17) DEFAULT NULL,
		`year` year(4) NOT NULL,`make` mediumtext NOT NULL,`model` mediumtext NOT NULL,`trim` mediumtext,
		`miles` int(6) DEFAULT NULL,`owner` tinyint(4) DEFAULT NULL,`purchdate` date DEFAULT NULL,
		`purchprice` decimal(10,0) DEFAULT NULL,`askprice` decimal(10,0) DEFAULT NULL,
		`intnotes` longtext COMMENT 'Internal-Only Notes',`pubnotes` longtext COMMENT 'Public Notes',
		`status` varchar(255) NOT NULL DEFAULT 'Draft',`insured` tinyint(1) NOT NULL DEFAULT 0,
		`payment` varchar(12) DEFAULT NULL,`paynotes` varchar(255) DEFAULT NULL,`buyer` int(11) DEFAULT NULL,
		PRIMARY KEY (`id`));";

		$dsn = 'mysql:host='.$GLOBALS['server'].';dbname='.$GLOBALS['dbName'].';port='.$GLOBALS['port'];
		// Set options
		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);
		//Create a new PDO instance
		try {
			$conn = new PDO($dsn, $GLOBALS['dbUsername'], $GLOBALS['dbPassword'], $options);
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			return true;
		} 
		catch (PDOException $e) {
			return $e->getMessage();
		}
	}
