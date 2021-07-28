<?php

	// Do not allow a direct connection to this file
	if (!isset($_SESSION['include'])) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	} else {unset($_SESSION['include']);}
?>
<a href='../'>Home</a>
<a href='../admin'>Admin</a>
<a href='../files/settings.php'>Settings</a><br/><br/>
<a href='../admin/secure.php?logout'>Logout</a>
