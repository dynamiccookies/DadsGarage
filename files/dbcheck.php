<?php

	if (!isset($_SESSION['include'])) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	} else {unset($_SESSION['include']);}

	$_SESSION['include'] = true;
	require_once '../admin/secure.php';

	ini_set('display_errors', $_SESSION['debug']);
	$server      = $ini['host'];
	$port        = 3306;
	$dbName      = $ini['dbname'];
	$dbUsername  = $ini['username'];
	$dbPassword  = $ini['password'];
	$array       = array();
	$dbSelected  = 0;

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

	// Check server connectivity
	try {
		$handle = fsockopen($server, $port);
		if ($handle) {$array['connTest'] = 'Pass';} 
		else {$array['connTest'] = 'Fail - Cannot connect to ' . $server . ':' . $port;}
		fclose($handle);
	}
	catch(PDOException $e){$array['connTest'] = 'Failure Caught - Cannot connect to ' . $server . ':' . $port;}
	
	// Check Username/Password 
	try {
		$conn = new PDO('mysql:host='.$server, $dbUsername, $dbPassword);
		
		// Set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$array['credTest'] = 'Pass';
	}
	catch(PDOException $e){
		$array['credTest'] = 'Fail - Check ' . ($array['connTest'] != 'Pass' ? 'the host address and ' : '') . 'your username & password.';
	}
	
	// Check if DB exists
	if(!empty($dbName)){

		// Create connection string
		$dsn = 'mysql:host=' . $server . ';dbname=' . $dbName . ';port=' . $port;

		// Set options
		$options = array(
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT         => true,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);

		// Create a new PDO instance
		try {
			$conn        = new PDO($dsn, $dbUsername, $dbPassword, $options);
			$stmt        = $conn->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . $dbName . "'");
			$dbSelected  = $stmt->fetchColumn();
		}
		catch (PDOException $e) {
			$sqlError = $e->getMessage();
			if ($_SESSION['debug']) echo "<script>console.log('SQL Error: " . $sqlError . "');</script>";
		}  
		
		if ($dbSelected == 1) {$array['dbTest'] = 'Pass';} 
		elseif ($dbSelected == 0 && $array['credTest'] == 'Pass') {$array['dbTest'] = 'Fail - ' . $dbName . ' does not exist.';}
		elseif ($dbSelected == 0 && $array['credTest'] != 'Pass' && $array['connTest'] == 'Pass') {$array['dbTest'] = 'Fail - Check your username & password.';}
		elseif ($dbSelected == 0 && $array['credTest'] != 'Pass') {$array['dbTest'] = 'Fail - Double check host address.';}
	}
	$conn = null;

	// Check if admin exists
 	function adminExists() {
		$dsn = 'mysql:host=' . $GLOBALS['server'] . ';dbname=' . $GLOBALS['dbName'] . ';port=' . $GLOBALS['port'];
		// Set options
		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);
		// Create a new PDO instance
		try {
			$conn        = new PDO($dsn, $GLOBALS['dbUsername'], $GLOBALS['dbPassword'], $options);
			$adminExists = $conn->query("SELECT COUNT(*) FROM users WHERE isadmin = 1");
			$adminExists = $adminExists->fetchColumn();
			if($adminExists == 0) {
 				$createAdmin = $conn->prepare("INSERT INTO `users` (`username`,`hash`,`fname`,`lname`,`isadmin`) VALUES ('admin','" . 
					password_hash('admin', PASSWORD_DEFAULT) . "','System','Account',1)");
				$createAdmin->execute(); 
				return TRUE;
			} else {
   				$findAdmin = $conn->prepare("SELECT * FROM users WHERE username='admin'");
				$findAdmin->execute();
				$findAdmin = $findAdmin->fetchAll(PDO::FETCH_ASSOC);
				if(!empty($findAdmin) && password_verify('admin',$findAdmin[0]['hash'])) {return TRUE;}
				else {return FALSE;}
 				return 'How did you get here?';
			}
		}
		catch (PDOException $e) {return 'There was a problem: ' . $e;}
	}

	//check if a specific table exists
	function tableExists($table) {	//https://stackoverflow.com/questions/1717495/check-if-a-database-table-exists-using-php-pdo
		$dsn = 'mysql:host=' . $GLOBALS['server'] . ';dbname=' . $GLOBALS['dbName'] . ';port=' . $GLOBALS['port'];
		// Set options
		$options = array(
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT         => true,
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

	//create missing tables
	function createTables() {
		$sql = "
			CREATE TABLE IF NOT EXISTS `customers` (
				`id` int(11) AUTO_INCREMENT NOT NULL,
				`username` varchar(255) NOT NULL,
				`hash` varchar(255) NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `username` (`username`));

			CREATE TABLE IF NOT EXISTS `expenses` (
				`id` bigint(11) AUTO_INCREMENT NOT NULL,
				`vehicle` int(11) NOT NULL,
				`date` date DEFAULT NULL,
				`description` mediumtext NOT NULL,
				`cost` decimal(10,2) NOT NULL,
				PRIMARY KEY (`id`));

			CREATE TABLE IF NOT EXISTS `files` (
				`id` mediumint(9) AUTO_INCREMENT NOT NULL,
				`vehicle` mediumint(9) NOT NULL,
				`filename` varchar(255) NOT NULL,
				`order` tinyint(4) DEFAULT 0 NOT NULL,
				PRIMARY KEY (`id`));

			CREATE TABLE IF NOT EXISTS `owners` (
				`id` int(11) AUTO_INCREMENT NOT NULL,
				`name` varchar(255) NOT NULL,
				`email` varchar(255) DEFAULT NULL,
				`phone` varchar(14) DEFAULT NULL,
				PRIMARY KEY (`id`));

			CREATE TABLE IF NOT EXISTS `photos` (
				`id` mediumint(9) AUTO_INCREMENT NOT NULL,
				`vehicle` mediumint(9) NOT NULL,
				`filename` varchar(255) NOT NULL,
				`order` tinyint(4) DEFAULT 0 NOT NULL,
				PRIMARY KEY (`id`));

			CREATE TABLE IF NOT EXISTS `users` (
				`id` int(11) AUTO_INCREMENT NOT NULL,
				`username` varchar(255) NOT NULL,
				`hash` varchar(255) NOT NULL,
				`fname` varchar(40) NOT NULL,
				`lname` varchar(40) NOT NULL,
				`isadmin` tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `username` (`username`));

			CREATE TABLE IF NOT EXISTS `vehicles` (
				`id` int(11) AUTO_INCREMENT NOT NULL,
				`vin` varchar(17) DEFAULT NULL,
				`year` year(4) NOT NULL,
				`make` mediumtext NOT NULL,
				`model` mediumtext NOT NULL,
				`trim` mediumtext,
				`miles` int(6) DEFAULT NULL,
				`owner` int(11) DEFAULT NULL,
				`askprice` decimal(10,0) DEFAULT NULL,
				`intnotes` longtext COMMENT 'Internal-Only Notes',
				`pubnotes` longtext COMMENT 'Public Notes',
				`status` varchar(255) NOT NULL DEFAULT 'Draft',
				`insured` tinyint(1) NOT NULL DEFAULT 0,
				`payment` varchar(12) DEFAULT NULL,
				`paynotes` varchar(255) DEFAULT NULL,
				`buyer` int(11) DEFAULT NULL,
				`purchase_price` DECIMAL NULL DEFAULT NULL,
				`purchase_date` DATE NULL DEFAULT NULL, 
				`sold_price` DECIMAL NULL DEFAULT NULL,
				`sold_date` DATE NULL DEFAULT NULL`,
				PRIMARY KEY (`id`));
		";



		$dsn = 'mysql:host=' . $GLOBALS['server'] . ';dbname=' . $GLOBALS['dbName'] . ';port=' . $GLOBALS['port'];

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
