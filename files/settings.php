<?php
	define('included', TRUE);

	//Create/update config.ini.php
	if (!file_exists("config.ini.php") || isset($_POST['Save'])) {
		$file="<?php \n/*;\n[connection]\ndbname = \"".($_POST["dbname"]?:"")."\"\nhost = \"".($_POST["host"]?:"").
		"\"\nusername = \"".($_POST["username"]?:"")."\"\npassword = \"".($_POST["password"]?:"")."\"\nbranch = \"".($_POST["branch"]?:"")."\"\n*/\n?>";
		file_put_contents("config.ini.php", $file);
	}

	//Read config.ini.php
	$ini = parse_ini_file("config.ini.php");

	//Test validity of database, host, & credentials
	require_once("dbcheck.php");
	$hostChk = (!$ini["host"]?"Required Field":($array["connTest"]?($array["connTest"]!="Pass"?$array["connTest"]:""):""));
	$hostChk = ($hostChk!=""?" class=\"required\" title=\"".$hostChk."\"":" class=\"pass\" title=\"Host Connection Successful\"");
	if ($ini["host"]) {
		$dbChk = (!$ini["dbname"]?"Required Field":($array["dbTest"]?($array["dbTest"]!="Pass"?$array["dbTest"]:""):""));
		$dbChk = ($dbChk!=""?" class=\"required\" title=\"".$dbChk."\"":" class=\"pass\" title=\"Database Connection Successful\"");
		$userChk = (!$ini["username"]?"Required Field":($array["credTest"]?($array["credTest"]!="Pass"?$array["credTest"]:""):""));
		$userChk = ($userChk!=""?" class=\"required\" title=\"".$userChk."\"":" class=\"pass\" title=\"Login Successful\"");
		$passChk = (!$ini["password"]?"Required Field":($array["credTest"]?($array["credTest"]!="Pass"?$array["credTest"]:""):""));
		$passChk = ($passChk!=""?" class=\"required\" title=\"".$passChk."\"":" class=\"pass\" title=\"Login Successful\"");
	}

	//Check existence/create database tables
	if (strpos($hostChk,'pass') && strpos($dbChk,'pass') && strpos($userChk,'pass') && strpos($passChk,'pass')) {
 		if (!tableExists("customers") || !tableExists("expenses") || !tableExists("files") || 
			!tableExists("owners") || !tableExists("photos") || !tableExists("users") || !tableExists("vehicles")) { 
			if ($_POST['createTables']){
				$created_tables = create_tables();
			} else {
				$button = " <input type=\"Submit\" name=\"createTables\" value=\"Create Table(s)\">";
				$dbChk = str_replace('pass','warn',$dbChk);
				$dbChk = str_replace('Database Connection Successful','One or more tables are missing from the database.',$dbChk);
			}
		}
	}

	//Update Application from GitHub
	if (isset($_POST['Update'])) {
		require_once('update.php');
		update($_POST["branch"]);
	}

/* Testing Database Creation - Future Release
	if (substr_count($dbChk,"does not exist.")>0) {
		$mkDB="<form action=\"<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>\" method=\"post\"><input type=\"Submit\" name=\"mkDB\" value=\"Create Database\"></form>";
	}
	if ($_POST['mkDB']) {
		try {
			$conn = new PDO("mysql:host=".$ini['host'].";dbname=".$ini['dbname'], $ini["username"], $ini["password"]);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$sql = "CREATE DATABASE ".$ini['dbname'];
			// use exec() because no results are returned
			$conn->exec($sql);
			echo "Database created successfully<br>";
		} catch(PDOException $e){echo $sql."<br>".$e->getMessage();}
	} */
?>
<head>
	<style>
		body {text-align:center;}
		div {font-size:36px;font-weight:bold;}
		table {margin:auto;border-top:2px solid;border-bottom:2px solid;padding:15px;}
		td:first-child {text-align:right;font-weight:bold;}
		input[type=textbox], input[type=password] {width:350px;border-radius:4px;outline:none;}
		.required {box-shadow:0 0 5px #ff0000;border:2px solid #ff0000;}
		.warn {box-shadow:0 0 5px #ffff00;border:2px solid #ffff00;}
		.pass {box-shadow:0 0 5px #00c600;border:2px solid #00c600;}
	</style>
</head>
<body>
	<div>Settings Page</div><br/>
	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
		<table>
			<tr><td>Host Name:</td><td><input name="host" type="textbox"<?php echo $hostChk;?> value="<?php echo $ini["host"];?>"></td></tr>
			<tr><td nowrap>Database Name:</td><td><input name="dbname" type="textbox"<?php echo $dbChk;?> value="<?php echo $ini["dbname"];?>"></td></tr>
			<tr><td>Username:</td><td><input name="username" type="textbox"<?php echo $userChk;?> value="<?php echo $ini["username"];?>"></td></tr>
			<tr><td>Password:</td><td><input name="password" type="password"<?php echo $userChk;?> value="<?php echo $ini["password"];?>"></td></tr>
			<tr><td>Git Branch:</td><td><input name="branch" type="textbox" value="<?php echo $ini["branch"];?>"></td></tr>
		</table>
		<br/><?php echo ($created_tables?($created_tables===true?"Tables created successfully.<br/>":"There was a problem creating the table(s).<br/>"):"");?>
		<?php echo ($_POST['results']?:"");?>
		<input type="Submit" name="Save" value="Save">&nbsp;
		<input type="Submit" name="Update" value="Update Application" title="Install updates from GitHub">
		<?php echo $button?:"";?>
	</form>
</body>
