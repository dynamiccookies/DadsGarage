<?php 
$repository = 'https://github.com/dynamiccookies/DadsGarage/'; //URL to GitHub repository
$repBranch = $_POST['repo']?:"master";
$source = 'DadsGarage-'.$repBranch; //RepositoryName-Branch
$redirectURL = 'files/settings.php'; //Redirect URL - Leave blank for no redirect
//-------------------------------------------
if ($_POST['repo']) {
	$file = file_put_contents("install.zip", fopen($repository."archive/".$repBranch.".zip", 'r'), LOCK_EX);
	if($file === FALSE) die("Error Writing to File: Please <a href=\"".$repository."issues/new?title=Installation - Error Writing to File\">click here</a> to submit a ticket.");
	$zip = new ZipArchive;
	$res = $zip->open('install.zip');
	if ($res === TRUE) {
		for($i=0; $i<$zip->numFiles; $i++) {
			$name = $zip->getNameIndex($i);
			if (strpos($name, "{$source}/") !== 0) continue;
			$file = getcwd().'/'.substr($name, strlen($source)+1);
			if (substr($file,-1)!='/') {
				$dir = dirname($file);
				if (!is_dir($dir)) mkdir($dir, 0777, true);
				$fread = $zip->getStream($name);
				$fwrite = fopen($file, 'w');
				while ($data = fread($fread, 1024)) {fwrite($fwrite, $data);}
				fclose($fread);
				fclose($fwrite);
			}
		}
		$zip->close();
		unlink('install.zip');
		unlink('.gitignore');
		unlink(__FILE__);
		if ($redirectURL) echo "<meta http-equiv=refresh content=\"0; URL=".$redirectURL."\">";
	} else {echo "Error Extracting Zip: Please <a href=\"".$project."issues/new?title=Installation - Error Extracting\">click here</a> to submit a ticket.";}
}
?>
<div style="text-align:center;">
	<span style="font-weight:bold;font-size:24px;">Welcome to the Dad's Garage Management System Installer!</span><br/><br/>
	<span style="font-size:18px;">Make sure this file is saved to the root of your install directory.<br/><br/>
	If you'd like to install a non-default repository, enter it here:</span><br/>
	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
		<input name="repo" type=textbox style="text-align:center;" value="master"/><br/>
		<input type="Submit" name="Submit" value="Install">
	</form>
</div>
