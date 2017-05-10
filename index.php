<?php
	$site = "forsale";
	require($_SERVER['DOCUMENT_ROOT']."/".$site."/files/include.php");
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/".$site."/files/log.txt",date('Y-m-d H:i:s') . "," . $_SERVER['REMOTE_ADDR'] . "\n",FILE_APPEND);	//log date/time and IP data
?>
<body id="main" class='bg'><p>Vehicles For Sale:</p>
	<div class='holder'>
		<?php 
			foreach ($rows as $row) {
				if ($row['status'] == 'For Sale') {
					$pSelect1->bindParam(':vid',$row["id"]);
					$pSelect1->execute();
					$photo1 = $pSelect1->fetchAll(PDO::FETCH_ASSOC);
					($photo1[0]['filename']?$src="vehicles/".$row['id']."/".$photo1[0]['filename']:$src=$_SERVER['DOCUMENT_ROOT']."/".$site."/files/noimage.jpg");
					$name = ($row["year"]==0000?'':$row["year"])." ".$row["make"]." ".$row["model"]." ".$row["trim"];
					(trim($row['askprice']) !== ""?$asking='$'.$row['askprice']:$asking='');
					echo "<div class='vehicle'>\n<a href='vehicle.php?id=".$row['id']."'><img src='".$src."'></a><br />\n<a href='vehicle.php?id=".$row['id']."'>".$name." - ".$asking." </a>\n</div>\n\n";
				}
			}
		?>
	</div>
	<div class='disclaimer'>DISCLAIMER: This is not a business. I'm just a really big nerd and love making websites, and each person in my very large family works on, fixes, and sells vehicles to make a little extra money. I thought it would make sense to create a place where we could all showcase the vehicles we have for sale, and allow potential buyers a simple location to view them all.</div>
</body>
</html>