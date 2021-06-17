<?php
	session_start();
	define('included', TRUE);
	require_once('header.php');
	ini_set('allow_url_fopen', 1);
	$userMessage = '';
	$debug       = TRUE;

	//Create/update config.ini.php on page load/save
	if (!file_exists('config.ini.php') || isset($_POST['Save'])) {
		updateConfig(($_POST['branch']?:($_SESSION['branch']?:'')),($_SESSION['inicommit']?:''));
	}

	//Read config.ini.php
	$ini                   = parse_ini_file('config.ini.php');
	$_SESSION['inicommit'] = $ini['commit'];

	//Test validity of database, host, & credentials
	if ($ini['host']) {
		require_once('dbcheck.php');
		$hostChk = ($array['connTest'] ? ($array['connTest']!='Pass' ? $array['connTest'] : ''):'');
		$dbChk = (!$ini['dbname']?'Required Field':($array['dbTest']?($array['dbTest']!='Pass'?$array['dbTest']:''):''));
		$dbChk = ($dbChk!=''?" class='required' title='".$dbChk."'":" class='pass' title='Database Connection Successful'");
		$userChk = (!$ini['username']?'Required Field':($array['credTest']?($array['credTest']!='Pass'?$array['credTest']:''):''));
		$userChk = ($userChk!=''?" class='required' title='".$userChk."'":" class='pass' title='Login Successful'");
		$passChk = (!$ini['password']?'Required Field':($array['credTest']?($array['credTest']!='Pass'?$array['credTest']:''):''));
		$passChk = ($passChk!=''?" class='required' title='".$passChk."'":" class='pass' title='Login Successful'");
	} else {$hostChk = 'Required Field';}
	$hostChk = ($hostChk!=''?" class='required' title='".$hostChk."'":" class='pass' title='Host Connection Successful'");

	//Check existence/create database tables
	if (strpos($hostChk,'pass') && strpos($dbChk,'pass') && strpos($userChk,'pass') && strpos($passChk,'pass')) {
		$dbExists = TRUE;
		if (!tableExists('customers')	|| !tableExists('expenses') || !tableExists('files') || !tableExists('owners') || 
			!tableExists('photos')		|| !tableExists('users')	|| !tableExists('vehicles')) {
			if ($_POST['createTables']){$created_tables = create_tables();
			} else {
				$button = " <input type='Submit' name='createTables' value='Create Table(s)'>";
				$dbChk = str_replace('pass','warn',$dbChk);
				$dbChk = str_replace('Database Connection Successful','One or more tables are missing from the database.',$dbChk);
			}
		}

		//Check existence/create default Admin user
		$userExists = usersExist();
 		if ($userExists===TRUE) {
			$userMessage = "The default username and password are 'admin'.<br/><a href='../admin'>Click here to change the password.</a><br/><br/>";
		} elseif (!$userExists===FALSE) {
			if (strpos($userExists,"Base table or view not found")!==FALSE) {
				$userMessage = "The Users table does not exist.<br/>Please click the Create Table(s) button to create it.<br/><br/>";
			} elseif (strpos($userExists,"Access denied for user '".$_POST['username']."'")) {$userMessage = "The username or password is incorrect.<br/><br/>";
			} else {$userMessage = $userExists;}
		} elseif($userExists===FALSE) {require("../admin/secure.php");}
	} else {$dbExists = FALSE;}

	if($dbExists) {if(tableExists("users")){require_once("include.php");}}
	if(!isset($_POST['ownerAdd']) && !isset($_POST['userAdd']) && !isset($_POST['Update'])) {unset($_SESSION['settings']);}
	if(isset($_POST['ownerAdd'])) {
		$oInsert->bindParam(':name',$_POST['name']);
		$oInsert->bindParam(':phone',$_POST['phone']);
		$oInsert->bindParam(':email',$_POST['email']);
		$oInsert->execute();
		$_SESSION['settings'] = 'owners';
	}
	if(isset($_POST['userAdd'])) {
		if($_POST['user']) {
			if(!$_POST['isadmin']) {$_POST['isadmin']=0;}
			$insertUsers->bindParam(':user',strtolower($_POST['user']));
			$insertUsers->bindParam(':pass',password_hash($_POST['user'], PASSWORD_DEFAULT));
			$insertUsers->bindParam(':fname',$_POST['fname']);
			$insertUsers->bindParam(':lname',$_POST['lname']);
			$insertUsers->bindParam(':isadmin',$_POST['isadmin'],PDO::PARAM_BOOL);
			$insertUsers->execute();
		}
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
			$repBranch = $_POST['branch']?:'master';
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
				unlink(dirname(__DIR__) . '/install.zip');
				unlink(dirname(__DIR__) . 'README.md');
				unlink(dirname(__DIR__) . '/.gitignore');
	
				updateConfig($repBranch,getBranchInfo(null,$repBranch)['new']['commit']);
				
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

	//(Re)Create config.ini.php file
	function updateConfig($branch = null, $commit = null) {
		require('password.php');
		file_put_contents('config.ini.php', 
			"<?php \n/*;\n[connection]\n".
				"dbname		= '" . ($_POST['dbname']   ?: '') . "'\n" .
				"host 		= '" . ($_POST['host']     ?: '') . "'\n" .
				"username 	= '" . ($_POST['username'] ?: '') . "'\n" .
				"password 	= '" . ($_POST['password'] ?: '') . "'\n" .
				"branch		= '" . $branch . "'\n" .
				"commit		= '" . $commit . "'\n" .
				"bitlyuser	= '" . '' . "'\n" .
				"bitlyAPI	= '" . '' . "'\n" . 
			"*/\n?>");
	}
	
	//Iterate through retreived branch info - create/return multidimentional array
	function getBranchInfo($commit = null, $branch = null) {
		if (!$_SESSION['json']) {$_SESSION['json'] = getJSON('branches');}
		$json = $_SESSION['json'];

		echo "<script>console.log('Commit: ".$commit." - Branch: ".$branch."');var json = ".json_encode($json).";console.log(json);</script>";

		foreach($json as $item) {$info['branches'][$item['name']]=$item['commit']['sha'];}
		if($commit) {
			$json = getJSON('commits/'.$commit);
			$info['current']=array('commit'=>$json['sha'],'date'=>str_replace('Z','',str_replace('T',' ',$json['commit']['committer']['date'])),'notes'=>$json['commit']['message']);
		}
		if($branch) {
			$json=getJSON('branches/'.$branch);
			$info['new']=array('name'=>$json['name'],'commit'=>$json['commit']['sha'],'date'=>str_replace('Z','',str_replace('T',' ',$json['commit']['commit']['committer']['date'])));
		}
		if (($commit) && ($branch) && ($info['current']['commit']!=$info['new']['commit'])) {
			$json = getJSON('compare/'.$info['current']['commit'].'...'.$info['new']['commit']);
			if ($json['status']=='ahead' || $json['status']=='diverged') {$info['new']['aheadby']="<div class='red bold'>Update available. ".$json['ahead_by'].' commit(s) behind.</div><br/>';}
		}
		return $info;
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
?>
<body class='settings darkbg'>
	<div id='adminSidenav' class='adminsidenav'><?php require_once('menu.php');?></div>
	<div id='adminMain'>
		<div class='adminContainer' onclick='myFunction(this)'>
		  <div class='bar1'></div>
		  <div class='bar2'></div>
		  <div class='bar3'></div>
		</div>
		<div id='mainContainer' class='bgblue bord5 b-rad15 m-lrauto center m-top25'>
			<div class='settings-header'>Settings</div><br/>
			<button class='tablink width25' value='General'
				<?php 
					if(strpos($dbChk,'pass')){
						echo "onclick=\"openTab('General', this, 'left')\"";
						echo (!$_SESSION['settings']?" id='defaultOpen'":'');
					} elseif(strpos($dbChk,'required')) { echo "title='The Database information is required first.' style='cursor:not-allowed;'";
					} else { echo "title='One or more tables are missing from the database.' style='cursor:not-allowed;'";}
				?> 
			><span class='alt'>G</span>eneral</button>
			<button class='tablink width25' value='Database' onclick="openTab('Database', this, 'middle')"	
				<?php echo ($_SESSION['settings']=='database'||!strpos($dbChk,'pass')?" id='defaultOpen'":'');?>
			><span class='alt'>D</span>atabase</button>
			<button class='tablink width25' value='Owners'
				<?php 
					if(strpos($dbChk,'pass')){
						echo "onclick=\"openTab('Owners', this, 'middle')\"";
						echo ($_SESSION['settings']=='owners'?" id='defaultOpen'":'');
					} elseif(strpos($dbChk,'required')) { echo "title='The Database information is required first.' style='cursor:not-allowed;'";
					} else { echo "title='One or more tables are missing from the database.' style='cursor:not-allowed;'";}
				?> 
			><span class='alt'>O</span>wners</button>
			<button class='tablink width25'	value='Users'
				<?php 
					if(strpos($dbChk,'pass')){
						echo "onclick=\"openTab('Users', this, 'right')\"";
						echo ($_SESSION['settings']=='users'?" id='defaultOpen'":'');
					} elseif(strpos($dbChk,'required')) { echo "title='The Database information is required first.' style='cursor:not-allowed;'";
					} else { echo "title='One or more tables are missing from the database.' style='cursor:not-allowed;'";}
				?>
			><span class='alt'>U</span>sers</button>
			<div id='General' class='tabcontent'>
				<form action='<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>' method='post'>
					<table class='settings borderupdown'>
						<tr><td>Bitly User Key:</td><td><input name='bitlyUser' type='textbox' value=''></td></tr>
						<tr><td nowrap>Bitly API Key:</td><td><input name='bitlyAPI' type='textbox' value=''></td></tr>
						<tr><td>Git Branch:</td><td style='text-align:left;'>
							<select name='branch'>
								<?php 
									foreach(getBranchInfo()['branches'] as $branch=>$value) {
										if(!$ini['branch']) {$ini['branch']='master';}
										echo "<option value='$branch'".($branch==$ini['branch']?' selected':'').">$branch</option>";
									}
								?>
							</select>
						</td></tr>
					</table><br/>
					<?php 
						if (isset($_SESSION['run'])) {
							echo $_SESSION['results'].'<br/><br/>';
							$_SESSION['run']+=1;
						}
						echo ($created_tables?($created_tables===true?
							'Tables created successfully.<br/>':'There was a problem creating the table(s).<br/>'):'');
						echo $userMessage;
						echo getBranchInfo($ini['commit'],$ini['branch'])['new']['aheadby']?:'';
						echo "<input type='Submit' name='Save' value='Save'>&nbsp;";
						echo "<input type='Submit' name='Update' value='Update Application' title='Install updates from GitHub'>";
						if ($dbExists) {echo $button?:'';}
					?>
				</form>
			</div>
			<div id="Database" class="tabcontent">
				<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
					<table class='settings borderupdown'>
						<tr><td>Host Address:</td><td><input name="host" type="textbox"<?php echo $hostChk;?> value="<?php echo $ini["host"];?>"></td></tr>
						<tr><td nowrap>Database Name:</td><td><input name="dbname" type="textbox"<?php echo $dbChk;?> value="<?php echo $ini["dbname"];?>"></td></tr>
						<tr><td>Username:</td><td><input name="username" type="textbox"<?php echo $userChk;?> value="<?php echo $ini["username"];?>" autocomplete='username'></td></tr>
						<tr><td>Password:</td><td><input name="password" type="password"<?php echo $userChk;?> value="<?php echo $ini["password"];?>" autocomplete='current-password'></td></tr>
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
						if ($dbExists) {echo $button?:"";}
					?>
				</form>
			</div>
			<div id="Owners" class="tabcontent">
				<?php 
					if($dbExists) {
						if(tableExists('owners')){
							require_once('include.php');
							$oSelect->execute();
							$oRows = $oSelect->fetchAll(PDO::FETCH_ASSOC);
							$owners=$oRows;
						}
					}
				?>
				<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
					<table class="settings">
						<tr><td>Name:</td><td><input name="name" type="textbox" value=""></td></tr>
						<tr><td>Phone:</td><td><input name="phone" type="textbox" value=""></td></tr>
						<tr><td>Email:</td><td><input name="email" type="textbox" value=""></td></tr>
					</table>
					<input type="Submit" name="ownerAdd" value="Add"><br/>
				</form>
				<hr class='hrsettings'>
				<?php if (is_array($owners) || $owners instanceof Traversable) {?>
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
				<?php }?>
			</div>
			<div id="Users" class="tabcontent">
 				<?php 
				if($dbExists){
					if(tableExists("users")){
						require_once("include.php");
						$selectAllUsers->execute();
						$users=$selectAllUsers->fetchAll(PDO::FETCH_ASSOC);
					}
				}?>
				<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
					<table class="settings">
						<tr><td>Username:</td><td><input id='newUsername' name="user" type="textbox" value=""></td></tr>
						<tr><td nowrap>First Name:</td><td><input name="fname" type="textbox" value=""></td></tr>
						<tr><td>Last Name:</td><td><input name="lname" type="textbox" value=""></td></tr>
						<tr><td>Is Admin?</td><td style="text-align:left;"><input name="isadmin" type="checkbox" value="1"></td></tr>
						<tr><td colspan=2>*On add and reset, password is equal to the username</td></tr>
					</table>
					<input type="submit" name="userAdd" id='userAdd' value="Add"><br/>
				</form>
				<hr class='hrsettings'>
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
										<input type='hidden' name='user' value='<?php echo $user['username']?>'>
										<input type='submit' name='deleteUser' value='Delete'>
									</form>
								</td>
							</tr>
					<?php }?>
				</table>
				<?php if(isset($_POST['resetUser'])) {?>
					<br><span class='red bold'><?php echo $_POST['user'];?>'s password has been reset.</span>
				<?php } elseif(isset($_POST['deleteUser'])) {?>
					<br><span class='red bold'><?php echo $_POST['user'];?>'s account has been deleted.</span>
				<?php } elseif(isset($_POST['userAdd'])) {
							if($_POST['user']) {?>
								<br><span class='red bold'><?php echo strtolower($_POST['user']);?>'s account has been created successfully.</span>
							<?php } else {?>
								<br><span class='red bold'>Username must not be blank.</span>
				<?php 		}
						}?>
			</div>
		</div>
	</div>
	<div class="commit"><?php echo $ini['commit'];?></div>
	<script src="admin.js"></script>
	<script>
		<?php
			if($dbExists) {
				if(tableExists('owners')) {
					$selectAllUsernames = $db->prepare("SELECT username FROM users ORDER BY fname ASC");
					$selectAllUsernames->execute();
					$usernames = $selectAllUsernames->fetchAll(PDO::FETCH_ASSOC);
				}
			}
		?>
		var usernames = <?php echo json_encode($usernames);?>;
		document.getElementById('defaultOpen').click();
		$(document).ready(function() {
			var txtUsername = $('#newUsername');
			txtUsername.keyup(function(){
				for (var i=0;usernames.length>i;i++) {
					if (txtUsername.val().toLowerCase() == usernames[i].username) {
						txtUsername.prop('title','Username exists').toggleClass('required',true);
						$('#userAdd').prop('disabled', true);
						break;
					} else {
						txtUsername.prop('title','').toggleClass('required',false);
						$('#userAdd').prop('disabled', false);
					}
				}
			});			
			$(document).keydown(function(e) {
				if(e.altKey) {e.preventDefault();$('.alt').css('text-decoration','underline');}
				if(e.altKey && e.keyCode == 71) {openTab('General', $("button[value='General']").get(0), 'left');}
				else if(e.altKey && e.keyCode == 68) {
					e.preventDefault();openTab('Database', $("button[value='Database']").get(0), 'middle');}
				else if(e.altKey && e.keyCode == 79) {openTab('Owners', $("button[value='Owners']").get(0), 'middle');}
				else if(e.altKey && e.keyCode == 85) {openTab('Users', $("button[value='Users']").get(0), 'right');}
			});
			$(document).keyup(function(e) {$('.alt').css('text-decoration','none');});
		});
	</script>
</body>
