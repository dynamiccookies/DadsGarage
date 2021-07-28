<?php

	if (!isset($_SESSION)) {session_start();}

	if (!$_GET['id']) {
		header('Location: index.php');
		exit;
	}

	$_SESSION['include'] = true;
	require_once 'includes/header.php';

	$_SESSION['include'] = true;
	require_once 'files/include.php';

	date_default_timezone_set("America/Chicago"); //What is this for??

	$id=$_GET['id'];	//Vehicle ID
	$oSelect1->bindParam(":oid",$rows[0]['owner']);
	$oSelect1->execute();
	$owner = $oSelect1->fetchAll(PDO::FETCH_ASSOC);	//Array with owner data

	$email=$owner[0]['email'];
	$phone=$owner[0]['phone'];
	(trim($rows[0]['askprice']) !== ""?$price='$'.$rows[0]['askprice']:$price='');
	$status = $rows[0]['status'];
	
	$base_url = "http://" . $_SERVER['SERVER_NAME'];
	$base_name = trim(($rows[0]["year"]==0000?'':$rows[0]["year"])." ".$rows[0]["make"]." ".$rows[0]["model"]." ".$rows[0]["trim"]);
	$txt = $rows[0]['pubnotes'];
	$img = $pRows;
	
	//Custom Location ---------------------------------------------
	$loc = (isset($_GET['1']) ? $_GET['1'] : '');	//get 'l' param for location
	
	//QR Code -----------------------------------------------------
	$qr = (isset($_GET['qr']) ? $_GET['qr'] : '');	//get 'qr' param for qr code
	if ($qr == 1) {		//if qr scanned
		$qrlog = date('Y-m-d H:i:s') . "," . $base_name . "," . $_SERVER['REMOTE_ADDR'] . ($loc ? ',' . $loc : "") . "\n";	//get date/time and IP qr code scanned on
		file_put_contents("files/qr.log",$qrlog,FILE_APPEND);	//log date/time and IP data
	}
	//bit.ly variables-Used for short URL on QR code.--------------
	$login = 'o_31ku8f5rm';
	$appkey = 'R_6380f8f15f3f4636a46990da43165ba8';
	//$bitly = get_bitly_short_url($base_url . $_SERVER['REQUEST_URI'] . "&qr=1" . ($loc ? '&l=' . $loc : ""),$login,$appkey);
	$bitly = get_bitly_short_url($base_url . $_SERVER['REQUEST_URI'] . "&qr=1",$login,$appkey);
	$currentURL = "http://chart.apis.google.com/chart?cht=qr&chld=h&chl=" . $bitly;  

	//The following bit.ly api code was borrowed from http://bit.ly/1qiGMip and then tweaked
	function get_bitly_short_url($url,$login,$appkey,$format='txt') {
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$appkey.'&uri='.urlencode($url).'&format='.$format);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
?>
<script>function clickpic ($inc){return document.getElementById('preview').src=document.getElementById('img' + $inc).src;}</script>
<link rel="stylesheet" type="text/css" href="files/slider.css">
<body class="darkbg">
								<!-- START OF ON SCREEN -->
	<div class='bgblue bord5 p15 b-rad15 m-lrauto center noprint' style='width:66%;max-width:80%;'>
		<div id="main">
			<div class='big bold center prtblue'><?php echo $base_name.($status=='Sold'?" - <span class='red bold'>".strtoupper($status)."</span>":($price!="$0"?" - ".$price:""));?></div>
			<!--<a class='noprint med bold' href="mailto:&subject=<?php echo str_replace(' ','%20',$base_name)?>">Request More Information</a>-->
		</div>
		<div class='content'>	<!-- Content -->
			<?php if (strlen($txt) <=0) {echo ("\n<!--<h2 class='noprint'>Information coming soon.</h2>--><p></p>");} else {echo ("<h3>" . $txt . "</h3>");}?>
		</div>
		<!-- Photo Gallery - Following code was borrowed from https://codepen.io/AMKohn/pen/EKJHf?editors=1100 -->
		<div class="gallery" align="center">
			<ul class="slides">
				<?php 
					$z = 1; 
					foreach ($img as $pic) {
						echo "<input type='radio' name='radio-btn' id='img-".$z."' ".($z==1?"checked":"")." />\n<li class='slide-container'>\n";
						echo "<div class='slide'>\n<img src='vehicles/".$rows[0]['id']."/".$pic['filename']."' />\n</div>\n";
						echo "<div class='nav'>\n<label for='img-".($z==1?count($img):$z-1)."' class='prev'>&#x2039;</label>\n";
						echo "<label for='img-".($z==count($img)?1:$z+1)."' class='next'>&#x203a;</label>\n</div>\n</li>\n";
						$z = $z + 1;
					}
					echo "</ul>\n\n<p></p>\n<ul>\n";
					$z = 1; 
					echo "<li class='nav-dots'>\n";
					foreach ($img as $pic) {
						echo "<label for='img-".$z."' class='nav-dot' id='img-dot-".$z."'></label>\n";
						$z = $z + 1;
					}
					echo "</li>\n";
				?>
			</ul>
			Click the left or right side of the image to scroll through the gallery.
		</div>
	</div> 						<!-- END OF ON SCREEN -->
	<!---------------------------------------------------------------------------------->
								<!-- START OF PRINT VIEW -->
	<div class='bgblue bord5 p15 b-rad15 m-lrauto center noscreen'>
		<div class='leftspacer'>&nbsp;</div>
		<div id="main">
			<div class='big bold center prtblue'><?php echo $base_name.($status=='Sold'?" - <span class='red bold'>".strtoupper($status)."</span>":($price!="$0"?" - ".$price:""));?></div>
			<div class='med'>
				<?php echo ($phone != "" ? "<b>Call:</b> " . $phone . "&nbsp;&nbsp;|&nbsp;&nbsp;" : ""); echo ($email != "" ? "<b>Email:</b> " . $email : "");?>
			</div>
		</div>
		<img class='qr' src="<?php echo $currentURL?>&chs=90x90" />
		<div class='topspacer'></div><div class='content'>	<!-- Content -->
			<?php if (strlen($txt)>0) {echo ("<h3>" . $txt . "</h3>");}?>
		</div>
		<img id="preview" align="center" src='vehicles/<?php echo $rows[0]['id']."/".$img[0]['filename'] ?>' alt="No Image Loaded" />
	</div>	
	<div class='scan noscreen'>Scan the QR code with your phone to view more pictures and details, or go to <?php echo $bitly;?>.</div>
	<table class='noscreen'>	<!--Tear Off Section-->
		<tr class='info'>
			<?php for ($i=0; $i<9; $i++) {echo "<td class='rotate'><div><span>" . $base_name . "<br>" . $phone . " - " . $price . "<br>" . $email . "</span></div></td>\n";}?>
		</tr>
		<tr class='smallqr'>
			<?php
				for ($i=0; $i<9; $i++) {
					echo "<td class='rotate'><div><span>\n";
					echo "\t<img class='qr' src='" . $currentURL . "&chs=75x75' />\n";
					echo "</span></div></td>\n";
				}
			?>
		</tr>
	</table>
								<!-- END OF PRINT VIEW -->
</body>