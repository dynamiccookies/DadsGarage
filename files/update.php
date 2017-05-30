<?php
	if(!defined('included')) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}
	function update($repo) {
 		try {
			$repository = 'https://github.com/dynamiccookies/DadsGarage/'; //URL to GitHub repository
			$repBranch = $repo;
			$source = 'DadsGarage-'.$repBranch; //RepositoryName-Branch
			$redirectURL = 'settings.php'; //Redirect URL - Leave blank for no redirect
			$file = file_put_contents(dirname(__DIR__)."/install.zip", fopen($repository."archive/".$repBranch.".zip", 'r'), LOCK_EX);
			if($file === FALSE) die("Error Writing to File: Please <a href=\"".$repository."issues/new?title=Installation - Error Writing to File\">click here</a> to submit a ticket.");
			$zip = new ZipArchive;
			$res = $zip->open(dirname(__DIR__).'/install.zip');
			if ($res === TRUE) {
				for($i=0; $i<$zip->numFiles; $i++) {
					$name = $zip->getNameIndex($i);
					if (strpos($name, "{$source}/") !== 0) continue;
					$file = dirname(__DIR__).'/'.substr($name, strlen($source)+1);
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
				unlink(dirname(__DIR__).'/install.zip');
				unlink(dirname(__DIR__).'/.gitignore');
				if ($redirectURL) echo "<meta http-equiv=refresh content=\"0; URL=".$redirectURL."\">";
				$results = 'Application Updated Successfully!';
			} else {
				echo "Error Extracting Zip: Please <a href=\"".$project."issues/new?title=Installation - Error Extracting\">click here</a> to submit a ticket.";
				$results = 'Something went wrong!';
			}
		} catch (Exception $e){$results = 'Something went wrong!<br/>'.$e;}
		return $results;
	}
?>
