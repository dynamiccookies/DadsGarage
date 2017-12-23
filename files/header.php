<?php
	$path=substr(getcwd(), strrpos(getcwd(), '/') + 1);
	if ($path=="admin") {$path="../files/style.css";} elseif ($path=="files") {$path="style.css";} else {$path="files/style.css";}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<?php if ($vehicle) {echo "<title>".$vehicle."</title>";} else {echo "<title>Dad's Garage</title>";}?>
		<link rel="stylesheet" type="text/css" href="<?php echo $path;?>">
		<script src="http://code.jquery.com/jquery-latest.min.js"></script>
	</head>
