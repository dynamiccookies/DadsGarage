<?php

	if (!isset($_SESSION['include'])) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	} else {unset($_SESSION['include']);}

	//Update Application from GitHub
	function update_application($branch) {
		try {
			ini_set('allow_url_fopen', 1);
			$repository  = 'https://github.com/dynamiccookies/DadsGarage/';
			$source      = 'DadsGarage-' . $branch;

			// Download repository files as 'install.zip' and store in '$file' variable
			$file = file_put_contents(dirname(__DIR__) . '/install.zip', fopen($repository . 'archive/' . $branch . '.zip', 'r'), LOCK_EX);

			// If '$file' variable does not contain data, return false
			if ($file === false) {
				if (isset($_SESSION['debug'])) error_log('Error creating install.zip file');

				return false;
			}

			$zip = new ZipArchive;

			// Open zip file and store contents in '$res' variable
			$res = $zip->open(dirname(__DIR__) . '/install.zip');
			if ($res === true) {
				for ($i = 0; $i < $zip->numFiles; $i++) {
	    			$name = $zip->getNameIndex($i);
		    		if (strpos($name, $source . '/') !== 0) continue;
				    $file = dirname(__DIR__) . '/' . substr($name, strlen($source) + 1);
					if (substr($file, -1) != '/') {
						if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
						$fread  = $zip->getStream($name);
						$fwrite = fopen($file, 'w');
						while ($data = fread($fread, 1024)) {fwrite($fwrite, $data);}
						fclose($fread);
						fclose($fwrite);
					}
				}
				$zip->close();
			
				// Delete the following files
				unlink(dirname(__DIR__) . '/install.zip');
				unlink(dirname(__DIR__) . '/README.md');
				unlink(dirname(__DIR__) . '/.gitignore');
				unlink(dirname(__DIR__) . '/install.php');

				return true;
    		} else {
				if (isset($_SESSION['debug'])) error_log('Error extracting install.zip file');

				return false;
    		}
		} catch (Exception $e) {
			if (isset($_SESSION['debug'])) error_log('Application failed to update: ' . $e->getMessage());

			return false;
		}
	}

	//(Re)Create config.ini.php file
	function update_config($branch = null, $commit = null) {

		if (file_exists('../includes/config.ini.php')) {
			$_SESSION['include'] = true;
			require_once '../admin/secure.php';

			$ini = parse_ini_file('../includes/config.ini.php');
		}

		if (isset($_POST['dbname']))     {$dbname   = $_POST['dbname'];}
		elseif (isset($ini['dbname']))   {$dbname   = $ini['dbname'];}
		else                             {$dbname   = '';}

		if (isset($_POST['host']))       {$host     = $_POST['host'];}
		elseif (isset($ini['host']))     {$host     = $ini['host'];}
		else                             {$host     = '';}

		if( isset($_POST['username']))   {$username = $_POST['username'];}
		elseif (isset($ini['username'])) {$username = $ini['username'];}
		else                             {$username = '';}

		if (isset($_POST['password']))   {$password = $_POST['password'];}
		elseif (isset($ini['password'])) {$password = $ini['password'];}
		else                             {$password = '';}

		if (isset($_POST['debug']))      {$debug    = $_POST['debug'];}
		elseif (isset($ini['debug']))    {$debug    = $ini['debug'];}
		else                             {$debug    = 'false';}

		if (!empty($branch))             {}
		elseif (isset($ini['branch']))   {$branch   = $ini['branch'];}
		else                             {$branch   = '';}

		if (!empty($commit))             {}
		elseif (isset($ini['commit']))   {$commit   = $ini['commit'];}
		else                             {$commit   = '';}

		if (isset($_POST['port']))       {$port     = $_POST['port'];}
		elseif (isset($ini['port']))     {$port     = $ini['port'];}
		else                             {$port     = '';}

        if (empty($port)) $port = 3306;

		file_put_contents('../includes/config.ini.php', 
			"<?php \n/*;\n[connection]\n" .
				"dbname    = '" . $dbname   . "'\n" .
				"host      = '" . $host     . "'\n" .
				"username  = '" . $username . "'\n" .
				"password  = '" . $password . "'\n" .
				"debug     = '" . $debug    . "'\n" .
				"branch    = '" . $branch   . "'\n" .
				"commit    = '" . $commit   . "'\n" .
				"port      = '" . $port     . "'\n" .
				"bitlyuser = '" . ''        . "'\n" .
				"bitlyAPI  = '" . ''        . "'\n" . 
			"*/\n?>"
		);

		return parse_ini_file('../includes/config.ini.php');
	}

	//Pull branch info from GitHub
	function getJSON($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/dynamiccookies/dadsgarage/' . $url); 
		curl_setopt($ch, CURLOPT_USERAGENT, 'dynamiccookies/DadsGarage');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$results = json_decode(curl_exec($ch), true);
		curl_close($ch);
		return $results;
	}
