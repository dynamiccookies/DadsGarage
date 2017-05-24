<?php
	if (!file_exists("config.ini.php")) {
		$str="<?php \n/*;\n[connection]\ndbname = \nhost = \nusername = \npassword = \nbranch = \n*/\n?>";
		file_put_contents("config.ini.php", $str);
	} 
	if(isset($_POST['Submit'])) {
		$str="<?php \n/*;\n[connection]\ndbname = ".$_POST["dbname"]."\nhost = ".$_POST["host"]."\nusername = ".$_POST["username"]."\npassword = ".$_POST["password"]."\nbranch = ".$_POST["branch"]."\n*/\n?>";
		file_put_contents("config.ini.php", $str);
	}
	$ini = parse_ini_file("config.ini.php");
	require_once("dbcheck.php");
?>
<head>
	<style>
		div:first-child {font-size:36px;font-weight:bold;}
		table {margin:auto;}
		td:first-child {text-align:right;font-weight:bold;}
		input[type=textbox] {width:400px;}
	</style>
</head>
<body>
	<div style="text-align:center;">Settings Page
	<form action="" method="post">
	<table>
		<tr>
			<td>Host Name:</td>
			<td><input name="host" type="textbox" value="<?php echo $ini["host"];?>"></td>
			<td><?php echo ($array["connTest"]?:"No Array");?></td>
		</tr>
		<tr>
			<td nowrap>Database Name:</td>
			<td><input name="dbname" type="textbox" value="<?php echo $ini["dbname"];?>"></td>
			<td><?php echo ($array["dbTest"]?:"No Array");?></td>
		</tr>
		<tr>
			<td>Username:</td>
			<td><input name="username" type="textbox" value="<?php echo $ini["username"];?>"></td>
			<td rowspan=2><?php echo ($array["credTest"]?:"No Array");?></td>
		</tr>
		<tr>
			<td>Password:</td>
			<td><input name="password" type="textbox" value="<?php echo $ini["password"];?>"></td>
		</tr>
		<tr>
			<td>Git Branch:</td>
			<td><input name="branch" type="textbox" value="<?php echo $ini["branch"];?>"></td>
			<td></td>
		</tr>
	</table>
	<br/><input type="Submit" name="Submit" value="Submit"></form>
	</div>
</body>
