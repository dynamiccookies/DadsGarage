<?php

	if (!isset($_SESSION)) {session_start();}
	
	if (!$_GET['id']) {
		header('Location: index.php');
		exit;
	}

	$_SESSION['include'] = true;
	require_once '../includes/header.php';

	$_SESSION['include'] = true;
	require_once '../admin/secure.php';

	$_SESSION['include'] = true;
	require_once '../includes/include.php';

	$dir     = '../vehicles';
	$id      = $_GET['id'];
	$reload  = '<meta http-equiv=refresh content="0; URL=' . $_SERVER['REQUEST_URI'] . '">';
	$vehicle = trim(($rows[0]['year'] == 0000 ? '' : $rows[0]['year']) . ' ' . $rows[0]['make'] . ' ' . $rows[0]['model'] . ' ' . $rows[0]['trim']);

	if(!file_exists($dir) && !is_dir($dir)) {mkdir($dir);}

	if(isset($_POST['SubmitTop'])) {
        $_POST['miles']    = str_replace(',', '', $_POST['miles']);
	    $_POST['askprice'] = (float) str_replace(str_split('$,'), '', $_POST['askprice']);
	    (isset($_POST['insured']) ? $_POST['insured'] = 1 : $_POST['insured'] = 0);
        
        if (empty($_POST['sold_date'])) $_POST['sold_date'] = null;

		$update->bindParam(':vin',       $_POST['vin']);
		$update->bindParam(':year',      $_POST['year']);
		$update->bindParam(':make',      $_POST['make']);
		$update->bindParam(':model',     $_POST['model']);
		$update->bindParam(':trim',      $_POST['trim']);
		$update->bindParam(':miles',     $_POST['miles']);
		$update->bindParam(':owner',     $_POST['owner']);
		$update->bindParam(':askprice',  $_POST['askprice']);
		$update->bindParam(':status',    $_POST['status']);
		$update->bindParam(':insured',   $_POST['insured']);
		$update->bindParam(':payment',   $_POST['payment']);
		$update->bindParam(':paynotes',  $_POST['soldnotes']);
		$update->bindParam(':sold_date', $_POST['sold_date']);
		$update->execute();
		echo $reload;
	}
	if(isset($_POST['SubmitDesc'])) {
		$updateDesc->bindParam(':pubnotes', $_POST['pubnotes']);
		$updateDesc->execute();
		echo $reload;
	}
	if(isset($_POST['SubmitInternal'])) {
		$updateInternal->bindParam(':intnotes', $_POST['intnotes']);
		$updateInternal->execute();
		$_SESSION['edit'] = 'internal';
		echo $reload;
	}
	if(isset($_POST['delPic'])) {

		// Delete picture file
		unlink($_POST['pic']);

		// Delete database entry for picture file
		$pDelete->bindParam(':pid', $_POST['pid']);
		$pDelete->execute();

		$_SESSION['edit'] = 'photos';
		echo '<script>window.location.replace("' . $_SERVER['REQUEST_URI'] . '#' . (isset($_POST['oldpic']) ? $_POST['oldpic'] : '') . '");</script>';
	}
	if(isset($_POST['updatepic'])) {

		//Update photo name and size
		rename($_POST['fullpic'], str_replace($_POST['oldpic'], $_POST['picname'], $_POST['fullpic']));

		$pUpdate->bindParam(':pid', $_POST['pid']);
		$pUpdate->bindParam(':filename', $_POST['picname']);
		$pUpdate->execute();
		$_SESSION['edit'] = 'photos';
		echo '<script>window.location.replace("edit.php?id=' . $id . '"); </script>';
	}
	if(isset($_POST['delFile'])) {

		// Delete file
		unlink($_POST['file']);

		//Delete database entry
		$fDelete->bindParam(':fid',$_POST['fid']);
		$fDelete->execute();
		
		$_SESSION['edit'] = 'files';
		echo $reload;
	}
	if(isset($_POST['updateFile'])) {

		// Update file name
		rename($_POST['fullFile'], str_replace($_POST['oldFile'], $_POST['filename'], $_POST['fullFile']));
		$fUpdate->bindParam(':fid', $_POST['fid']);
		$fUpdate->bindParam(':filename', $_POST['filename']);
		$fUpdate->execute();
		$_SESSION['edit'] = 'files';
		echo $reload;
	}
	if(isset($_POST['eupdate'])) {
		$date = date('Y/m/d', strtotime(str_replace('-', '/', $_POST['date'])));

		$eUpdate->bindParam(':eid',$_POST['eid']);
		$eUpdate->bindParam(':date', $date);
		$eUpdate->bindParam(':desc', $_POST['desc']);
		$eUpdate->bindParam(':cost', str_replace('$', '', $_POST['cost']));
		$eUpdate->execute();

		$_SESSION['edit'] = 'expenses';
		echo $reload;
	}
	if(isset($_POST['einsert'])) {
		$date = date('Y/m/d', strtotime(str_replace('-', '/', $_POST['date'])));

		$eInsert->bindParam(':vehicle', $_POST['vehicle']);
		$eInsert->bindParam(':date', $date);
		$eInsert->bindParam(':desc', $_POST['desc']);
		$eInsert->bindParam(':cost', str_replace('$', '', $_POST['cost']));
		$eInsert->execute();

		$_SESSION['edit'] = 'expenses';
		echo $reload;
	}
