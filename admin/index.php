<?php

	if (!isset($_SESSION)) {session_start();}

	$_SESSION['include'] = true;
	require_once '../includes/header.php';

	$_SESSION['include'] = true;
	require_once '../files/include.php';

	$_SESSION['include'] = true;
	require_once 'secure.php';

	if (isset($_POST['submit'])) {
		
		if (!empty($_POST['VIN'])) {
			$duplicate_vin      = false;

			foreach ($rows as $row) {
				if (!empty($row['vin']) && $row['vin'] == $_POST['VIN']) {
				    $duplicate_vin = true;
					break;
				}
			}

            if (!$duplicate_vin) {
				$vehicle_attributes  = array('Make', 'Model', 'ModelYear', 'Trim', 'VIN');
				$_SESSION['include'] = true;
    			require_once '../includes/vin-decoder.php';

    			// The decodeVIN function is in the '../includes/vin-decoder.php' file
    			$vinValues = decodeVIN($_POST['VIN'], ...$vehicle_attributes);
    
    			$insert->bindParam(':vin',   $vinValues['VIN']);
    			$insert->bindParam(':year',  $vinValues['ModelYear']);
    			$insert->bindParam(':make',  $vinValues['Make']);
    			$insert->bindParam(':model', $vinValues['Model']);
    			$insert->bindParam(':trim',  $vinValues['Trim']);
        		$insert->execute();
            } else {
    			$dupVIN = '<span class="red bold">VIN Exists: </span><a href="edit.php?id=' . $row['id'] . '" target="_blank" class="bold">' . $_POST['VIN'] . '</a>';
            }
		} else {
		    $blank = '';
			$insert->bindParam(':vin',   $blank);
			$insert->bindParam(':year',  $_POST['year']);
			$insert->bindParam(':make',  $_POST['make']);
			$insert->bindParam(':model', $_POST['model']);
			$insert->bindParam(':trim',  $_POST['trim']);
    		$insert->execute();

    		$success = '';
    		echo "<meta http-equiv=refresh content=\"3; URL=" . $_SERVER['REQUEST_URI'] . "\">";
		}
	}
?>
<body class='darkbg'>

	<div id='adminSidenav' class='adminsidenav'>
		<?php 
			$_SESSION['include'] = true;
			require_once '../includes/menu.php';
		?>
	</div>
	<div id='adminMain'>
		<div class='adminContainer' onclick='myFunction(this)'>
		  <div class='bar1'></div>
		  <div class='bar2'></div>
		  <div class='bar3'></div>
		</div>
		<div id='mainContainer' class='bgblue bord5 b-rad15 m-lrauto center m-top25'>
			<div class='huge bold center p15'>Admin Page</div>
			<hr />
			<div class='med bold'>Add Vehicle:</div>
			<form action='' method='post'>
				<center>
					<table class='m-bottom15'>
						<tr>
							<td colspan=2><input type='textbox' style='width:96%'    name='VIN' placeholder='VIN' value=''></td>
							<td rowspan=3><input type='submit'  style='margin:10px;' name='submit' value='Add'></td>
						</tr>
						<tr>
							<td><input type='textbox' style='width:100px' name='year'  placeholder='Year'  value=''></td>
							<td><input type='textbox' style='width:100px' name='make'  placeholder='Make'  value=''></td>
						</tr>
						<tr>
							<td><input type='textbox' style='width:100px' name='model' placeholder='Model' value=''></td>
							<td><input type='textbox' style='width:100px' name='trim'  placeholder='Trim'  value=''></td>
						</tr>
					</table>
					<?= (isset($dupVIN) ? $dupVIN : '');?>
					<span id='success' class='<?= $success;?>red bold'>Vehicle Added Successfully!</span>
				</center>
			</form>
			<hr /><br/>
			<button class='tablink width50' onclick="openTab('ForSale', this, 'left')"
				<?php echo (!isset($_SESSION['admin']) ? " id='defaultTab'" : '');?>
			>For Sale</button>
			<button class='tablink width50'	onclick="openTab('Sold', this, 'right')"
				<?php echo (isset($_SESSION['admin']) && $_SESSION['admin'] == 'sold' ? " id='defaultTab'" : '');?>
			>Sold</button>
			<div id='ForSale' class='tabcontent'>
				<table class='tbl-align htmlTable table'>
					<?php
						foreach ($rows as $row) {
							if ($row['status'] != 'Delete' && $row['status'] != 'Sold') {
								$pSelect1->bindParam(':vid', $row['id']);
								$pSelect1->execute();
								$photo1 = $pSelect1->fetchAll(PDO::FETCH_ASSOC);
								
								(isset($photo1[0]['filename']) 
									? $src = "<img src='../vehicles/" . $row['id'] . "/" . $photo1[0]['filename'] . "' width=100px>" 
									: $src = '(no photo)');

								($row['status']=='Draft'?$color=' red':$color=' blue');
								$name = ($row["year"]==0000?'':$row["year"])." ".$row["make"]." ".$row["model"]." ".$row["trim"];
								echo "<tr><td class='center'>".$src."</td><td class='td-align' nowrap><a href='edit.php?id=".$row["id"]."' class='small'>".$name."</a></td><td align='center' class='tdcenter".$color."' nowrap>".$row['status']."</td></tr>";
							}
						}
					?>
				</table>
			</div>
			<div id="Sold" class="tabcontent">
				<table class='tbl-align htmlTable table'>
					<?php
						foreach ($rows as $row) {
							if ($row['status'] == 'Sold') {
								$pSelect1->bindParam(':vid',$row["id"]);
								$pSelect1->execute();
								$photo1 = $pSelect1->fetchAll(PDO::FETCH_ASSOC);

								(isset($photo1[0]['filename']) 
									? $src = "<img src='../vehicles/" . $row['id'] . "/" . $photo1[0]['filename'] . "' width=100px>"
									: $src = '(no photo)');

								$name = $row["year"]." ".$row["make"]." ".$row["model"]." ".$row["trim"];
								echo "<tr><td>".$src."</td><td class='td-align'><a href='edit.php?id=".$row["id"]."' class='small'>".$name."</a></td></tr>";
							}
						}
					?>
				</table>
			</div>
		</div>
	</div>
	<script src="../files/admin.js"></script>
	<script type="text/javascript">
		document.getElementById("defaultTab").click();
/* 		<?php if(isset($_SESSION['view'])) {if ($_SESSION['view']=='sold') {echo 'viewSold();';} else {echo 'viewSale();';}} else {echo 'viewSale();';}?>
 */	</script>
</body>
</html>
