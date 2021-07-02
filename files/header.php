<?php

	$css = substr(getcwd(), strrpos(getcwd(), '/') + 1);
	if($css == 'admin') {$css = '../files/';} elseif($css == 'files') {$css = '';} else {$css = 'files/';}
?>
<!DOCTYPE html>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
	<head>
		<?php if(isset($vehicle)) {echo '<title>' . $vehicle . '</title>';} else {echo "<title>Dad's Garage</title>";}?>
		<link rel='stylesheet' type='text/css' href='<?php echo $css . 'style.css';?>'>
		<script src='http://code.jquery.com/jquery-latest.min.js'></script>
	</head>
