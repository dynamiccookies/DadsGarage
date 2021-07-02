<?php

	if(!isset($_SESSION)){session_start();} 
	if(isset($_POST['view'])) {
		$_SESSION['view']=$_POST['view'];
	} else { $_SESSION['view']='forsale';}
?>