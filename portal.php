<?php
	$site = "forsale";
	include($_SERVER['DOCUMENT_ROOT']."/".$site."/admin/secure.php");

?>
<body class="bg">
	<div class='u center huge bold'>
		<?php echo $_SESSION['fname']." ".$_SESSION['lname']?>
	</div>
	<center><p><b>Disclaimer:</b> If you are not <?php echo $_SESSION['fname']." ".$_SESSION['lname']?>, log out immediately. <br>This is private information.
	<br><a href="<?php echo "admin/".$logout;?>">Logout</a><br><a href="admin">Admin</a></p></center>
	<p>&nbsp;</p>
	<div class='bgblue bord5 p15 b-rad15 m-lrauto center m-top25' style='width:33%; max-width:66%;display:table;'>
		<table class='m-lrauto center'><tr id='sale' style='display:block;'><td><table class='tbl-align htmlTable'>
		<?php
			foreach ($rows as $row) {
				if ($row['buyer'] != NULL) {
					if ($row['buyer'] == $_SESSION['buyerid']) {
						$pSelect1->bindParam(':vid',$row["id"]);
						$pSelect1->execute();
						$photo1 = $pSelect1->fetchAll(PDO::FETCH_ASSOC);
						($photo1[0]['filename']?$src="<img src='vehicles/".$row['id']."/".$photo1[0]['filename']."' width=100px>":$src='(no photo)');
						($row['status']=='Draft'?$color=' red':$color=' blue');
						$name = ($row["year"]==0000?'':$row["year"])." ".$row["make"]." ".$row["model"]." ".$row["trim"];
						echo "<tr><td class='center'>".$src."</td><td class='td-align' nowrap>".$name."</td></tr>";
						//<td align='center' class='tdcenter".$color."' nowrap>".$row['status']."</td>
					}	
				}
			}
		?>
		</table></td></tr></table>
	</div>
</body>
</html>