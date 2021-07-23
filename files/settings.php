<?php

	if(!isset($_SESSION)){session_start();}

	define('included', TRUE);

	require_once('header.php');

	//Create/update config.ini.php on page load/save
	if(!file_exists('config.ini.php') || isset($_POST['Save'])) {

		updateConfig(
			($_POST['branch'] ?: ($_SESSION['branch'] ?: '')), 
			($_SESSION['inicommit'] ?: '')
		);
	}

	//Read config.ini.php and set variables
	$ini                   = parse_ini_file('config.ini.php');
	$_SESSION['debug']     = filter_var($ini['debug'], FILTER_VALIDATE_BOOLEAN);
	$_SESSION['inicommit'] = $ini['commit'];
	$userMessage           = '';

	//Test validity of database, host, & credentials
	if (isset($ini['host'])) {
		require_once 'dbcheck.php';

		$hostChk = (isset($array['connTest']) && $array['connTest'] != 'Pass' ? $array['connTest'] : '');
		$dbChk = (!$ini['dbname']?'Required Field':($array['dbTest']?($array['dbTest']!='Pass'?$array['dbTest']:''):''));
		$dbChk = ($dbChk!=''?" class='required' title='".$dbChk."'":" class='pass' title='Database Connection Successful'");
		$userChk = (!$ini['username']?'Required Field':($array['credTest']?($array['credTest']!='Pass'?$array['credTest']:''):''));
		$userChk = ($userChk!=''?" class='required' title='".$userChk."'":" class='pass' title='Login Successful'");
		$passChk = (!$ini['password']?'Required Field':($array['credTest']?($array['credTest']!='Pass'?$array['credTest']:''):''));
		$passChk = ($passChk!=''?" class='required' title='".$passChk."'":" class='pass' title='Login Successful'");
	} else {$hostChk = 'Required Field';}
	$hostChk = ($hostChk!=''?" class='required' title='".$hostChk."'":" class='pass' title='Host Connection Successful'");

	// Check existence/create database tables
	if (strpos($hostChk,'pass') && strpos($dbChk,'pass') && strpos($userChk,'pass') && strpos($passChk,'pass')) {
		$dbExists = true;
		if (
			!tableExists('customers') ||
			!tableExists('expenses')  ||
			!tableExists('files')     ||
			!tableExists('owners')    ||
			!tableExists('photos')    ||
			!tableExists('users')	  ||
			!tableExists('vehicles')
		) {
			if ($_POST['createTables']){$createdTables = createTables();
			} else {
				$button = " <input type='Submit' name='createTables' value='Create Table(s)'>";
				$dbChk = str_replace('pass','warn',$dbChk);
				$dbChk = str_replace('Database Connection Successful','One or more tables are missing from the database.',$dbChk);
			}
		}

		// Create/check default Admin user
		$adminExists = adminExists();
 		if ($adminExists === TRUE) {
			$userMessage = "The default username and password are 'admin'.<br/><a href='../admin'>Change the password</a>.<br/><br/>";
		} elseif (!$adminExists === FALSE) {
			if (strpos($adminExists,"Base table or view not found") !== FALSE) {
				$userMessage = "The Users table does not exist.<br/>Please click the Create Table(s) button to create it.<br/><br/>";
			} elseif (strpos($adminExists,"Access denied for user '" . $_POST['username'] . "'")) {$userMessage = "The username or password is incorrect.<br/><br/>";
			} else {$userMessage = $adminExists;}
		} elseif($adminExists === FALSE) {require("../admin/secure.php");}
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
			ini_set('allow_url_fopen', 1);
        	$repository  = 'https://github.com/dynamiccookies/DadsGarage/';
        	$repBranch   = (isset($_POST['branch']) ? $_POST['branch'] : 'master');
        	$source      = 'DadsGarage-' . $repBranch;
        	$redirectURL = 'settings.php';

    		// Download repository files as 'install.zip' and store in '$file' variable
    		$file = file_put_contents(dirname(__DIR__) . '/install.zip', fopen($repository . 'archive/' . $repBranch . '.zip', 'r'), LOCK_EX);

    		// If '$file' variable does not contain data, present error message to screen and kill script
    		if ($file === false) die("Error Writing to File: Please <a href='" . $repository . "issues/new?title=Installation - Error Writing to File'>submit an issue</a>.");
    
    		$zip = new ZipArchive;

    		// Open zip file and store contents in '$res' variable
    		$res = $zip->open(dirname(__DIR__) . '/install.zip');
    		if ($res === true) {
    			for($i = 0; $i < $zip->numFiles; $i++) {
	    			$name = $zip->getNameIndex($i);
		    		if (strpos($name, "{$source}/") !== 0) continue;
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

				updateConfig($repBranch, getBranchInfo(null, $repBranch)['new']['commit']);

    			// If '$redirectURL' variable exists, redirect page to that URL
    			if ($redirectURL) echo "<meta http-equiv=refresh content='0; URL=" . $redirectURL . "'>";
				$_SESSION['results'] = 'Application Updated Successfully!';

    		} else {
    		    echo "Error Extracting Zip: Please <a href='" . $repository . "issues/new?title=Installation - Error Extracting'>submit an issue</a>.";
				$_SESSION['results'] = 'Something went wrong!';
    		}
		} catch (Exception $e) {$_SESSION['results'] = 'Something went wrong!<br/>'.$e;}
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

		if(file_exists('config.ini.php')) $ini = parse_ini_file('config.ini.php');


        if(isset($_POST['dbname']))      {$dbname   = $_POST['dbname'];}
        elseif(isset($_ini['dbname']))   {$dbname   = $ini['dbname'];}
        else                             {$dbname   = '';}

        if(isset($_POST['host']))        {$host     = $_POST['host'];}
        elseif(isset($_ini['host']))     {$host     = $ini['host'];}
        else                             {$host     = '';}

        if(isset($_POST['username']))    {$username = $_POST['username'];}
        elseif(isset($_ini['username'])) {$username = $ini['username'];}
        else                             {$username = '';}

        if(isset($_POST['password']))    {$password = $_POST['password'];}
        elseif(isset($_ini['password'])) {$password = $ini['password'];}
        else                             {$password = '';}

        if(isset($_POST['debug']))       {$debug    = $_POST['debug'];}
        elseif(isset($_ini['debug']))    {$debug    = $ini['debug'];}
        else                             {$debug    = 'false';}

        if(isset($branch))               {$branch = $branch;}
        elseif(isset($_ini['branch']))   {$branch = $ini['branch'];}
        else                             {$branch = '';}

        if(isset($commit))               {$commit = $commit;}
        elseif(isset($_ini['commit']))   {$commit = $ini['commit'];}
        else                             {$commit = '';}

		file_put_contents('config.ini.php', 
			"<?php \n/*;\n[connection]\n" .
				"dbname		= '" . $dbname   . "'\n" .
				"host 		= '" . $host     . "'\n" .
				"username 	= '" . $username . "'\n" .
				"password 	= '" . $password . "'\n" .
				"debug		= '" . $debug    . "'\n" .
				"branch		= '" . $branch   . "'\n" .
				"commit		= '" . $commit   . "'\n" .
				"bitlyuser	= '" . ''        . "'\n" .
				"bitlyAPI	= '" . ''        . "'\n" . 
			"*/\n?>");
	}
	
	//Iterate through retreived branch info - create/return multidimentional array
	function getBranchInfo($commit = null, $branch = null) {
		if (isset($_SESSION['json'])) {$json = $_SESSION['json'];}
		else {$json = getJSON('branches');}

		foreach ($json as $item) {$info['branches'][$item['name']] = $item['commit']['sha'];}

		if ($commit) {
			$json            = getJSON('commits/' . $commit);
			$info['current'] = array(
				'commit' => $json['sha'],
				'date'   => str_replace(array('T', 'Z'), '', $json['commit']['committer']['date']),
				'notes'  => $json['commit']['message']
			);
		}

		if ($branch) {
			$json        = getJSON('branches/' . $branch);
			$info['new'] = array(
				'name'   => $json['name'],
				'commit' => $json['commit']['sha'],
				'date'   => str_replace(array('T', 'Z'), '', $json['commit']['commit']['committer']['date'])
			);
		}

		if ($commit && $branch && $info['current']['commit'] != $info['new']['commit']) {
			$json = getJSON('compare/' . $info['current']['commit'] . '...' . $info['new']['commit']);
			if ($json['status'] == 'ahead' || $json['status'] == 'diverged') {
				$info['new']['aheadby'] = "<div class='red bold bgyellow'>Update available. " . $json['ahead_by'] . ' commit(s) behind.</div><br/>';
			}
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
						echo (!isset($_SESSION['settings'])?" id='defaultOpen'":'');
					} elseif(strpos($dbChk,'required')) { echo "title='The Database information is required first.' style='cursor:not-allowed;'";
					} else { echo "title='One or more tables are missing from the database.' style='cursor:not-allowed;'";}
				?> 
			><span class='alt'>G</span>eneral</button>
			<button class='tablink width25' value='Database' onclick="openTab('Database', this, 'middle')"	
				<?php echo ((isset($_SESSION['settings']) && $_SESSION['settings']=='database') || !strpos($dbChk,'pass') ? " id='defaultOpen'" : '');?>
			><span class='alt'>D</span>atabase</button>
			<button class='tablink width25' value='Owners'
				<?php 
					if(strpos($dbChk,'pass')){
						echo "onclick=\"openTab('Owners', this, 'middle')\"";
						echo ((isset($_SESSION['settings']) && $_SESSION['settings']=='owners') ? " id='defaultOpen'" : '');
					} elseif(strpos($dbChk,'required')) { echo "title='The Database information is required first.' style='cursor:not-allowed;'";
					} else { echo "title='One or more tables are missing from the database.' style='cursor:not-allowed;'";}
				?> 
			><span class='alt'>O</span>wners</button>
			<button class='tablink width25'	value='Users'
				<?php 
					if(strpos($dbChk,'pass')){
						echo "onclick=\"openTab('Users', this, 'right')\"";
						echo ((isset($_SESSION['settings']) && $_SESSION['settings']=='users') ? " id='defaultOpen'" : '');
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
						<tr>
							<td>Debug Mode:</td>
							<td style='text-align:left;'>
                                <input type="hidden" name="debug" value="<?php 
								        if($_SESSION['debug'] === true) {
								            echo '1';
								        } elseif($_SESSION['debug'] === false) {
								            echo '0';
								        }
							        ?>"
                                ><input type='checkbox' onclick="this.previousSibling.value=1-this.previousSibling.value"
								    <?php 
								        if($_SESSION['debug'] === true) {
								            echo 'value=true checked';
								        } elseif($_SESSION['debug'] === false) {
								            echo 'value=false unchecked';
								        } else {
								            echo "phpvalue=" . $ini['debug'];
								        }
							        ?>
						        >
							</td>
						</tr>
					</table><br/>
					<?php 
						if (isset($_SESSION['run'])) {
							echo $_SESSION['results'].'<br/><br/>';
							$_SESSION['run']+=1;
						}
						echo (isset($createdTables) ? ($createdTables === true ? 
							'Tables created successfully.<br/>' : 'There was a problem creating the table(s).<br/>') : '');
						echo $userMessage;
						$aheadBy = getBranchInfo($ini['commit'],$ini['branch']);
						echo (isset($aheadBy['new']['aheadby']) ? $aheadBy['new']['aheadby'] : '');
						echo "<input type='Submit' name='Save' value='Save'>&nbsp;";
						echo "<input type='Submit' name='Update' value='Update Application' title='Install updates from GitHub'>";
						if ($dbExists) {echo (isset($button) ? $button : '');}
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
						echo (isset($created_tables) ? ($created_tables === true ?
							'Tables created successfully.<br/>' : 'There was a problem creating the table(s).<br/>') : '');
						echo $userMessage;
						$aheadBy = getBranchInfo($ini['commit'],$ini['branch']);
						echo (isset($aheadBy['new']['aheadby']) ? $aheadBy['new']['aheadby'] : '');
						echo "<input type=\"Submit\" name=\"Save\" value=\"Save\">&nbsp;";
						echo "<input type=\"Submit\" name=\"Update\" value=\"Update Application\" title=\"Install updates from GitHub\">";
						if ($dbExists) {echo (isset($button) ? $button : '');}
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
				}
				$selectAllUsernames = $db->prepare("SELECT username FROM users ORDER BY fname ASC");
				$selectAllUsernames->execute();
				$usernames = $selectAllUsernames->fetchAll(PDO::FETCH_ASSOC);
			}
		?>

		var usernames = <?php echo (isset($usernames) ? json_encode($usernames) : "''");?>;
		$(document).ready(function() {
			var txtUsername = $('#newUsername');
			document.getElementById('defaultOpen').click();
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
