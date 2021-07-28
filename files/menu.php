<?php

	echo "<a href='../'>Home</a>\n";
	echo "<a href='../admin'>Admin</a>\n";
	echo "<a href='../files/settings.php'>Settings</a>\n<br/><br/>\n";
	echo "<a href='../admin/secure.php?logout'>Logout</a>";
	// Do not allow a direct connection to this file
	if (!isset($_SESSION['include'])) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	} else {unset($_SESSION['include']);}
?>
