<?php
	require_once("files/include.php");
	file_put_contents("files/log.txt",date('Y-m-d H:i:s') . "," . $_SERVER['REMOTE_ADDR'] . "\n",FILE_APPEND);	//log date/time and IP data
?>
<body id="main" class='bg'><!--<p>Vehicles For Sale:</p>-->
	<div class='holder'>
		<?php 
			foreach ($rows as $row) {
				if ($row['status'] == 'For Sale') {
					$pSelect1->bindParam(':vid',$row["id"]);
					$pSelect1->execute();
					$photo1 = $pSelect1->fetchAll(PDO::FETCH_ASSOC);
					($photo1[0]['filename']?$src="vehicles/".$row['id']."/".$photo1[0]['filename']:$src="files/noimage.jpg");
					$name = ($row["year"]==0000?'':$row["year"])." ".$row["make"]." ".$row["model"]." ".$row["trim"];
					(trim($row['askprice'])!="0"?$asking=' - $'.$row['askprice']:$asking='');
					echo "<div class='vehicle'>\n<a href='vehicle.php?id=".$row['id']."'><img src='".$src."'></a><br />\n<a href='vehicle.php?id=".$row['id']."'>".$name.$asking." </a>\n</div>\n\n";
				}
			}
		?>
	</div>
</body>
</html>
