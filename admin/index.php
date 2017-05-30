<?php
	session_start();
	$site = "forsale";
	include($_SERVER['DOCUMENT_ROOT']."/".$site."/admin/secure.php");
	$url = "http://".$_SERVER['SERVER_NAME']."/".$site;
	if(!$_SESSION['isadmin']) {die("<meta http-equiv=refresh content=\"0; URL=".$url."/portal.php\">");}
	if(isset($_POST['submit'])) {
		$insert->bindParam(':year',$_POST["year"]);
		$insert->bindParam(':make',$_POST["make"]);
		$insert->bindParam(':model',$_POST["model"]);
		$insert->bindParam(':trim',$_POST["trim"]);
		$insert->execute();
		$success = '';
		echo "<meta http-equiv=refresh content=\"3; URL=".$_SERVER['REQUEST_URI']."\">";
	}
?>
<body class='bg'>
	<script src="http://code.jquery.com/jquery-3.1.0.min.js"></script>
	<div class="huge bold u center">Admin Page</div><br />
	<div class='bgblue bord5 p15 b-rad15 m-lrauto center' style='width:33%;min-width:410px;'>
		<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
				<div class="med bold">Add Vehicle:</div>
				<center><table class='m-bottom15'><tr><td>Year</td><td>Make</td><td>Model</td><td>Trim</td></tr>
				<tr><td><input type='textbox' style='width:40px' name='year' value=''></td><td>
				<input type='textbox' style='width:100px' name='make' value=''></td><td>
				<input type='textbox' style='width:100px' name='model' value=''></td><td>
				<input type='textbox' style='width:75px' name='trim' value=''></td><td>
				<input type="submit" name="submit" value="Add"></td></tr></td></tr></table>
				<span id='success' class='<?php echo $success;?>red bold'>Vehicle Added Successfully!</span></center>
		</form>
	</div>
	<div class='bgblue bord5 p15 b-rad15 m-lrauto center m-top25' style='width:33%; max-width:66%;display:table;'>
		<table class='viewing m-lrauto center'><tr>
			<td>Currently viewing:</td>
			<td><span id='lblSale' class='bold'>For Sale&nbsp;&nbsp;&nbsp;&nbsp;</span><span id='lblSold' class='bold'>Sold&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
			<td><button class='block' type='button' id='sale1' onclick="viewSold()">View Sold</button>
			<button class='block' type='button' id='hidden1' onclick="viewSale()">View For Sale</button></td>
		</tr></table>
		<p></p>
		<table class='m-lrauto center'><tr id='sale' style='display:block;'><td><table class='tbl-align htmlTable'>
		<?php
			foreach ($rows as $row) {
				if ($row['status'] != 'Delete' && $row['status'] != 'Sold') {
					$pSelect1->bindParam(':vid',$row["id"]);
					$pSelect1->execute();
					$photo1 = $pSelect1->fetchAll(PDO::FETCH_ASSOC);
					($photo1[0]['filename']?$src="<img src='../vehicles/".$row['id']."/".$photo1[0]['filename']."' width=100px>":$src='(no photo)');
					($row['status']=='Draft'?$color=' red':$color=' blue');
					$name = ($row["year"]==0000?'':$row["year"])." ".$row["make"]." ".$row["model"]." ".$row["trim"];
					echo "<tr><td class='center'>".$src."</td><td class='td-align' nowrap><a href='edit.php?id=".$row["id"]."' class='small'>".$name."</a></td><td align='center' class='tdcenter".$color."' nowrap>".$row['status']."</td></tr>";
				}
			}
		?>
		</table></td></tr>
		<tr id='hidden' style='display:none;'><td><table class='tbl-align htmlTable'>
		<?php
			foreach ($rows as $row) {
				if ($row['status'] == 'Sold') {
					$pSelect1->bindParam(':vid',$row["id"]);
					$pSelect1->execute();
					$photo1 = $pSelect1->fetchAll(PDO::FETCH_ASSOC);
					($photo1[0]['filename']?$src="<img src='../vehicles/".$row['id']."/".$photo1[0]['filename']."' width=100px>":$src='(no photo)');
					$name = $row["year"]." ".$row["make"]." ".$row["model"]." ".$row["trim"];
					echo "<tr><td>".$src."</td><td class='td-align'><a href='edit.php?id=".$row["id"]."' class='small'>".$name."</a></td></tr>";
				}
			}
		?>
		</table></td></tr></table>
		<p><a href="<?php echo $url;?>">Home</a> | <a href="<?php echo $logout;?>">Logout</a></p>
	</div>
	<script type="text/javascript">
		<?php if(isset($_SESSION['view'])) {if ($_SESSION['view']=='sold') {echo 'viewSold();';} else {echo 'viewSale();';}} else {echo 'viewSale();';}?>
		function viewSold() {
			document.getElementById('hidden').style.display = 'block';document.getElementById('sale').style.display = 'none';
			document.getElementById('hidden1').style.display = 'block';document.getElementById('sale1').style.display = 'none';
			document.getElementById('lblSold').style.display = 'block';document.getElementById('lblSale').style.display = 'none';
			$.ajax({type: "POST", url: 'ajax.php', data: {view: "sold"}}).done(function() {});
		}
		function viewSale() {
			document.getElementById('sale').style.display = 'block';document.getElementById('hidden').style.display = 'none';
			document.getElementById('sale1').style.display = 'block';document.getElementById('hidden1').style.display = 'none';
			document.getElementById('lblSale').style.display = 'block';document.getElementById('lblSold').style.display = 'none';
			$.ajax({type: "POST", url: 'ajax.php', data: {view: "forsale"}}).done(function() {});
		}
	</script>
</body>
</html>