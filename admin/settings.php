<?php

	if (!isset($_SESSION)) {session_start();}

	$_SESSION['include'] = true;
	require_once '../includes/header.php';

	//Create/update config.ini.php on page load/save
	if (!file_exists('../includes/config.ini.php') || isset($_POST['Save'])) {

		unset($_SESSION['branches']);
		unset($_SESSION['compare']);

		if (isset($_POST['branch'])) {
			$branch = $_POST['branch'];
		} elseif (isset($_SESSION['branch'])) {
			$branch = $_SESSION['branch'];
		} else {
			$branch = '';
		}

		if (isset($_SESSION['inicommit'])) {
			$commit = $_SESSION['inicommit'];
		} else {
			$commit = '';
		}

		$ini = update_config($branch, $commit);
	} else {
		$ini = parse_ini_file('../includes/config.ini.php');
	}

	$user_message          = '';
	$_SESSION['debug']     = filter_var($ini['debug'], FILTER_VALIDATE_BOOLEAN);
	$_SESSION['inicommit'] = $ini['commit'];

	//Test validity of database, host, & credentials
	if (!empty($ini['host'])) {
    	$_SESSION['include'] = true;
		require_once '../includes/initialize-database.php';

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
	if (strpos($hostChk, 'pass') && strpos($dbChk, 'pass') && strpos($userChk, 'pass') && strpos($passChk, 'pass')) {
		$dbExists = true;

		if (!check_tables()) {
			if (isset($_POST['createTables'])) {
				$createdTables = createTables();
			} else {
				$button = " <input type='Submit' name='createTables' value='Create Table(s)'>";
				$dbChk  = str_replace('pass', 'warn', $dbChk);
				$dbChk  = str_replace('Database Connection Successful', 'One or more tables are missing from the database.', $dbChk);
			}
		}

		// Create/check default Admin user
		$adminExists = adminExists();
 		if ($adminExists === true) {
			$user_message = "The default username and password are 'admin'.<br/><a href='../admin'>Change the password</a>.<br/><br/>";
		} elseif ($adminExists === false) {
			$_SESSION['include'] = true;
			require_once '../admin/secure.php';
		} else {
			if (strpos($adminExists, 'Base table or view not found')) {
				$user_message = 'The Users table does not exist.<br/>Please click the Create Table(s) button to create it.<br/><br/>';
			} elseif (strpos($adminExists, "Access denied for user '" . $_POST['username'] . "'")) {
				$user_message = 'The username or password is incorrect.<br/><br/>';
			} else {$user_message = $adminExists;}
		}
	} else {$dbExists = false;}

	if ($dbExists) {
		if (check_tables('users')) {
			$_SESSION['include'] = true;
			require_once '../includes/include.php';
		}
	}
	if (!isset($_POST['ownerAdd']) && !isset($_POST['userAdd']) && !isset($_POST['Update'])) {unset($_SESSION['settings']);}
	if (isset($_POST['ownerAdd'])) {
		$oInsert->bindParam(':name',  $_POST['name']);
		$oInsert->bindParam(':phone', $_POST['phone']);
		$oInsert->bindParam(':email', $_POST['email']);
		$oInsert->execute();
		$_SESSION['settings'] = 'owners';
	}
	if (isset($_POST['userAdd'])) {
		if ($_POST['user']) {
			if (!$_POST['isadmin']) {$_POST['isadmin']=0;}
			$insertUsers->bindParam(':user',    strtolower($_POST['user']));
			$insertUsers->bindParam(':pass',    password_hash($_POST['user'], PASSWORD_DEFAULT));
			$insertUsers->bindParam(':fname',   $_POST['fname']);
			$insertUsers->bindParam(':lname',   $_POST['lname']);
			$insertUsers->bindParam(':isadmin', $_POST['isadmin'], PDO::PARAM_BOOL);
			$insertUsers->execute();
		}
		$_SESSION['settings'] = 'users';
	}
	if (isset($_POST['resetUser'])) {
		$updateUsers->bindParam(':name', $_POST['user']);
		$updateUsers->bindParam(':pass', password_hash($_POST['user'], PASSWORD_DEFAULT));
		$updateUsers->execute();
		$_SESSION['settings'] = 'users';
	}
	if (isset($_POST['deleteUser'])) {
		$deleteUser->bindParam(':id', $_POST['deleteID']);
		$deleteUser->execute();
		$_SESSION['settings'] = 'users';
	}
	if (isset($_POST['deleteOwner'])) {
		$deleteOwner->bindParam(':id', $_POST['deleteID']);
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
        	$redirectURL = '../admin/settings.php';

    		// Download repository files as 'install.zip' and store in '$file' variable
    		$file = file_put_contents(dirname(__DIR__) . '/install.zip', fopen($repository . 'archive/' . $repBranch . '.zip', 'r'), LOCK_EX);

    		// If '$file' variable does not contain data, present error message to screen and kill script
    		if ($file === false) die("Error Writing to File: Please <a href='" . $repository . "issues/new?title=Installation - Error Writing to File'>submit an issue</a>.");
    
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

				$ini = update_config($repBranch, $_SESSION['branches'][$repBranch]['sha']);

				unset($_SESSION['branches']);
				unset($_SESSION['compare']);

				$_SESSION['results'] = 'Application Updated Successfully!';

    			// If '$redirectURL' variable exists, redirect page to that URL
    			if ($redirectURL) echo "<meta http-equiv=refresh content='0; URL=" . $redirectURL . "'>";
    		} else {
    		    echo "Error Extracting Zip: Please <a href='" . $repository . "issues/new?title=Installation - Error Extracting'>submit an issue</a>.";
				$_SESSION['results'] = 'Something went wrong!';
    		}
		} catch (Exception $e) {$_SESSION['results'] = 'Something went wrong!<br/>'.$e;}
	}

	if (isset($_SESSION['results']) && !isset($_SESSION['run'])) {
		$_SESSION['run'] = 1;
	} elseif (isset($_SESSION['run']) && $_SESSION['run'] == 3) {
		unset($_SESSION['results']);
		unset($_SESSION['run']);
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

		if (isset($branch))              {}
		elseif (isset($ini['branch']))   {$branch   = $ini['branch'];}
		else                             {$branch   = '';}

		if (isset($commit))              {}
		elseif (isset($ini['commit']))   {$commit   = $ini['commit'];}
		else                             {$commit   = '';}

		file_put_contents('../includes/config.ini.php', 
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
?>
<body class='settings darkbg'>
	<div id='adminSidenav' class='adminsidenav'>
		<?php 
			$_SESSION['include'] = true;
			require_once '../includes/menu.php';
		?>
	</div>
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
					if(isset($dbChk) && strpos($dbChk, 'pass')){
						echo "onclick=\"openTab('General', this, 'left')\"";
						echo (!isset($_SESSION['settings']) ? " id='defaultOpen'" : '');
					} elseif(isset($dbChk) && strpos($dbChk, 'required')) { echo "title='The Database information is required first.' style='cursor:not-allowed;'";
					} else { echo "title='One or more tables are missing from the database.' style='cursor:not-allowed;'";}
				?> 
			><span class='alt'>G</span>eneral</button>
			<button class='tablink width25' value='Database' onclick="openTab('Database', this, 'middle')"	
				<?= ((isset($_SESSION['settings']) && $_SESSION['settings'] == 'database') || (!isset($dbChk) || !strpos($dbChk, 'pass')) ? " id='defaultOpen'" : '');?>
			><span class='alt'>D</span>atabase</button>
			<button class='tablink width25' value='Owners'
				<?php 
					if(isset($dbChk) && strpos($dbChk, 'pass')){
						echo "onclick=\"openTab('Owners', this, 'middle')\"";
						echo ((isset($_SESSION['settings']) && $_SESSION['settings'] == 'owners') ? " id='defaultOpen'" : '');
					} elseif(isset($dbChk) && strpos($dbChk, 'required')) { echo "title='The Database information is required first.' style='cursor:not-allowed;'";
					} else {echo "title='One or more tables are missing from the database.' style='cursor:not-allowed;'";}
				?> 
			><span class='alt'>O</span>wners</button>
			<button class='tablink width25'	value='Users'
				<?php 
					if(isset($dbChk) && strpos($dbChk, 'pass')){
						echo "onclick=\"openTab('Users', this, 'right')\"";
						echo ((isset($_SESSION['settings']) && $_SESSION['settings'] == 'users') ? " id='defaultOpen'" : '');
					} elseif(isset($dbChk) && strpos($dbChk, 'required')) { echo "title='The Database information is required first.' style='cursor:not-allowed;'";
					} else {echo "title='One or more tables are missing from the database.' style='cursor:not-allowed;'";}
				?>
			><span class='alt'>U</span>sers</button>
			<div id='General' class='tabcontent'>
				<form action='<?= htmlspecialchars($_SERVER['PHP_SELF']);?>' method='post'>
					<table class='settings borderupdown'>
						<tr><td>Bitly User Key:</td><td><input name='bitlyUser' type='textbox' value=''></td></tr>
						<tr><td nowrap>Bitly API Key:</td><td><input name='bitlyAPI' type='textbox' value=''></td></tr>
						<tr>
							<td>Git Branch:</td>
							<td style='text-align:left;'>
								<?php
                                    // If the GitHub API JSON query has not happened during this session or it needs to be updated
									if (!isset($_SESSION['branches'])) {
										$branches = getJSON('branches');

										if ($ini['debug']) error_log('GitHub Branches: ' . json_encode($branches));

										// If the GitHub API JSON query returned an error
										if (isset($branches['message'])) {
											if ($ini['debug']) error_log($branches['message']);

											if (strpos($branches['message'], 'API rate limit exceeded')) {
												$select_title = ' title="Please check back later for the complete list of branches."';
											} else {
												$select_title = '';
												$user_message = $branches['message'];
											}

											echo '<select name="branch"' . $select_title . ' disabled>';
											echo '<option value="' . $ini['branch'] . '" selected>' . $ini['branch'] . '</option>';
											echo '</select>';

										// Otherwise, if there's no error
										} else {
											$selected_branch        = $ini['branch'];
											$selected_branch_exists = false;
											$_SESSION['branches']   = array();

											foreach ($branches as $branch) {
												$branch_exists = false;
												if ($branch['name'] == $selected_branch) $branch_exists = $selected_branch_exists = true;
												$_SESSION['branches'][$branch['name']] = array(
													'selected' => $branch_exists,
													'sha'      => $branch['commit']['sha']
												);
											}

    										if ($ini['debug']) error_log('New Session Branches: ' . json_encode($_SESSION['branches']));

                                            if (!$selected_branch_exists) $selected_branch = 'master';

											echo '<select name="branch">';

											foreach ($_SESSION['branches'] as $branch => $value) {
												echo '<option value="' . $branch . '"' . ($value['selected'] ? ' selected' : '') . '>' . $branch . '</option>';
											}

											if (!$selected_branch_exists && !empty($ini['branch'])) {
												$_SESSION['compare'] = 'Your installed branch (' . $ini['branch'] . ') no longer exists.<br>Please select another branch and click save.';
											}
											echo '</select>';
										}
									} else {
										if ($ini['debug']) error_log('Stored Session Branches: ' . json_encode($_SESSION['branches']));

										echo '<select name="branch">';
										foreach ($_SESSION['branches'] as $branch => $value) {
											echo '<option value="' . $branch . '"' . ($value['selected'] ? ' selected' : '') . '>' . $branch . '</option>';
										}
										echo '</select>';
									}
								?>
							</td>
						</tr>
						<tr>
							<td>Debug Mode:</td>
							<td style='text-align:left;'>
                                <input type='hidden' name='debug' value='<?php 
									if ($_SESSION['debug'] === true) {echo '1';}
									elseif ($_SESSION['debug'] === false) {echo '0';}
							    ?>'>
								<input type='checkbox' onclick='this.previousSibling.value=1-this.previousSibling.value'
								    <?php 
								        if ($_SESSION['debug'] === true) {echo 'value=true checked';}
										else {echo 'value=false unchecked';}
							        ?>
								>
							</td>
						</tr>
					</table><br/>
					<?php 
						if (isset($_SESSION['run'])) {
							echo $_SESSION['results'] . '<br/><br/>';
							$_SESSION['run'] += 1;
						}
						
						echo (isset($createdTables) ? 
							($createdTables === true ? 
								'Tables created successfully.<br/>' : 'There was a problem creating the table(s).<br/>') : '');
						echo $user_message;

						if (!isset($_SESSION['compare']) && isset($_SESSION['branches'][$ini['branch']])) {
							$compare = getJSON('compare/' . $_SESSION['branches'][$ini['branch']]['sha'] . '...' . $ini['commit']);

							if ($ini['debug']) {
							    error_log('GitHub Commits: ' . $_SESSION['branches'][$ini['branch']]['sha'] . ' vs ' . $ini['commit']);
							    error_log('GitHub Compare: ' . json_encode($compare));
							}

							if (isset($compare['message'])) {
								if ($ini['debug']) error_log($compare['message']);
								if (!strpos($compare['message'], 'API rate limit exceeded')) $_SESSION['compare'] = $compare['message'];
							} else {
    							switch ($compare['status']) {
    							  case 'ahead':
    								$_SESSION['compare'] = 'BETA RELEASE. ' . $compare['ahead_by'] . ' commit(s) ahead.';
    								break;
    							  case 'behind':
    								$_SESSION['compare'] = 'Update available. ' . $compare['behind_by'] . ' commit(s) behind.';
    								break;
    							  case 'diverged':
    								$_SESSION['compare'] = 'Application out of sync. ' . $compare['ahead_by'] . ' commit(s) behind and ' . $compare['behind_by'] . ' commit(s) ahead.';
    								break;
    							  case 'identical':
    								// The application is up to date
    								$_SESSION['compare'] = '';
    								break;
    							  default:
    								$_SESSION['compare'] = 'How did you get here? ' . (!$ini['debug'] ? 'Enable Debug Mode and c' : 'C') . 'heck the logs.';
    							}
							}
						}

						if (empty($_SESSION['compare'])) {
							echo '';
						} else {
							echo "<div class='red bold bgyellow' style='width:505px;margin:auto;'>" . $_SESSION['compare'] . '</div><br/>';
							echo "<input type='Submit' name='Update' value='Update Application' title='Install updates from GitHub'>&nbsp;&nbsp;";
						}

						echo "<input type='Submit' name='Save' value='Save'>";
						
						if ($dbExists) {echo (isset($button) ? $button : '');}

					?>
				</form>
			</div>
			<div id='Database' class='tabcontent'>
				<form action='<?= htmlspecialchars($_SERVER['PHP_SELF']);?>' method='post'>
					<table class='settings borderupdown'>
						<tr><td>Host Address:</td><td><input name='host' type='textbox'<?= (isset($hostChk) ? $hostChk : '');?> value='<?= $ini['host'];?>'></td></tr>
						<tr><td nowrap>Database Name:</td><td><input name='dbname' type='textbox'<?= (isset($dbChk) ? $dbChk : '');?> value='<?= $ini['dbname'];?>'></td></tr>
						<tr><td>Username:</td><td><input name='username' type='textbox'<?= (isset($userChk) ? $userChk : '');?> value='<?= $ini['username'];?>' autocomplete='username'></td></tr>
						<tr><td>Password:</td><td><input name='password' type='password'<?= (isset($userChk) ? $userChk : '');?> value='<?= $ini['password'];?>' autocomplete='current-password'></td></tr>
					</table><br/>
					<?php 
						if (isset($_SESSION['run'])) {
							echo $_SESSION['results'] . '<br/><br/>';
							$_SESSION['run'] += 1;
						}
						echo (isset($created_tables) ? 
							($created_tables === true ?
								'Tables created successfully.<br/>' : 'There was a problem creating the table(s).<br/>') : '');
						echo $user_message;
						echo "<input type='Submit' name='Save' value='Save'>&nbsp;";
						if ($dbExists) {echo (isset($button) ? $button : '');}
					?>
				</form>
			</div>
			<div id='Owners' class='tabcontent'>
				<?php 
					if ($dbExists && check_tables('owners')) {
						$_SESSION['include'] = true;
						require_once '../includes/include.php';
						$oSelect->execute();
						$oRows  = $oSelect->fetchAll(PDO::FETCH_ASSOC);
						$owners = $oRows;
					}
				?>
				<form action='<?= htmlspecialchars($_SERVER['PHP_SELF']);?>' method='post'>
					<table class='settings'>
						<tr><td>Name:</td><td><input name='name' type='textbox' value=''></td></tr>
						<tr><td>Phone:</td><td><input name='phone' type='textbox' value=''></td></tr>
						<tr><td>Email:</td><td><input name='email' type='textbox' value=''></td></tr>
					</table>
					<input type='Submit' name='ownerAdd' value='Add'><br/>
				</form>
				<hr class='hrsettings'>
				<?php if (isset($owners) && (is_array($owners) || $owners instanceof Traversable)) {?>
					<table id='owners'>
						<tr><th>Name</th><th>Phone</th><th>Email</th><th>Delete</th></tr>
						<?php foreach ($owners as $owner) {?>
							<tr>
								<td><?= $owner['name']?></td>
								<td><?= $owner['phone']?></td>
								<td><?= $owner['email']?></td>
								<td>
									<form action='<?= htmlspecialchars($_SERVER['PHP_SELF'])?>' method='post'>
										<input type='hidden' name='deleteID' value='<?= $owner['id']?>'>
										<input type='submit' name='deleteOwner' value='Delete'>
									</form>
								</td>
							</tr>
						<?php }?>
					</table>
				<?php }?>
			</div>
			<div id='Users' class='tabcontent'>
 				<?php 
				if($dbExists){
					if(check_tables('users')){
						$_SESSION['include'] = true;
						require_once '../includes/include.php';
						$selectAllUsers->execute();
						$users = $selectAllUsers->fetchAll(PDO::FETCH_ASSOC);
					}
				}?>
				<form action='<?= htmlspecialchars($_SERVER['PHP_SELF']);?>' method='post'>
					<table class='settings'>
						<tr><td>Username:</td><td><input id='newUsername' name='user' type='textbox' value=''></td></tr>
						<tr><td nowrap>First Name:</td><td><input name='fname' type='textbox' value=''></td></tr>
						<tr><td>Last Name:</td><td><input name='lname' type='textbox' value=''></td></tr>
						<tr><td>Is Admin?</td><td style='text-align:left;'><input name='isadmin' type='checkbox' value='1'></td></tr>
						<tr><td colspan=2>*On add and reset, password is equal to the username</td></tr>
					</table>
					<input type='submit' name='userAdd' id='userAdd' value='Add'><br/>
				</form>
				<hr class='hrsettings'>
				<table id='users'>
					<tr><th>Username</th><th>First Name</th><th>Last Name</th><th>Is Admin?</th><th>Password</th><th>Delete</th></tr>
					<?php 
						if (isset($users)) {
							foreach($users as $user) {
					?>
							<tr>
								<td><?= $user['username']?></td>
								<td><?= $user['fname']?></td>
								<td><?= $user['lname']?></td>
								<td><input type='checkbox' disabled<?= ($user['isadmin'] == 1 ? ' checked' : '')?>></td>
								<td>
									<form action='<?= htmlspecialchars($_SERVER['PHP_SELF'])?>' method='post'>
										<input type='hidden' name='user' value='<?= $user['username']?>'>
										<input type='submit' name='resetUser' value='Reset'>
									</form>
								</td>
								<td>
									<form action='<?= htmlspecialchars($_SERVER['PHP_SELF'])?>' method='post'>
										<input type='hidden' name='deleteID' value='<?= $user['id']?>'>
										<input type='hidden' name='user' value='<?= $user['username']?>'>
										<input type='submit' name='deleteUser' value='Delete'>
									</form>
								</td>
							</tr>
					<?php }}?>
				</table>
				<?php if(isset($_POST['resetUser'])) {?>
					<br><span class='red bold'><?= $_POST['user'];?>'s password has been reset.</span>
				<?php } elseif(isset($_POST['deleteUser'])) {?>
					<br><span class='red bold'><?= $_POST['user'];?>'s account has been deleted.</span>
				<?php } elseif(isset($_POST['userAdd'])) {
							if($_POST['user']) {?>
								<br><span class='red bold'><?= strtolower($_POST['user']);?>'s account has been created successfully.</span>
							<?php } else {?>
								<br><span class='red bold'>Username must not be blank.</span>
				<?php 		}
						}?>
			</div>
		</div>
	</div>
	<div class='commit'><?= $ini['commit'];?></div>
	<script src='../scripts/admin.js'></script>
	<script>
		<?php
			if($dbExists && check_tables('owners')) {
				$selectAllUsernames = $db->prepare('SELECT username FROM users ORDER BY fname ASC');
				$selectAllUsernames->execute();
				$usernames = $selectAllUsernames->fetchAll(PDO::FETCH_ASSOC);
			}
		?>

		var usernames = <?= (isset($usernames) ? json_encode($usernames) : "''");?>;
		$(document).ready(function() {
			var txtUsername = $('#newUsername');
			document.getElementById('defaultOpen').click();
			txtUsername.keyup(function(){
				for (var i=0;usernames.length>i;i++) {
					if (txtUsername.val().toLowerCase() == usernames[i].username) {
						txtUsername.prop('title', 'Username exists').toggleClass('required', true);
						$('#userAdd').prop('disabled', true);
						break;
					} else {
						txtUsername.prop('title', '').toggleClass('required', false);
						$('#userAdd').prop('disabled', false);
					}
				}
			});			
			$(document).keydown(function(e) {
				if(e.altKey) {e.preventDefault();$('.alt').css('text-decoration', 'underline');}
				if(e.altKey && e.keyCode == 71) {openTab('General', $("button[value='General']").get(0), 'left');}
				else if(e.altKey && e.keyCode == 68) {
					e.preventDefault();openTab('Database', $("button[value='Database']").get(0), 'middle');}
				else if(e.altKey && e.keyCode == 79) {openTab('Owners', $("button[value='Owners']").get(0), 'middle');}
				else if(e.altKey && e.keyCode == 85) {openTab('Users', $("button[value='Users']").get(0), 'right');}
			});
			$(document).keyup(function(e) {$('.alt').css('text-decoration', 'none');});
		});
	</script>
</body>
