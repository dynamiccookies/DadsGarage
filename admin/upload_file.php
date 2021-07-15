<?php

	if(!isset($_SESSION)){session_start();} 
	ini_set('memory_limit', '128M');
	include 'secure.php';

	function rearrange($arr){
		foreach ($arr as $key => $all) {
			foreach ($all as $i => $val) {
				$new[$i][$key] = $val;
			}
		}
		return $new;
	}

	function resize($photo){
		$fn    = $photo['tmp_name'];
		$size  = getimagesize($fn);

		// width/height
		$ratio = $size[0]/$size[1];

		if( $ratio > 1) {
			if($size[1] > 500){
				$height = 500;
				$width  = 500 * $ratio;
			}
		} else {
			if($size[1] > 500){
				$width  = 500 * $ratio;
				$height = 500;
			}
		}

		$src = imagecreatefromstring(file_get_contents($fn));
		$dst = imagecreatetruecolor($width, $height);
		imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
		imagedestroy($src);
		imagepng($dst, $photo['tmp_name']);
		imagedestroy($dst);			
	}
	
	$pinsert->bindParam(':vehicle',  $id);
	$pinsert->bindParam(':filename', $filename);
	$finsert->bindParam(':vehicle',  $id);
	$finsert->bindParam(':filename', $filename);
	$id  = $_GET['id'];
	$inc = 0;

	$allowedExts = array('gif', 'jpeg', 'jpg', 'png');
	$types       = array('image/gif', 'image/jpeg', 'image/jpg', 'image/pjpeg', 'image/x-png', 'image/png');
	echo "<body class='darkbg'><div id='mainContainer' class='bgblue bord5 p15 b-rad15 m-lrauto center m-top25' style='margin-top:auto !important;'>";
	echo '<h2><b>File Upload Summary:</b></h2>';
	try {
		if (isset($_FILES['photos'])) {
			$photos = rearrange($_FILES['photos']);
			$_SESSION['edit'] = 'photos';
			foreach ($photos as $photo) {
				$inc += 1;
				resize($photo);

				$ext = explode('.', $photo['name']);
				$ext = end($ext);
				$ext = strtolower($ext);

				if (in_array($ext, $allowedExts) && in_array($photo['type'], $types) && $photo['size'] < 10000000 && $photo['error'] == 0) {

					// Check if folder exists - create if false
					if (!file_exists('../vehicles')) {mkdir('../vehicles');}

					// Check if folder exists - create if false
					if (!file_exists('../vehicles/' . $id)) {mkdir('../vehicles/' . $id);}

					// Check if photo exists - show error if true, create if false
					if (file_exists('../vehicles/' . $id . '/' . $photo['name'])) {
						echo $inc . '. <b>File Already Exists</b>: ' . $photo['name'] . '<br><br>';
					} else {
						$filename = $photo['name'];
						move_uploaded_file($photo['tmp_name'], '../vehicles/' . $id . '/' . $photo['name']);
						$pinsert->execute();
						echo $inc . '. <b>Photo Successfully Saved</b>: ' . $photo['name'] . '<br><br>';
					}
				} else {
					if ($photo['error'] == 4) {
						echo $inc . '.<br><br>';
					} else {
						$wsize = '';
						$wtype = '';
						if ($photo['size'] >= 10000000) {$wsize = " class='red'";}
						if (!(in_array($photo['type'], $types))) {$wtype = " class='red'";}
						echo $inc . '. <b>Invalid File</b>: The file is either the wrong type or too large.<br>';
						echo '&nbsp;&nbsp;&nbsp;&nbsp;Name: ' . $photo['name'] . '<br>';
						echo '<span ' . $wsize . '>&nbsp;&nbsp;&nbsp;&nbsp;Size: ' . round($photo['size']/1024,2) . 'kb</span><br>';
						echo '<span ' . $wtype . '>&nbsp;&nbsp;&nbsp;&nbsp;Type: ' . $photo['type'] . '</span><br><br>';
					}
				}
			}
		} elseif (isset($_FILES['files'])) {
			$files = rearrange($_FILES['files']);
			$_SESSION['edit'] = 'files';
			foreach ($files as $file) {
				$inc += 1; 
				if ($file['size'] < 10000000 && $file['error'] == 0) {
					
					// Check if folder exists - create if false
					if (!file_exists('../vehicles')) {mkdir('../vehicles');}

					// Check if folder exists - create if false
					if (!file_exists('../vehicles/' . $id)) {mkdir('../vehicles/' . $id);}
					
					// Check if file exists - show error if true, create if false
					if (file_exists('../vehicles/' . $id . '/' . $file['name'])) {
						echo $inc . '. <b>File Already Exists</b>: ' . $file['name'] . '<br><br>';
					} else {
						$filename = $file['name'];
						move_uploaded_file($file['tmp_name'], '../vehicles/' . $id . '/' . $file['name']);
						$finsert->execute();
						echo $inc . '. <b>File Successfully Saved</b>: ' . $file['name'] . '<br><br>';
					}
				} else {
					if ($file['error'] == 4) {echo $inc . '.<br><br>';
					} else {
						$wsize = '';
						if ($file['size'] >= 10000000) {$wsize = " class='red'";}
						echo $inc . '. <b>Invalid File</b>: The file is too large.<br>';
						echo '&nbsp;&nbsp;&nbsp;&nbsp;Name: ' . $file['name'] . '<br>';
						echo '<span ' . $wsize . '>&nbsp;&nbsp;&nbsp;&nbsp;Size: ' . round($file['size']/1024,2) . 'kb</span><br>';
					}
				}
			}	
		}else {echo 'You have attempted to upload too many files at one time. <br>Please try again with a smaller quantity.';}
	} catch (Exception $e) {
    echo 'Error Message: ',  $e->getMessage(), '\n<br><br>You have most likely attempted to upload too many files at one time.';
}
	echo "\n<p>Go Back to <a href='edit.php?id=" . $id . "'>" . trim($rows[0]['year'] . ' ' . $rows[0]['make'] . 
		' ' . $rows[0]['model'] . ' ' . $rows[0]['trim']) . '</a>.</p></div></body>';
?>
