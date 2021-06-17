<?php 
	session_start();

	// GitHub repo (username/repository)
	$repo           = 'dynamiccookies/DadsGarage';

	// Default branch to install if none selected - Set to 'master' if left blank
	$defaultBranch  = '';

	// Name of your application - Used in installer title
	$title          = "Dad's Garage Management System";

	// URL to open after install is completed
	$redirectURL    = 'files/settings.php';


	//-------------DO NOT EDIT BELOW------------------------------
	ini_set('allow_url_fopen', 1);
	$repository = 'https://github.com/' . $repo . '/';
	$repBranch  = $_POST['branches'] ?: ($defaultBranch ?: 'master');
	$source     = substr($repo, strpos($repo, '/') + 1) . '-' . $repBranch;

	// Pull list of branches from GitHub for install
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . $repo . '/branches'); 
	curl_setopt($ch, CURLOPT_USERAGENT,substr($repo, strpos($repo, '/') + 1));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$branches = json_decode(curl_exec($ch),true);
	curl_close($ch);

	// Run install if button clicked
	if ($_POST['branches']) {
		
		// Store selected branch into session variable for use later
		$_SESSION['branch'] = $repBranch;
		
		// Store selected branch's commit SHA into session variable for use later
		foreach ($branches as $branch) {if($branch['name']==$repBranch) {$_SESSION['inicommit']=$branch['commit']['sha'];}}

		// Download repository files as 'install.zip' and store in '$file' variable
		$file = file_put_contents('install.zip', fopen($repository . 'archive/' . $repBranch . '.zip', 'r'), LOCK_EX);

		// If '$file' variable does not contain data, present error message to screen and kill script
		if($file === FALSE) die("Error Writing to File: Please <a href='" . $repository . "issues/new?title=Installation - Error Writing to File'>submit an issue</a>.");

		// Create '$zip' variable as ZipArchive object
		$zip = new ZipArchive;

		// Open zip file and store contents in '$res' variable
		$res = $zip->open('install.zip');
		if ($res === TRUE) {
			for($i=0; $i<$zip->numFiles; $i++) {
				$name = $zip->getNameIndex($i);
				if (strpos($name, "{$source}/") !== 0) continue;
				$file = getcwd() . '/' . substr($name, strlen($source)+1);
				if (substr($file,-1) != '/') {
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
			
			// Delete the following files - '__FILE__' means current file (install.php)
			unlink('install.zip');
			unlink('README.md');
			unlink('.gitignore');
			unlink(__FILE__);

			// If '$redirectURL' variable exists, redirect page to that URL
			if ($redirectURL) echo "<meta http-equiv=refresh content='0; URL=" . $redirectURL . "'>";
		} else {echo "Error Extracting Zip: Please <a href='" . $repository . "issues/new?title=Installation - Error Extracting'>submit an issue</a>.";}
	}
?>

<!doctype html>
<html>
	<head><title><?php echo $title;?> Installer</title></head>
	<body>
		<div style='text-align:center;'>
			<span style='font-weight:bold;font-size:24px;'>Welcome to the <?php echo $title;?> Installer!</span><br/><br/>
			<span style='font-size:18px;'>
				Make sure this file is saved to the root of your install directory.<br/><br/>
				If you'd like to install a repository other than the default, select it here:
			</span><br/>
			<form action='<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>' method='post'>
				<select name='branches'>
					<?php 
						foreach ($branches as $branch) {
							$branch = $branch['name'];
							echo "<option value='$branch'" . ($branch == $repBranch ? ' selected' : '') . ">$branch</option>";
						}
					?>
				</select><br/><br/>
				<input type='Submit' name='Submit' value='Install'>
			</form>
		</div>
	</body>
</html>
