<?php
	$ini = parse_ini_file("config.ini.php");
	if(isset($_POST['Submit'])) {
		$str=file_get_contents("config.ini.php");
		$str=str_replace("dbname = ".$ini["dbname"],"dbname = ".$_POST["dbname"],$str);
		$str=str_replace("host = ".$ini["host"],"host = ".$_POST["host"],$str);
		$str=str_replace("username = ".$ini["username"],"username = ".$_POST["username"],$str);
		$str=str_replace("password = ".$ini["password"],"password = ".$_POST["password"],$str);
		$str=str_replace("branch = ".$ini["branch"],"branch = ".$_POST["branch"],$str);
		file_put_contents("config.ini.php", $str);
	}
?>
<div style="text-align:center;font-weight:bold;">Settings Page
<form action="" method="post">
<br/>Database Name: <input name="dbname" type="textbox" value="<?php echo ($_POST["dbname"]?:$ini["dbname"]);?>">
<br/>Host Name: <input name="host" type="textbox" value="<?php echo ($_POST["host"]?:$ini["host"]);?>">
<br/>Username: <input name="username" type="textbox" value="<?php echo ($_POST["username"]?:$ini["username"]);?>">
<br/>Password: <input name="password" type="textbox" value="<?php echo ($_POST["password"]?:$ini["password"]);?>">
<br/>Git Branch: <input name="branch" type="textbox" value="<?php echo ($_POST["branch"]?:$ini["branch"]);?>">
<br/><input type="Submit" name="Submit" value="Submit"></form>
</div>