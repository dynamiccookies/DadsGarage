<?php

	if (!isset($_SESSION['include'])) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	} else {unset($_SESSION['include']);}

	$_SESSION['include'] = true;
	require_once '../admin/secure.php';

	ini_set('display_errors', $ini['debug']);
	$server     = $ini['host'];
	$port       = $ini['port'];
	$dbName     = $ini['dbname'];
	$dbUsername = $ini['username'];
	$dbPassword = $ini['password'];

	// Check server connectivity
	function check_server($ini) {
		$server = $ini['host'];
		$port   = $ini['port'];

		$handle = fsockopen($server, $port, $error_code, $error_message);

		if ($handle) {
			fclose($handle);

			return true;
		} else {
			if ($_SESSION['debug']) error_log('Error Connecting to ' . $server . ':' . $port . ': (' . $error_code . ') ' . $error_message);

			return false;
		}
	}

	// Check Username/Password 
	function check_credentials($ini) {
		$server     = $ini['host'];
		$username   = $ini['username'];
		$password   = $ini['password'];

		try {
			$conn = new PDO('mysql:host=' . $server, $username, $password);
			
			// Set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$conn = null;

			return true;

		} catch (PDOException $e) {
			if ($_SESSION['debug']) error_log('Error Logging into ' . $server . ': ' . $e->getMessage());

			return false;
		}
	}

	// Check if DB exists
	function check_database($ini) {
		$server         = $ini['host'];
		$port           = $ini['port'];
		$database       = $ini['dbname'];
		$username       = $ini['username'];
		$password       = $ini['password'];
		$database_found = false;

		if (!empty($database)) {

			// Create connection string
			$dsn = 'mysql:host=' . $server . ';dbname=' . $database . ';port=' . $port;

			// Set options
			$options = array(
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_PERSISTENT         => true,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			);

			// Create a new PDO instance
			try {
				$conn           = new PDO($dsn, $username, $password, $options);
				$database_found = $conn->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . $database . "'");
				$database_found = $database_found->fetchColumn();
				$conn           = null;

				if ($database_found) {
					return true;
				} else {
					return false;
				}

			} catch (PDOException $e) {
				if ($_SESSION['debug']) error_log('Database Error: ' . $e->getMessage());
				
				return false;
			}
		} else {
			return false;
		}
	}

	// Check if admin exists
 	function admin_exists($ini) {
		$dsn = 'mysql:host=' . $ini['host'] . ';dbname=' . $ini['dbname'] . ';port=' . $ini['port'];
		// Set options
		$options = array(
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT         => true,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);

		// Create a new PDO instance
		try {
			$conn   = new PDO($dsn, $ini['username'], $ini['password'], $options);
			$admins = $conn->query("SELECT COUNT(*) FROM users WHERE isadmin = 1");
			$admins = $admins->fetchColumn();

			if ($admins == 0) {
 				$create = $conn->prepare(
					"INSERT INTO `users` (`username`,`hash`,`fname`,`lname`,`isadmin`) " .
					"VALUES ('admin','" . password_hash('admin', PASSWORD_DEFAULT) . "','System','Account',1)"
				);
				$create->execute();
				$conn = null;

				return true;
			} else {
   				$search = $conn->prepare("SELECT * FROM users WHERE username='admin'");
				$search->execute();
				$search = $search->fetchAll(PDO::FETCH_ASSOC);
				$conn   = null;

				if(!empty($search) && password_verify('admin', $search[0]['hash'])) {return true;}
				else {return false;}

 				return 'How did you get here?';
			}
		}
		catch (PDOException $e) {return 'There was a problem: ' . $e;}
	}

	function check_tables($ini, ...$tables) {
        if (!$tables) {$tables = array('customers', 'expenses', 'files', 'owners', 'photos', 'users', 'vehicles');}
		$dsn = 'mysql:host=' . $ini['host'] . ';dbname=' . $ini['dbname'] . ';port=' . $ini['port'];

		// Set PDO options
		$options = array(
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT         => true,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);

		//Create a new PDO instance
		try {
			$conn = new PDO($dsn, $ini['username'], $ini['password'], $options);
			foreach ($tables as $table) {$conn->query('SELECT 1 FROM ' . $table . ' LIMIT 1');}
			$conn = null;

			return true;
		}
		catch (PDOException $e) {
			error_log('ERROR in check_tables(): ' . $e->getMessage());
			
			return false;
		}
	}

	//create missing tables
	function create_tables($ini) {
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

		$dsn = 'mysql:host=' . $ini['host'] . ';dbname=' . $ini['dbname'] . ';port=' . $ini['port'];

		$options = array(
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT         => true,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);

		//Create a new PDO instance
		try {
			$conn   = new PDO($dsn, $ini['username'], $ini['password'], $options);
			$create = $conn->prepare($sql);
			$create->execute();
			$conn   = null;

			return true;
		} catch (PDOException $e) {return $e->getMessage();}
	}
