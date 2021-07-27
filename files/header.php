<?php

	if (preg_match('/admin|files/', getcwd())) {$css = '../';}
	else {$css = '';}
?>
<!DOCTYPE html>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
	<head>
		<?php if(isset($vehicle)) {echo '<title>' . $vehicle . '</title>';} else {echo "<title>Dad's Garage</title>";}?>
		<link rel='stylesheet' type='text/css' href='<?= $css . 'files/style.css';?>'>
		<script src='//code.jquery.com/jquery-latest.min.js'></script>
	</head>
