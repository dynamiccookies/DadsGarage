<a href="../">Home</a>
<a href="../admin">Admin</a>
<a href="../files/settings.php">Settings</a>
<a href="#">Customers</a><br/><br/>
<a href="<?php
	//Set path to 'admin'
	$admin=substr(getcwd(), strrpos(getcwd(), '/') + 1);
	if ($admin=="files") {$admin="../admin/";} 
	elseif ($admin=="admin") {$admin="";} 
	else {$admin="admin/";}
	echo $admin;
?>secure.php?logout=1">Logout</a>
