<?php
	require($_SERVER['DOCUMENT_ROOT']."/forsale/files/password.php");
	require($_SERVER['DOCUMENT_ROOT']."/forsale/files/include.php");
	if(isset($_GET['logout'])) {
		(isset($_GET['index'])?$index=$_GET['index']:$index='');
		echo "<meta http-equiv=refresh content=\"0; URL=http://".$_SERVER['SERVER_NAME']."/".$index."/".($_SESSION['isadmin']?"admin":"portal.php")."\">";
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
		<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
		<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
		<script src="http://code.jquery.com/jquery-latest.min.js"></script>
		<script language="JavaScript" type="text/javascript">
			function chgPass(show,hide){document.getElementById(show).className = "show";document.getElementById(hide).className = "hide";}
		</script>
		<style type="text/css">.hide {display:none;}</style>
	</head>
	<body class="bg">
		<style>input {border: 1px solid black;}</style>
		<div style="width:500px; margin-left:auto; margin-right:auto; text-align:center">
		  <form method="post">
			<h3>Please enter password to access this page</h3>
			<font color="red"><?php echo $error_msg; ?></font><br />
			Login:<br /><input type="input" name="access_login" /><br />
			Password:<br /><input type="password" name="access_password" /><br />
			<div id="show" class="hide">
				New Password:<br /><input type="password" name="new_password" /><br />
				<a onclick="chgPass('hide','show')" href="javascript:void(0);">Cancel</a>
			</div>
			<a onclick="chgPass('show','hide')" class="show" id="hide" href="javascript:void(0);">Change Password</a>
			<p></p><input type="submit" name="Submit" value="Submit" />
		</form>
		  <br />
		</div>
	</body>
	</html>
	<?php
	  // stop at this point
		die();
	}
}
if (isset($_POST['access_login'])) {
	$selectUsers->bindParam(':name',strtolower($_POST['access_login']));
	$selectUsers->execute();
	$account = $selectUsers->fetchAll(PDO::FETCH_ASSOC);

	if(!password_verify($_POST['access_password'],$account[0]['hash'])) {
		showLoginPasswordProtect("Incorrect password.");
	} else {
		if(isset($_POST['new_password']) && $_POST['new_password']<>'') {
			$updateUsers->bindParam(':pass',password_hash($_POST['new_password'], PASSWORD_DEFAULT));
			$updateUsers->bindParam(':name',strtolower($_POST['access_login']));
			$updateUsers->execute();
		}
		$_SESSION['buyerid']=$account[0]['id'];
		$_SESSION['isadmin']=$account[0]['isadmin'];
		$_SESSION['fname']=$account[0]['fname'];
		$_SESSION['lname']=$account[0]['lname'];
		$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
		$_SESSION['LoggedIn'] = true;
		unset($_POST['access_login']);
		unset($_POST['access_password']);
		unset($_POST['Submit']);
	}
} else {
    if ((isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) || !$_SESSION['LoggedIn']) {
		// last request was more than 30 minutes ago
		if($_SESSION) {session_unset(); session_destroy();}	// destroy session data in storage
		showLoginPasswordProtect("");
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