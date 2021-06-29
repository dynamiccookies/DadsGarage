<?php
	$css = substr(getcwd(), strrpos(getcwd(), '/') + 1);
	if ($css == 'admin') {$css = '../files/style.css';} elseif ($css == 'files') {$css = 'style.css';} else {$css = 'files/style.css';}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<link rel='stylesheet' type='text/css' href='<?php echo $css ;?>'>
		<?php if(isset($vehicle)) {echo '<title>' . $vehicle . '</title>';} else {echo "<title>Dad's Garage</title>";}?>
		<script src='http://code.jquery.com/jquery-latest.min.js'></script>
	</head>
