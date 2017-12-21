<?php
	session_start();
	define('included', TRUE);
	require_once($files."header.php");
	ini_set("allow_url_fopen", 1);
	$userMessage = "";

	//Create/update config.ini.php
	if (!file_exists("config.ini.php") || isset($_POST['Save'])) {
		$file="<?php \n/*;\n[connection]\ndbname = \"".($_POST["dbname"]?:"")."\"\nhost = \"".($_POST["host"]?:"").
		"\"\nusername = \"".($_POST["username"]?:"")."\"\npassword = \"".($_POST["password"]?:"")."\"\nbranch = \"".
		($_POST["branch"]?:($_SESSION["branch"]?:""))."\"\ncommit = \"".($_SESSION['inicommit']?:"")."\"\n*/\n?>";
		file_put_contents("config.ini.php", $file);
	}

	//Read config.ini.php
	$ini = parse_ini_file("config.ini.php");
	$_SESSION['inicommit']=$ini['commit'];
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
			$userMessage = "The default username and password are 'admin'.<br/><a href='../admin'>Click here to change the password.</a><br/><br/>";
		} elseif (!$userExists===FALSE) {
			if (strpos($userExists,"Base table or view not found")!==FALSE) {
				$userMessage = "The Users table does not exist.<br/>Please click the Create Table(s) button to create it.<br/><br/>";
			} elseif (strpos($userExists,"Access denied for user '".$_POST['username']."'")) {$userMessage = "The username or password is incorrect.<br/><br/>";
			} else {$userMessage = $userExists;}
		} elseif($userExists===FALSE) {require("../admin/secure.php");}
	}
	if(!isset($_POST['ownerAdd']) && !isset($_POST['userAdd'])) {unset($_SESSION['settings']);}
	if(isset($_POST['ownerAdd'])) {
		$oInsert->bindParam(':name',$_POST['name']);
		$oInsert->bindParam(':phone',$_POST['phone']);
		$oInsert->bindParam(':email',$_POST['email']);
		$oInsert->execute();
		$_SESSION['settings'] = 'owners';
	}
	if(isset($_POST['userAdd'])) {
		$_POST['isadmin']?:$_POST['isadmin']=0;
		$insertUsers->bindParam(':user',strtolower($_POST['user']));
		$insertUsers->bindParam(':pass',password_hash($_POST['user'], PASSWORD_DEFAULT));
		$insertUsers->bindParam(':fname',$_POST['fname']);
		$insertUsers->bindParam(':lname',$_POST['lname']);
		$insertUsers->bindParam(':isadmin',$_POST['isadmin'],PDO::PARAM_BOOL);
		$insertUsers->execute();
		$_SESSION['settings'] = 'users';
	}
	if(isset($_POST['resetUser'])) {
		$updateUsers->bindParam(':name',$_POST['user']);
		$updateUsers->bindParam(':pass',password_hash($_POST['user'], PASSWORD_DEFAULT));
		$updateUsers->execute();
		$_SESSION['settings'] = 'users';
	}
	if(isset($_POST['deleteUser'])) {
		$deleteUser->bindParam(':id',$_POST['deleteID']);
		$deleteUser->execute();
		$_SESSION['settings'] = 'users';
	}
	if(isset($_POST['deleteOwner'])) {
		$deleteOwner->bindParam(':id',$_POST['deleteID']);
		$deleteOwner->execute();
		$_SESSION['settings'] = 'owners';
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
	
				$file="<?php \n/*;\n[connection]\ndbname = \"".($_POST["dbname"]?:"")."\"\nhost = \"".($_POST["host"]?:"").
				"\"\nusername = \"".($_POST["username"]?:"")."\"\npassword = \"".($_POST["password"]?:"")."\"\nbranch = \"".
				($_POST["branch"]?:"")."\"\ncommit = \"".getBranchInfo(null,$_POST['branch'])['new']['commit']."\"\n*/\n?>";
				file_put_contents("config.ini.php", $file);
	
				if ($redirectURL) echo "<meta http-equiv=refresh content=\"0; URL=".$redirectURL."\">";
				$_SESSION['results'] = 'Application Updated Successfully!';
			} else {
				echo "Error Extracting Zip: Please <a href=\"".$repository."issues/new?title=Installation - Error Extracting\">click here</a> to submit a ticket.";
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

	//Pull branch info from GitHub
	function getBranchInfo($commit = null,$branch = null) {
		$json = getJSON("branches");
		foreach($json as $item) {$info['branches'][$item['name']]=$item['commit']['sha'];}
		if($commit) {
			$json = getJSON("commits/".$commit);
			$info['current']=array("commit"=>$json['sha'],"date"=>str_replace("Z","",str_replace("T"," ",$json['commit']['committer']['date'])),"notes"=>$json['commit']['message']);
		}
		if($branch) {
			$json=getJSON("branches/".$branch);
			$info['new']=array("name"=>$json['name'],"commit"=>$json['commit']['sha'],"date"=>str_replace("Z","",str_replace("T"," ",$json['commit']['commit']['committer']['date'])));
		}
		if (($commit) && ($branch) && ($info['current']['commit']!=$info['new']['commit'])) {
			$json = getJSON("compare/".$info['current']['commit']."...".$info['new']['commit']);
			if ($json['status']=="ahead" || $json['status']=="diverged") {$info['new']['aheadby']="<div class='red bold'>Update available. ".$json['ahead_by']." commit(s) behind.</div><br/>";}
		}
		return $info;
	}
	function getJSON($url) {
		$url = "https://api.github.com/repos/dynamiccookies/dadsgarage/".$url;
		return json_decode(file_get_contents($url, false, stream_context_create(array('http' => array('user_agent'=> $_SERVER['HTTP_USER_AGENT'])))),true);
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
		<div id="mainContainer" class="bgblue bord5 b-rad15 m-lrauto center m-top25">
			<div class="settings-header">Settings Page</div><br/>
			<button class="tablink width33" onclick="openTab('Database', this, 'left')"<?php echo (!$_SESSION['settings']?" id=\"defaultOpen\"":"");?>>Database</button>
			<button class="tablink width33" 
				<?php 
					if($dbExists){
						echo "onclick=\"openTab('Owners', this, 'middle')\"";
						echo ($_SESSION['settings']=='owners'?" id=\"defaultOpen\"":"");
					} else { echo "title=\"The Database information is required first.\" style=\"cursor:not-allowed;\"";}
				?> 
			>Owners</button>
			<button class="tablink width33"	
				<?php 
					if($dbExists){
						echo "onclick=\"openTab('Users', this, 'right')\"";
						echo ($_SESSION['settings']=='users'?" id=\"defaultOpen\"":"");
					} else { echo "title=\"The Database information is required first.\" style=\"cursor:not-allowed;\"";}
				?>
			>Users</button>
			<div id="Database" class="tabcontent">
				<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
					<table class="settings">
						<tr><td>Host Name:</td><td><input name="host" type="textbox"<?php echo $hostChk;?> value="<?php echo $ini["host"];?>"></td></tr>
						<tr><td nowrap>Database Name:</td><td><input name="dbname" type="textbox"<?php echo $dbChk;?> value="<?php echo $ini["dbname"];?>"></td></tr>
						<tr><td>Username:</td><td><input name="username" type="textbox"<?php echo $userChk;?> value="<?php echo $ini["username"];?>"></td></tr>
						<tr><td>Password:</td><td><input name="password" type="password"<?php echo $userChk;?> value="<?php echo $ini["password"];?>"></td></tr>
						<tr><td>Git Branch:</td><td style="text-align:left;">
							<select name="branch">
								<?php 
									foreach(getBranchInfo()['branches'] as $branch=>$value) {
										if(!$ini["branch"]) {$ini["branch"]="master";}
										echo "<option value='$branch'".($branch==$ini["branch"]?" selected":"").">$branch</option>";
									}
								?>
							</select>
						</td></tr>
					</table><br/>
					<?php 
						if (isset($_SESSION['run'])) {
							echo $_SESSION['results']."<br/><br/>";
							$_SESSION['run']+=1;
						}
						echo ($created_tables?($created_tables===true?
							"Tables created successfully.<br/>":"There was a problem creating the table(s).<br/>"):"");
						echo $userMessage;
						echo getBranchInfo($ini['commit'],$ini['branch'])['new']['aheadby']?:"";
						echo "<input type=\"Submit\" name=\"Save\" value=\"Save\">&nbsp;";
						echo "<input type=\"Submit\" name=\"Update\" value=\"Update Application\" title=\"Install updates from GitHub\">";
						if (dbExists) {echo $button?:"";}
					?>
				</form>
			</div>
			<div id="Owners" class="tabcontent">
				<?php $owners=$oRows;?>
				<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
					<table class="settings">
						<tr><td>Name:</td><td><input name="name" type="textbox" value=""></td></tr>
						<tr><td>Phone:</td><td><input name="phone" type="textbox" value=""></td></tr>
						<tr><td>Email:</td><td><input name="email" type="textbox" value=""></td></tr>
					</table><br/>
					<input type="Submit" name="ownerAdd" value="Add"><br/><br/>
				</form>
				<table id="owners">
					<tr><th>Name</th><th>Phone</th><th>Email</th><th>Delete</th></tr>
					<?php foreach($owners as $owner) {?>
							<tr>
								<td><?php echo $owner['name']?></td>
								<td><?php echo $owner['phone']?></td>
								<td><?php echo $owner['email']?></td>
								<td>
									<form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"])?>' method='post'>
										<input type='hidden' name='deleteID' value='<?php echo $owner['id']?>'>
										<input type='submit' name='deleteOwner' value='Delete'>
									</form>
								</td>
							</tr>
					<?php }?>
				</table>
			</div>
			<div id="Users" class="tabcontent">
				<?php if($dbExists){if(tableExists("users")){$selectAllUsers->execute();$users=$selectAllUsers->fetchAll(PDO::FETCH_ASSOC);}}?>
				<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
					<table class="settings">
						<tr><td>Username:</td><td><input name="user" type="textbox" value=""></td></tr>
						<tr><td nowrap>First Name:</td><td><input name="fname" type="textbox" value=""></td></tr>
						<tr><td>Last Name:</td><td><input name="lname" type="textbox" value=""></td></tr>
						<tr><td>Is Admin?</td><td style="text-align:left;"><input name="isadmin" type="checkbox" value="1"></td></tr>
						<tr><td colspan=2>*On add and reset, password is equal to the username</td></tr>
					</table><br/>
					<input type="Submit" name="userAdd" value="Add"><br/><br/>
				</form>
				<table id="users">
					<tr><th>Username</th><th>First Name</th><th>Last Name</th><th>Is Admin?</th><th>Password</th><th>Delete</th></tr>
					<?php foreach($users as $user) {?>
							<tr>
								<td><?php echo $user['username']?></td>
								<td><?php echo $user['fname']?></td>
								<td><?php echo $user['lname']?></td>
								<td><input type='checkbox' disabled<?php echo $user['isadmin']==1?" checked":""?>></td>
								<td>
									<form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"])?>' method='post'>
										<input type='hidden' name='user' value='<?php echo $user['username']?>'>
										<input type='submit' name='resetUser' value='Reset'>
									</form>
								</td>
								<td>
									<form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"])?>' method='post'>
										<input type='hidden' name='deleteID' value='<?php echo $user['id']?>'>
										<input type='submit' name='deleteUser' value='Delete'>
									</form>
								</td>
							</tr>
					<?php }?>
				</table>
			</div>
		</div>
	</div>
	<div class="commit"><?php echo $ini['commit'];?></div>
	<script src="admin.js"></script>
	<script>document.getElementById("defaultOpen").click();</script>
</body>
