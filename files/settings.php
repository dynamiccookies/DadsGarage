<?php
	session_start();
	define('included', TRUE);
	require_once($files."header.php");
	
	//Pull list of branches from GitHub
	ini_set("allow_url_fopen", 1);
	$options  = array('http' => array('user_agent'=> $_SERVER['HTTP_USER_AGENT']));
	$url = "https://api.github.com/repos/dynamiccookies/dadsgarage/branches";
	$branches = json_decode(file_get_contents($url, false, stream_context_create($options)),true);

	$userMessage = "";
	//Create/update config.ini.php
	if (!file_exists("config.ini.php") || isset($_POST['Save'])) {
//		if (!file_exists("config.ini.php")) {$commit=$branches[0]['commit']['sha'];} //Need to find a way to get the correct array item number
		$file="<?php \n/*;\n[connection]\ndbname = \"".($_POST["dbname"]?:"")."\"\nhost = \"".($_POST["host"]?:"").
		"\"\nusername = \"".($_POST["username"]?:"")."\"\npassword = \"".($_POST["password"]?:"")."\"\nbranch = \"".
		($_POST["branch"]?:"")."\"\ncommit = \"".($commit?:"")."\"\n*/\n?>";
		file_put_contents("config.ini.php", $file);
	}

	//Read config.ini.php
	$ini = parse_ini_file("config.ini.php");

	//Test for GitHub updates
	foreach($branches as $branch) {
		if ($branch['name']==$ini['branch']) {if ($branch['commit']['sha']!=$ini['commit']) {$updateAvailable=TRUE;}}
	}
	
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
		$dbExists = TRUE;
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
		//Check existence/create default Admin user
		$userExists=usersExist();
 		if ($userExists===TRUE) {
			$userMessage = "The default username and password are 'admin'.<br/>
			<a href='../admin'>Click here to change the password.</a><br/><br/>";
		} elseif (!$userExists===FALSE) {
			if (strpos($userExists,"Base table or view not found")!==FALSE) {
				$userMessage = "The Users table does not exist.<br/>
				Please click the Create Table(s) button to create it.<br/><br/>";
			} elseif (strpos($userExists,"Access denied for user '".$_POST['username']."'")) {
				$userMessage = "The username or password is incorrect.<br/><br/>";
			} else {$userMessage = $userExists;}
		} elseif($userExists===FALSE) {require("../admin/secure.php");}
	}

	//Update Application from GitHub
	if (isset($_POST['Update'])) {
  		try {
			$repository = 'https://github.com/dynamiccookies/DadsGarage/'; //URL to GitHub repository
			$repBranch = $_POST['branch']?:"master";
			$source = 'DadsGarage-'.$repBranch; //RepositoryName-Branch
			$redirectURL = 'settings.php'; //Redirect URL - Leave blank for no redirect
			$file = file_put_contents(dirname(__DIR__)."/install.zip", fopen($repository."archive/".$repBranch.".zip", 'r'), LOCK_EX);
			if($file === FALSE) die("Error Writing to File: Please <a href=\"".$repository."issues/new?title=Installation - Error Writing to File\">click here</a> to submit a ticket.");
			$zip = new ZipArchive;
			$res = $zip->open(dirname(__DIR__).'/install.zip');
			if ($res === TRUE) {
				for($i=0; $i<$zip->numFiles; $i++) {
					$name = $zip->getNameIndex($i);
					if (strpos($name, "{$source}/") !== 0) continue;
					$file = dirname(__DIR__).'/'.substr($name, strlen($source)+1);
					if (substr($file,-1)!='/') {
						$dir = dirname($file);
						if (!is_dir($dir)) mkdir($dir, 0777, true);
						$fread = $zip->getStream($name);
						$fwrite = fopen($file, 'w');
						while ($data = fread($fread, 1024)) {fwrite($fwrite, $data);}
						fclose($fread);
						fclose($fwrite);
					}
				}
				$zip->close();
				unlink(dirname(__DIR__).'/install.zip');
				unlink(dirname(__DIR__).'/.gitignore');
	
				for($i=0,$size=count($branches);$i<$size;++$i) {
					if($branches[$i]['name']==$_POST['branch']){$commit=$branches[$i]['commit']['sha'];}
				}
				$file="<?php \n/*;\n[connection]\ndbname = \"".($_POST["dbname"]?:"")."\"\nhost = \"".($_POST["host"]?:"").
				"\"\nusername = \"".($_POST["username"]?:"")."\"\npassword = \"".($_POST["password"]?:"")."\"\nbranch = \"".
				($_POST["branch"]?:"")."\"\ncommit = \"".$commit."\"\n*/\n?>";
				file_put_contents("config.ini.php", $file);
	
				if ($redirectURL) echo "<meta http-equiv=refresh content=\"0; URL=".$redirectURL."\">";
				$_SESSION['results'] = 'Application Updated Successfully!';
			} else {
				echo "Error Extracting Zip: Please <a href=\"".$project."issues/new?title=Installation - Error Extracting\">click here</a> to submit a ticket.";
				$_SESSION['results'] = 'Something went wrong!';
			}
		} catch (Exception $e){$_SESSION['results'] = 'Something went wrong!<br/>'.$e;}
	}
	if (isset($_SESSION['results']) && !isset($_SESSION['run'])) {
		$_SESSION['run']=1;
	} elseif (isset($_SESSION['run']) && $_SESSION['run']==3) {
		unset($_SESSION['results']);
		unset($_SESSION['run']);
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
<body class="settings darkbg">
	<div id="adminSidenav" class="adminsidenav"><?php require_once("menu.php");?></div>
	<div id="adminMain">
		<div class="adminContainer" onclick="myFunction(this)">
		  <div class="bar1"></div>
		  <div class="bar2"></div>
		  <div class="bar3"></div>
		</div>
		<div id="mainContainer" class="bgblue bord5 p15 b-rad15 m-lrauto center m-top25">
			<div class="settings-header">Settings Page</div><br/>
			<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
				<table class="settings">
					<tr><td>Host Name:</td><td><input name="host" type="textbox"<?php echo $hostChk;?> value="<?php echo $ini["host"];?>"></td></tr>
					<tr><td nowrap>Database Name:</td><td><input name="dbname" type="textbox"<?php echo $dbChk;?> value="<?php echo $ini["dbname"];?>"></td></tr>
					<tr><td>Username:</td><td><input name="username" type="textbox"<?php echo $userChk;?> value="<?php echo $ini["username"];?>"></td></tr>
					<tr><td>Password:</td><td><input name="password" type="password"<?php echo $userChk;?> value="<?php echo $ini["password"];?>"></td></tr>
					<tr><td>Git Branch:</td><td style="text-align:left;">
						<select name="branch">
							<?php foreach ($branches as $branch) {
								$branch = $branch['name'];
								if($ini["branch"]=="") {$ini["branch"]="master";}
								echo "<option value'$branch'".($branch==$ini["branch"]?" selected":"").">$branch</option>";}?>
						</select>
					</td></tr>
				</table>
				<br/>
				<?php 
					if (isset($_SESSION['run'])) {
						echo $_SESSION['results']."<br/>";
						$_SESSION['run']+=1;
					}
					echo ($created_tables?($created_tables===true?
						"Tables created successfully.<br/>":"There was a problem creating the table(s).<br/>"):"");
					echo $userMessage;
					echo ($updateAvailable?"<div class='red bold'>Update Available</div><br/>":"");
					echo "<input type=\"Submit\" name=\"Save\" value=\"Save\">&nbsp;";
					echo "<input type=\"Submit\" name=\"Update\" value=\"Update Application\" title=\"Install updates from GitHub\">";
					if (dbExists) {echo $button?:"";}
				?>
			</form>
		</div>
	</div>
	<div class="commit"><?php echo $ini['commit'];?></div>
	<script src="admin.js"></script>
</body>
