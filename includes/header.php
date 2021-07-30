<?php

	// Do not allow a direct connection to this file
	if (!isset($_SESSION['include'])) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	} else {unset($_SESSION['include']);}

	if (preg_match('/admin|files/', getcwd())) {$css = '../';}
	else {$css = '';}
?>
<!DOCTYPE html>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
	<head>
		<?php if(isset($vehicle)) {echo '<title>' . $vehicle . '</title>';} else {echo "<title>Dad's Garage</title>\n";}?>
		<link rel='stylesheet' type='text/css' href='<?= $css . 'css/style.css';?>'>
		<script src='//code.jquery.com/jquery-latest.min.js'></script>
	</head>
