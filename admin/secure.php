<?php

	if(!isset($_SESSION)){session_start();} 
	require '../files/password.php';
	require '../files/include.php';
	
	if(isset($_GET['logout'])) {
		echo "<meta http-equiv=refresh content=\"0; URL=" . (isset($_SESSION['isadmin']) ? "." : "..\\") . "\">";
		session_unset();     // unset $_SESSION variable for the run-time 
		session_destroy();   // destroy session data in storage
	}
	if(!function_exists('showLoginPasswordProtect')) {
		// show login form
		function showLoginPasswordProtect($error_msg) {
?>
			<html>
			<head>
				<title>Please enter password to access this page</title>
				<META HTTP-EQUIV='CACHE-CONTROL' CONTENT='NO-CACHE'>
				<META HTTP-EQUIV='PRAGMA' CONTENT='NO-CACHE'>
				<script src='http://code.jquery.com/jquery-latest.min.js'></script>
				<script src='https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js'></script>
				<script language='JavaScript' type='text/javascript'>
					$(document).ready(function() {
						$('#newpass,#confirmpass').keyup(function(){
							if($('#newpass').val() != $('#confirmpass').val()) {
								$('#submit').prop('disabled', true);
								$('#submit,#confirmpass').prop('title','Passwords do not match.');
								$('#confirmpass').toggleClass('required',true);
							} else {
								$('#submit').prop('disabled', false);
								$('#submit,#confirmpass').prop('title','');
								$('#confirmpass').toggleClass('required',false);
							}
						});
						var strength = {0:'Worst',1:'Bad',2:'Weak',3:'Good',4:'Strong'};
						var password = document.getElementById('newpass');
						var meter = document.getElementById('password-strength-meter');
						var text = document.getElementById('password-strength-text');

						password.addEventListener('input', function() {
						  var val = password.value;
						  var result = zxcvbn(val);

						  // Update the password strength meter
						  meter.value = result.score;

						  // Update the text indicator
						  if (val !== '') {
							text.innerHTML = strength[result.score]; 
						  } else {
							text.innerHTML = '';
						  }
						});
					});

					function chgPass(){
						$('.trigger').toggleClass('hidden');
						$('#newpass,#confirmpass,#password-strength-text').val('').text('');
						$('#password-strength-meter').val(0);
						$('#submit').prop('disabled', false);
						$('#submit,#confirmpass').prop('title','');
						$('#confirmpass').toggleClass('required',false)
					}
				</script>
				<style type='text/css'>
					.hidden {display:none;}
					.border {border:1px solid black;}
					.loginfields {
						margin:auto;
						border-bottom:1px solid black;
					}
					.loginfields td {padding-bottom:5px;}
					.loginfields tr td:first-child {text-align:right;}
					.required {
						box-shadow:0 0 5px #ff0000;
						border:2px solid #ff0000;
					}
					
					meter {
						/* Reset the default appearance */            
						margin: 0 auto 1em;
						width: 170px;
						height: .75em;
						
						/* Applicable only to Firefox */
						background: none;
						background-color: rgba(0,0,0,0.1);
					}

					meter::-webkit-meter-bar {
						background: none;
						background-color: rgba(0,0,0,0.1);
					}

					meter[value="0"]::-webkit-meter-optimum-value,
					meter[value="1"]::-webkit-meter-optimum-value { background: red; }
					meter[value="2"]::-webkit-meter-optimum-value { background: yellow; }
					meter[value="3"]::-webkit-meter-optimum-value { background: orange; }
					meter[value="4"]::-webkit-meter-optimum-value { background: green; }

					meter[value="1"]::-moz-meter-bar,
					meter[value="1"]::-moz-meter-bar { background: red; }
					meter[value="2"]::-moz-meter-bar { background: yellow; }
					meter[value="3"]::-moz-meter-bar { background: orange; }
					meter[value="4"]::-moz-meter-bar { background: green; }

					.feedback {
						color: #9ab;
						font-size: 90%;
						padding: 0 .25em;
						font-family: Courgette, cursive;
						margin-top: 1em;
					}

					meter::-webkit-meter-optimum-value {
						transition: width .4s ease-out;
					}
				</style>
			</head>
			<body class='darkbg'>
				<div id='mainContainer' class='bgblue bord5 p15 b-rad15 m-lrauto center m-top25' style='margin-top:auto!important;'>
					<form method='post'>
						<h3>Please enter your credentials to access the site</h3>
						<?php echo $error_msg?"<span class='red'>" . $error_msg . '</span><br />':''; ?>
						<table class='loginfields'>
							<tr>
								<td>Username:</td>
								<td><input class='border' type='input' name='access_login' autocomplete='username' autofocus /></td>
							</tr>
							<tr>
								<td><span class='trigger hidden'>Old </span>Password:</td>
								<td><input class='border' type='password' name='access_password' autocomplete='current-password' /></td>
							</tr>
							<tr class='trigger hidden'>
								<td>New Password:</td>
								<td><input id='newpass' class='border' type='password' name='new_password' autocomplete="new-password" /></td>
							</tr>
							<tr class='trigger hidden'>
								<td>Confirm Password:</td>
								<td><input id='confirmpass' class='border' type='password' autocomplete="new-password" /></td>
							</tr>
						</table><br>
						<div class='trigger hidden'>
							Password Strength: <span id="password-strength-text"></span><br>
							<meter max="4" id="password-strength-meter"></meter><br>
							<a onclick="chgPass()" href='javascript:void(0);'>Cancel</a>
						</div>
						<a onclick="chgPass()" href='javascript:void(0);' class='trigger'>Change Password</a>
						<p></p><input id='submit' type='submit' name='Submit' value='Submit' />
					</form>
					<br />
				</div>
			</body>
			</html>
<?php
			die();	//stop at this point
		}
	}
	if (isset($_POST['access_login'])) {
		$lowercase = strtolower($_POST['access_login']);
		$selectUsers->bindParam(':name',$lowercase);
		$selectUsers->execute();
		$account = $selectUsers->fetchAll(PDO::FETCH_ASSOC);
		$account = $account[0];

		if (!password_verify($_POST['access_password'],$account['hash'])) {
			showLoginPasswordProtect('Incorrect username and/or password.');
		} elseif (
			password_verify($_POST['access_password'],$account['hash']) && 
			$_POST['access_login'] == $_POST['access_password'] &&
			(!isset($_POST['new_password']) || $_POST['new_password'] == '')
		) {
			showLoginPasswordProtect('Username and password cannot be identical.<br>Please change your password.');
		} else {
			if (isset($_POST['new_password']) && $_POST['new_password']<>'') {
				$updateUsers->bindParam(':pass',password_hash($_POST['new_password'], PASSWORD_DEFAULT));
				$updateUsers->bindParam(':name',strtolower($_POST['access_login']));
				$updateUsers->execute();
			}
			$_SESSION['userid']        = $account['id'];
			$_SESSION['isadmin']       = $account['isadmin'];
			$_SESSION['fname']         = $account['fname'];
			$_SESSION['lname']         = $account['lname'];
			$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
			$_SESSION['LoggedIn']      = true;

			unset($_POST['access_login']);
			unset($_POST['access_password']);
			unset($_POST['Submit']);
		}
	} else {
		if ((isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) || !isset($_SESSION['LoggedIn'])) {
			// last request was more than 30 minutes ago
			if($_SESSION) {session_unset(); session_destroy();}	// destroy session data in storage
			showLoginPasswordProtect('');
		}
		if (!isset($_SESSION['CREATED'])) {
			$_SESSION['CREATED'] = time();
		} else if (time() - $_SESSION['CREATED'] > 1800) {
			// session started more than 30 minutes ago
			session_regenerate_id(true);    // change session ID for the current session and invalidate old session ID
			$_SESSION['CREATED'] = time();  // update creation time
		}//http://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes
	}
?>