?>
<body class='darkbg' onLoad='updateOwner()'>
	<div id='adminSidenav' class='adminsidenav'>
		<?php 
			$_SESSION['include'] = true;
			require_once '../includes/menu.php';
		?>
	</div>
	<div id='adminMain'>
		<div class='adminContainer' onclick='myFunction(this)'><div class='bar1'></div><div class='bar2'></div><div class='bar3'></div></div>
		<div id='mainContainer' class='bgblue bord5 b-rad15 clear m-lrauto m-bottom25 bold'>
			<form action='<?= htmlspecialchars($_SERVER['REQUEST_URI']);?>' method='post'>
				<table style='padding:15px;'>
					<tr><!-- Row 1 -->
						<td colspan='12' class='center huge bold'>
							<a href='../vehicle.php?id=<?= $rows[0]['id'];?>' target='_blank'><?= $vehicle;?></a><br/><hr size='3px'/>
						</td>
					</tr>
					<tr><!-- Row 2 -->
						<td nowrap>VIN: 
							<?= (strlen($rows[0]['vin']) == 17 ? 
								"(<a href='http://www.vindecoder.net/?vin=" . $rows[0]['vin'] . "&submit=Decode' target='_blank'>Decode</a>)" :
								'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
							?>
						</td>
						<td><input style='width:165px;' type='textbox' tabindex=1 name='vin' value='<?= strtoupper($rows[0]['vin'])?>'></td>
						<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
						<td>Year:</td>
						<td><input style='width:90px;' type='textbox' tabindex=4 name='year' value='<?= ($rows[0]['year'] == 0000 ? '' : $rows[0]['year']);?>'></td>
						<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
						<td>Owner:</td>
						<td nowrap>
							<select id='ownerdd' tabindex=9 name='owner' onchange='updateOwner()'>
								<option value=0>Select Owner...</option>
								<?php
									foreach ($oRows as $ownerdd) {
										($ownerdd['id'] == $rows[0]['owner'] ? $selected = ' selected' : $selected = '');
										echo '<option value=' . $ownerdd['id'] . $selected . '>' . $ownerdd['name'] . '</option>\n';
									}
								?>
							</select>
						</td>
						<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
						<td rowspan=5><?= (isset($pRows[0]['filename']) ? "<img src='../vehicles/" . $id . '/' . $pRows[0]['filename'] . "' width=200px />" : '');?></td>
						<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
					</tr>
					<tr><!-- Row 3 -->
						<td nowrap><?= ($rows[0]['status'] == 'Sold' ? 'Sale' : 'Asking');?> Price:</td>
						<td><input style='width:165px;' type='textbox' tabindex=2 name='askprice' value='<?php 
							if (strpos($rows[0]['askprice'], '$') === FALSE && trim($rows[0]['askprice']) !== '') {
								echo '$' . number_format($rows[0]['askprice']);
							}
						?>'></td>
						<td>&nbsp;</td>
						<td>Make:</td>
						<td><input style='width:90px;' type='textbox' name='make' tabindex=5 value='<?= $rows[0]['make']?>'></td>
						<td>&nbsp;</td>
						<td>Phone:</td>
						<td><span id='phonenum' class='show'></span></td>
						<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
					<tr><!-- Row 4 -->
						<td nowrap>Status:</td>
						<td nowrap>
							<select name='status' id='status' onchange='statusChange()' style='min-width:84px;' tabindex=3>
							<?php
								$statuses = array('Draft', 'For Sale', 'Sold', 'Delete');
								foreach($statuses as $stat){
									($stat == $rows[0]['status'] ? $selected = ' selected' : $selected = '');
									echo "<option value='" . $stat . "'" . $selected . '>' . $stat . '</option>\n';
								}
							?>
							</select>
							<select name='payment' id='payment' onchange='paymentType()' class='<?= ($rows[0]['status'] != 'Sold' ? 'noscreen ' : '');?>'>
								<?php
									$statuses = array('Cash', 'Payments', 'Trade');
									foreach ($statuses as $stat) {
										($stat == $rows[0]['payment'] ? $selected = ' selected' : $selected = '');
										echo "<option value='" . $stat . "'" . $selected . '>' . $stat . '</option>\n';
									}
								?>
							</select>
						</td>
						<td>&nbsp;</td>
						<td>Model:</td>
						<td><input style='width:90px;' type='textbox' tabindex=6 name='model' value='<?= $rows[0]['model']?>'></td>
						<td>&nbsp;</td>
						<td>Email:</td>
						<td><span id='emailadd' class='show'></span></td>
						<td colspan=3>&nbsp;</td>
					</tr>
					<tr><!-- Row 5 -->
						<td>
							<span id='buyer' class='<?= ($rows[0]['status'] != 'Sold' ? 'noscreen ' : '');?>'>Buyer:</span>
							<span id='lblinsured' class='block' name='lblinsured'>
                                <?php 
                                    // Need to build out the insurance info section further - Placeholder for now
                                    $insuranceEmail = '';
                                    echo "<a href='mailto:$insuranceEmail?subject=Auto%20Insurance&body=$vehicle%20-%20VIN:%20";
                                    echo strtoupper($rows[0]['vin']) . " title='Send Email to insurance company'>Insured?</a>:";
                                ?>
                            </span>
						</td>
						<td nowrap>
							<input type='textbox' style='width:78px;' class='<?= ($rows[0]['status'] != 'Sold' ? 'noscreen ' : '');?>' id='fname' name='fname' placeholder='First Name' value=''>
							<input type='textbox' style='width:78px;' class='<?= ($rows[0]['status'] != 'Sold' ? 'noscreen ' : '');?>' id='lname' name='lname' placeholder='Last Name' value=''>
							<input type='checkbox' class='block' id='insured' name='insured' value='insured'<?= ($rows[0]['insured'] == 1 ? " checked='checked'" : '')?>>
						</td>
						<td>&nbsp;</td>
						<td>Trim:</td>
						<td><input style='width:90px;' type='textbox' tabindex=7 name='trim' value='<?= $rows[0]['trim']?>'></td>
						<td colspan=4><center><font class='red' id='notice'>&nbsp;</font></center></td>
						<td colspan=2>&nbsp;</td>
					</tr>
					<tr><!-- Row 6 -->
						<td><span id='lblsaledate' class='<?= ($rows[0]['status'] != 'Sold' ? 'noscreen ' : '');?>'>Sale Date:</span></td>
						<td>
							<input 
								type='date' 
								id='sold_date' 
								class='<?= ($rows[0]['status'] != 'Sold' ? 'noscreen ' : '');?>' 
								style='width:167px;' 
								name='sold_date'
								value='<?= (isset($rows[0]['sold_date']) ? $rows[0]['sold_date'] : '');?>'
							>
						</td>
						<td>&nbsp;</td>
						<td>Miles:</td>
						<td><input style='width:90px;' type='textbox' name='miles' tabindex=8 value='<?= number_format($rows[0]['miles'])?>'></td>
						<td>&nbsp;</td>
						<td colspan=2><center><input type='Submit' tabindex=12 name='SubmitTop' id='SubmitTop' value='Save'></center></td>
						<td colspan=3>&nbsp;</td>
					</tr>
				</table>
			</form>
			<br/><hr><br/>
			<button class='tablink width20' onclick="openTab('Description', this, 'left')"
				<?= (!isset($_SESSION['edit']) ? ' id="defaultTab"' : '');?>
			>Description</button>
			<button class='tablink width20' onclick="openTab('Internal', this, 'middle')"
				<?= (isset($_SESSION['edit']) && $_SESSION['edit'] == 'internal' ? ' id="defaultTab"' : '');?>
			>Internal</button>
			<button class='tablink width20' onclick="openTab('Expenses', this, 'middle')"
				<?= (isset($_SESSION['edit']) && $_SESSION['edit'] == 'expenses' ? ' id="defaultTab"' : '');?>
			>Expenses</button>
			<button class='tablink width20' onclick="openTab('Files', this, 'middle')"
				<?= (isset($_SESSION['edit']) && $_SESSION['edit'] == 'files' ? ' id="defaultTab"' : '');?>
			>Files</button>
			<button class='tablink width20' onclick="openTab('Photos', this, 'right')"
				<?= (isset($_SESSION['edit']) && $_SESSION['edit'] == 'photos' ? ' id="defaultTab"' : '');?>
			>Photos</button>
			<div id='Description' class='tabcontent'>
				<form action='<?= htmlspecialchars($_SERVER['REQUEST_URI']);?>' method='post'>
					<textarea class='boxsizingBorder' tabindex=10 style='padding:10px;margin-top:5px;width:90%;height:200px;' name='pubnotes'><?= $rows[0]['pubnotes'];?></textarea><br/>
					<input type='Submit' tabindex=11 name='SubmitDesc' id='SubmitDesc' value='Save'>
				</form>
			</div>
			<div id='Internal' class='tabcontent'>
				<form action='<?= htmlspecialchars($_SERVER['REQUEST_URI']);?>' method='post'>
					<textarea class='boxsizingBorder' tabindex=12 style='padding:10px;margin-top:5px;width:90%;height:200px;' name='intnotes'><?= $rows[0]['intnotes']?></textarea><br/>
					<input type='Submit' tabindex=13 name='SubmitInternal' id='SubmitInternal' value='Save'>
				</form>
			</div>
			<div id='Expenses' class='tabcontent'>
				<div class='bgblue p15 b-rad15 bord5'>
					Expenses:<br>
					<center>
						<table>
							<tr>
								<td width='80px'>Date</td>
								<td width='205px'>Description</td>
								<td>Cost</td>
							</tr>
							<?php
								if (!empty($eRows)) {
									foreach ($eRows as $exp) {
										$date = ($exp['date'] != '0000-00-00' && $exp['date'] != '1969-12-31' ? date('m/d/Y', strtotime($exp['date'])) : '');
							?>
							<tr>
								<td nowrap colspan=3>
									<form action='<?= htmlspecialchars($_SERVER['REQUEST_URI'])?>' method='post'>
										<input type='hidden' name='eid' value='<?= $exp['id']?>'>
										<input style='width:75px;' type='textbox' name='date' value='<?= $date?>'>&nbsp;
										<input style='width:200px;' type='textbox' name='desc' value='<?= $exp['description']?>'>&nbsp;
										<input style='width:50px;' type='textbox' name='cost' value='<?= $exp['cost']?>'>&nbsp;
										<input type='submit' name='eupdate' value='Update'>
									</form>
								</td>
							</tr>
							<?php }?>
							<tr>
								<td nowrap colspan=2 align=right>Total: </td>
								<td class='red'>$<?= $eTotal[0]['Total']?></td>
							</tr>
							<?php }?>
							<tr>
								<td nowrap colspan=3>
									<form action='<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]);?>' method='post'>
										<input type='hidden' name='vehicle' value='<?php echo $_GET['id']?>'>
										<input style='width:75px;' type='textbox' name='date' value=''>&nbsp;
										<input style='width:200px;' type='textbox' name='desc' value=''>&nbsp;
										<input style='width:50px;' type='textbox' name='cost' value=''>&nbsp;
										<input type='submit' name='einsert' value='Insert'>
									</form>
								</td>
							</tr>
						</table>
					</center>
				</div>
			</div>
			<div id='Files' class='tabcontent'>
				<div class='bgblue p15 b-rad15 bord5'>
					Files:<br>
					<div>
						<form action='../admin/upload.php?id=<?= $rows[0]['id'];?>' method='post' enctype='multipart/form-data'>
						    <input type='hidden' name='include' value='true'>
							<input name='files[]' type='file' multiple /><input type='submit' value='Upload files' />
						</form>
						<br><center><img src='../files/loading_anim.gif' class='noscreen' id='loading1' width=100px></center>
					</div>
					<hr class='m-top25 m-bottom25 center red' style='width:80%;' />
					<div><center>
						<?php 
							if ($fRows) {
								echo "<table class='photos'>";
								foreach ($fRows as $file) {
									$path='../vehicles/' . $id . '/' . $file['filename'];
						?>
									<tr>
										<td nowrap align='right'>
											<form action='<?= htmlspecialchars($_SERVER['REQUEST_URI'])?>' method='post'>
												<a class='show' href='<?= $path?>' target='_blank'><?= $file['filename']?></a>
												<input type='hidden' name='fullFile' value='<?= $path?>'>
												<input type='hidden' name='oldFile' value='<?= $file['filename']?>'>
												<input class='noscreen' type='textbox' style='width:150px' name='filename' value='<?= $file['filename']?>'>
												<input type='hidden' name='fid' value='<?= $file['id']?>'>
												&nbsp;&nbsp;&nbsp;&nbsp;<a href='.' class='show fedit hide'>Edit</a>&nbsp;&nbsp;&nbsp;&nbsp;
												<input class='noscreen' type='submit' name='updateFile' value='Update'>
											</form>
										</td>
										<td>
											<form action='<?= htmlspecialchars($_SERVER['REQUEST_URI'])?>' method='post'>
												<input type='hidden' name='file' value='<?= $path?>'>
												<input type='hidden' name='fid' value='<?= $file['id']?>'>
												<input type='submit' name='delFile' value='Delete'>
											</form>
										</td>
									</tr>
						<?php		}
								echo '</table>';
							} else {echo ('There are no files for this vehicle.');}
						?>
					</center></div>
				</div>
			</div>
			<div id='Photos' class='tabcontent'>
				<div class='bgblue p15 b-rad15 bord5'>
					Photos:<br>
					<div>
						<form action='../admin/upload.php?id=<?= $rows[0]['id'];?>' method='post' enctype='multipart/form-data'>
						    <input type='hidden' name='include' value='true'>
							<input name='photos[]' type='file' multiple accept='image/*' /><input type='submit' onclick='loading2()' value='Upload photos' />
						</form>
						<br><center><img src='../files/loading_anim.gif' class='noscreen' id='loading2' width=100px></center>
					</div>
					<hr class='m-top25 m-bottom25 center red' style='width:80%;' />
					<div><center>
						<?php 
							if ($pRows) {
								echo "<table class='photos'>";
								foreach ($pRows as $image) {
									$path = '../vehicles/' . $id . '/' . $image['filename'];
						?>
									<tr>
										<td><a href='<?= $path?>' target='_blank' id='<?= $image['filename']?>'><img src='<?= $path?>' width=200px /></a></td>
										<td nowrap>
											<form action='<?= htmlspecialchars($_SERVER['REQUEST_URI'])?>' method='post'>
												<input type='hidden' name='fullpic' value='<?= $path?>'>
												<input type='hidden' name='oldpic' value='<?= $image['filename']?>'>
												<input type='textbox' style='width:150px' name='picname' value='<?= $image['filename']?>'>
												<input type='hidden' name='pid' value='<?= $image['id']?>'>
												<input type='submit' name='updatepic' value='Update'>
											</form>
										</td>
										<td>
											<form action='<?= htmlspecialchars($_SERVER['REQUEST_URI'])?>' method='post'>
												<input type='hidden' name='pic' value='<?= $path?>'>
												<input type='hidden' name='pid' value='<?= $image['id']?>'>
												<input type='submit' name='delPic' value='Delete'>
											</form>
										</td>
									</tr>
						<?php   }
								echo '</table>';
							} else {echo 'There are no photos for this vehicle.';}
						?>
					</center></div>
				</div>
			</div>
		</div>
	</div>
	<script src='../scripts/admin.js'></script>
	<script src='../scripts/edit.js'></script>
	<script>
		var ownersArray = <?= json_encode($oRows); ?>;
		var thissite = 'http://<?= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];?>';
		document.getElementById('defaultTab').click();
	</script>
</body>
</html>
