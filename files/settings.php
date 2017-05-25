<?php
	if (!file_exists("config.ini.php") || isset($_POST['Submit'])) {
		$file="<?php \n/*;\n[connection]\ndbname = ".($_POST["dbname"]?:"")."\nhost = ".($_POST["host"]?:"").
		"\nusername = ".($_POST["username"]?:"")."\npassword = ".($_POST["password"]?:"")."\nbranch = ".($_POST["branch"]?:"")."\n*/\n?>";
		file_put_contents("config.ini.php", $file);
	}
	$ini = parse_ini_file("config.ini.php");
	if ($ini["host"] && $ini["dbname"] && $ini["username"] && $ini["password"]) {require_once("dbcheck.php");}
	$hostChk = (!$ini["host"]?"Required Field":($array["connTest"]?($array["connTest"]!="Pass"?$array["connTest"]:""):""));
	$hostChk = ($hostChk!=""?" class=\"required\" title=\"".$hostChk."\"":" class=\"pass\" title=\"Host Connection Successful\"");
	$dbChk = (!$ini["dbname"]?"Required Field":($array["dbTest"]?($array["dbTest"]!="Pass"?$array["dbTest"]:""):""));
	$dbChk = ($dbChk!=""?" class=\"required\" title=\"".$dbChk."\"":" class=\"pass\" title=\"Database Connection Successful\"");
	$userChk = (!$ini["username"]?"Required Field":($array["credTest"]?($array["credTest"]!="Pass"?$array["credTest"]:""):""));
	$userChk = ($userChk!=""?" class=\"required\" title=\"".$userChk."\"":" class=\"pass\" title=\"Login Successful\"");
	$passChk = (!$ini["password"]?"Required Field":($array["credTest"]?($array["credTest"]!="Pass"?$array["credTest"]:""):""));
	$passChk = ($passChk!=""?" class=\"required\" title=\"".$passChk."\"":" class=\"pass\" title=\"Login Successful\"");
?>
<head>
	<style>
		body {text-align:center;}
		div {font-size:36px;font-weight:bold;}
		table {margin:auto;}
		td:first-child {text-align:right;font-weight:bold;}
		input[type=textbox], input[type=password] {width:350px;border-radius:4px;}
		.required {box-shadow:0 0 5px #ff0000;border:2px solid #ff0000;}
		.pass {box-shadow:0 0 5px #00c600;border:2px solid #00c600;}
	</style>
</head>
<body>
	<div>Settings Page</div>
	<form action="" method="post">
		<table>
			<tr><td>Host Name:</td><td><input name="host" type="textbox"<?php echo $hostChk;?> value="<?php echo $ini["host"];?>"></td></tr>
			<tr><td nowrap>Database Name:</td><td><input name="dbname" type="textbox"<?php echo $dbChk;?> value="<?php echo $ini["dbname"];?>"></td></tr>
			<tr><td>Username:</td><td><input name="username" type="textbox"<?php echo $userChk;?>" value="<?php echo $ini["username"];?>"></td></tr>
			<tr><td>Password:</td><td><input name="password" type="password"<?php echo $userChk;?>" value="<?php echo $ini["password"];?>"></td></tr>
			<tr><td>Git Branch:</td><td><input name="branch" type="textbox" value="<?php echo $ini["branch"];?>"></td></tr>
		</table>
		<br/><input type="Submit" name="Submit" value="Submit">
	</form>
</body>
