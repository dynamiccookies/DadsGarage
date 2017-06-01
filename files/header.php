<?php
	$path=substr(getcwd(), strrpos(getcwd(), '/') + 1);
	if ($path=="admin") {$path="../files/style.css";} 
	elseif ($path=="files") {$path="style.css";} 
	else {$path="files/style.css";}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head><!--Updated Header-->
		<title>Page</title>
		<link rel="stylesheet" type="text/css" href="<?php echo $path;?>">
	</head>