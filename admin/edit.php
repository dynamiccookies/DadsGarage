<?php
	session_start();
	$site = "forsale";
	include($_SERVER['DOCUMENT_ROOT']."/".$site."/admin/secure.php");
	$id = $_GET['id']; 
	$home = "http://".$_SERVER['SERVER_NAME']."/".$site;
	if(!$_SESSION['isadmin']) {die("<meta http-equiv=refresh content=\"0; URL=".$home."/portal.php\">");}
	$reload = "<meta http-equiv=refresh content=\"0; URL=".$_SERVER['REQUEST_URI']."\">";
	$reloadPic = "<meta http-equiv=refresh content=\"0; URL=".$_SERVER['REQUEST_URI']."#pscroll\">"; //Need to test
	$vehicle = ($rows[0]["year"]==0000?'':$rows[0]["year"])." ".$rows[0]['make']." ".$rows[0]['model']." ".$rows[0]['trim'];

	if(isset($_POST['SubmitAll'])) {													//Submit data for top half of page, including notes sections
		$update->bindParam(':vin',$_POST['vin']); 
		$update->bindParam(':year',$_POST['year']); 
		$update->bindParam(':make',$_POST['make']); 
		$update->bindParam(':model',$_POST['model']); 
		$update->bindParam(':trim',$_POST['trim']); 
		$update->bindParam(':miles',str_replace(',','',$_POST['miles'])); 
		$update->bindParam(':owner',$_POST['owner']); 
		$update->bindParam(':askprice',str_replace(str_split('$,'),'',$_POST['askprice'])); 
		$update->bindParam(':intnotes',$_POST['intnotes']); 
		$update->bindParam(':pubnotes',$_POST['pubnotes']); 
		$update->bindParam(':status',$_POST['status']); 
		$update->bindParam(':insured',($_POST['insured']?$_POST['insured']=1:$_POST['insured']=0));
		$update->bindParam(':payment',$_POST['payment']);
		$update->bindParam(':paynotes',$_POST['soldnotes']);
		$update->execute();
		echo $reload;
	}
	if(isset($_POST['delPic'])) {
		unlink($_POST["pic"]);						//Delete photo
		$pDelete->bindParam(':pid',$_POST["pid"]);	//Delete database entry
		$pDelete->execute();
//		echo $reloadPic;
		echo "<script> window.location.replace('".$_SERVER['REQUEST_URI']."#".$_POST["oldpic"]."'); </script>";
	}
	if(isset($_POST['updatepic'])) {				//Update photo name
		rename($_POST["fullpic"],str_replace($_POST["oldpic"],$_POST["picname"],$_POST["fullpic"]));

		ini_set('memory_limit', '128M');
		$fn = $_POST["fullpic"];
		$size = getimagesize($fn);
		$ratio = $size[0]/$size[1]; // width/height
		if($size[1]>500){
			if( $ratio > 1) {
				$height = 500;
				$width = 500*$ratio;
			}
			else {
				$width = 500*$ratio;
				$height = 500;
			}
			$src = imagecreatefromstring(file_get_contents($fn));
			$dst = imagecreatetruecolor($width,$height);
			try {
				imagecopyresampled($dst,$src,0,0,0,0,$width,$height,$size[0],$size[1]);
				imagedestroy($src);
				imagepng($dst,$_POST["fullpic"]); // adjust format as needed
				imagedestroy($dst);			
			} catch (Exception $e) {
				echo 'An error occurred. Please take note of the following line(s) and click the link below.<br>';
				echo 'Caught exception: ',  $e->getMessage(), "\n";
				echo "<p><a href='http://".$_SERVER['SERVER_NAME']."/".$site."/admin'>http://".$_SERVER['SERVER_NAME']."/".$site."/admin</a></p>";
			}
		}
		$pUpdate->bindParam(':pid',$_POST["pid"]);
		$pUpdate->bindParam(':filename',$_POST["picname"]);
		$pUpdate->execute();
		echo "<script> window.location.replace('".$_SERVER['REQUEST_URI']."#".$_POST["oldpic"]."'); </script>";
	}
	if(isset($_POST['delFile'])) {
		unlink($_POST["file"]);						//Delete file
		$fDelete->bindParam(':fid',$_POST["fid"]);	//Delete database entry
		$fDelete->execute();
		echo $reloadPic;
	}
	if(isset($_POST['updateFile'])) {							//Update file name
		rename($_POST["fullFile"],str_replace($_POST["oldFile"],$_POST["filename"],$_POST["fullFile"]));
		$fUpdate->bindParam(':fid',$_POST["fid"]);
		$fUpdate->bindParam(':filename',$_POST["filename"]);
		$fUpdate->execute();
		echo $reloadPic;
	}
	if(isset($_POST['eupdate'])) {								//Expense update
		$eUpdate->bindParam(':eid',$_POST["eid"]);
		$date = date('Y/m/d', strtotime(str_replace('-', '/',$_POST["date"])));
		$eUpdate->bindParam(':date',$date);
		$eUpdate->bindParam(':desc',$_POST["desc"]);
		$eUpdate->bindParam(':cost',str_replace('$','',$_POST["cost"]));
		$eUpdate->execute();
		echo $reloadPic;
	}
	if(isset($_POST['einsert'])) {								//Expense insert
		$eInsert->bindParam(':vehicle',$_POST["vehicle"]);
		$date = date('Y/m/d', strtotime(str_replace('-', '/',$_POST["date"])));
		$eInsert->bindParam(':date',$date);
		$eInsert->bindParam(':desc',$_POST["desc"]);
		$eInsert->bindParam(':cost',str_replace('$','',$_POST["cost"]));
		$eInsert->execute();
		echo $reloadPic;
	}
?>
<body onload="updateOwner()" class='bg'>
	<script src="http://code.jquery.com/jquery-3.1.0.min.js"></script>
	<script language="JavaScript" type="text/javascript">
		var ownersArray = <?php echo json_encode($oRows); ?>;
		var thissite = 'http://<?php echo $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];?>';
		$(document).ready(function () {
			$('#oEdit').click(function (e) {
				e.preventDefault();
				var dad = $(this).parent().parent().parent();
				dad.find('.show').hide();
				dad.find('.noscreen').show();
				document.getElementById("oPhone").value = document.getElementById("phonenum").innerHTML;
				document.getElementById("oEmail").value = document.getElementById("emailadd").innerHTML;
				document.getElementById("SubmitAll").disabled = true;
				document.getElementById("ownerdd").disabled = true;
			});
			$('#oSave').click(function (e) {
				e.preventDefault();
				var e = document.getElementById("ownerdd");
				var owner = e.options[e.selectedIndex].value;
				var dad = $(this).parent().parent().parent();
				dad.find('.show').show();
				dad.find('.noscreen').hide();
				document.getElementById("phonenum").innerHTML = valPhone(document.getElementById("oPhone").value);
				document.getElementById("emailadd").innerHTML = document.getElementById("oEmail").value;
				document.getElementById("SubmitAll").disabled = false;
				document.getElementById("ownerdd").disabled = false;
				if (document.getElementById("phonenum").innerHTML != ownersArray[owner-1]['phone'] || document.getElementById("emailadd").innerHTML != ownersArray[owner-1]['email']) {
					window.location.href = thissite + '&ophone=' + valPhone(document.getElementById("phonenum").innerHTML) + '&oemail=' + document.getElementById("emailadd").innerHTML;
				}
			});
			$('.fedit').click(function (e) {
				e.preventDefault();
				var dad = $(this).parent();
				dad.find('.show').hide();
				dad.parent().parent().parent().find('.hide').hide();
				dad.find('.noscreen').show();
			});
		});
		function oSubmit() {
			var dad = $(this).parent().parent();
			dad.find('.show').show();
			dad.find('.noscreen').hide();
			document.getElementById("phonenum").innerHTML = document.getElementById("oPhone").value;
			document.getElementById("emailadd").innerHTML = document.getElementById("oEmail").value;
			document.getElementByName('SubmitAll').disabled = false;
		}
		
		function updateOwner() {
			var e = document.getElementById("ownerdd");
			var owner = e.options[e.selectedIndex].value;
			if (owner !=0) {
				document.getElementById("phonenum").innerHTML = ownersArray[owner-1]['phone'];
				document.getElementById("emailadd").innerHTML = ownersArray[owner-1]['email'];
				document.getElementById("oPhone").value = ownersArray[owner-1]['phone'];
				document.getElementById("oEmail").value = ownersArray[owner-1]['email'];
			} else {
				document.getElementById("phonenum").innerHTML = '';
				document.getElementById("emailadd").innerHTML = '';
				document.getElementById("oPhone").value = '';
				document.getElementById("oEmail").value = '';
			}
			statusChange();
		}
		function statusChange() {
			var txt = '';
			var e = document.getElementById("status");
			var stat = e.options[e.selectedIndex].value;
			switch(stat) {
				case 'Draft':
					txt = 'Vehicle is in DRAFT mode.';
					break;
				case 'Sold':
					txt = 'Vehicle has been SOLD.';
					break;
				case 'Delete':
					txt = 'Vehicle has been marked for DELETION.';
					break;
				default:
					txt = '';
			}
			document.getElementById("notice").innerHTML = txt;
			if (stat == 'Sold') {
				document.getElementById("payment").className = "";
				document.getElementById("lblinsured").className = "noscreen";
				document.getElementById("insured").className = "noscreen";
				document.getElementById("buyer").className = "";
				document.getElementById("fname").className = "";
				document.getElementById("lname").className = "";
				document.getElementById("lblsaledate").className = "";
				document.getElementById("saledate").className = "";
/* 				var a = document.getElementById("payment");
				var pay = a.options[a.selectedIndex].value;
				if (pay != "Cash") {
					document.getElementById("soldnotes").className = "";
					document.getElementById("lblsoldnotes").className = "";
				}
 */			}else{
				document.getElementById("lblsaledate").className = "noscreen";
				document.getElementById("saledate").className = "noscreen";
				document.getElementById("buyer").className = "noscreen";
				document.getElementById("fname").className = "noscreen";
				document.getElementById("lname").className = "noscreen";
				document.getElementById("lblinsured").className = "";
				document.getElementById("insured").className = "";
				document.getElementById("payment").className = "noscreen";
/* 				document.getElementById("soldnotes").className = "noscreen";
				document.getElementById("lblsoldnotes").className = "noscreen";
 */			}

		}
/* 		function paymentType() {
			var e = document.getElementById("payment");
			var stat = e.options[e.selectedIndex].value;
			if (stat != "Cash") {
				document.getElementById("soldnotes").className = "";
				document.getElementById("lblsoldnotes").className = "";
			} else {
				document.getElementById("soldnotes").className = "noscreen";
				document.getElementById("lblsoldnotes").className = "noscreen";
			}
		}
 */		function loading1() {
			document.getElementById("loading1").style.display = 'block';
		}
		function loading2() {
			document.getElementById("loading2").style.display = 'block';
		}
		function valPhone(num) {
			var arr = num.match(/\d+/g);
			var str = '';
			for (var i = 0, len = arr.length; i < len; i++) {str += arr[i];}
			str = substr_replace(str,'(',0,0);
			str = substr_replace(str,') ',4,0);
			str = substr_replace(str,'-',9,0);
			if (str.length != 14) {str = ''}
			return str;
		}
		
		function substr_replace(str, replace, start, length) {		//php substr_replace in js
		  // discuss at: http://phpjs.org/functions/substr_replace/ // original by: Brett Zamir (http://brett-zamir.me)
		  if (start < 0) {start = start + str.length;}
		  length = length !== undefined ? length : str.length;
		  if (length < 0) {length = length + str.length - start;}
		  return str.slice(0, start) + replace.substr(0, length) + replace.slice(length) + str.slice(start + length);
		}
	</script>
	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" style="padding-bottom:20px;">
		<div class='center huge bold'>
			<div class='m-lrauto'>
				You're editing <a href="../vehicle.php?id=<?php echo $rows[0]['id'];?>"><?php echo $vehicle;?></a>
			</div>
		</div>
		<br>
		<table class='bgblue bord5 p15 b-rad15 clear m-lrauto m-bottom25 bold'>
			<tr>																	<!-- Row 1 -->
				<td>VIN: <?php
						echo (strlen($rows[0]['vin'])==17? "(<a href='http://www.vindecoder.net/?vin=".$rows[0]['vin']."&submit=Decode' target='_blank'>Decode</a>)":"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
					?>
				</td>
				<td><input style="width:165px;" type="textbox" tabindex=1 name="vin" value="<?php echo strtoupper($rows[0]['vin'])?>"></td>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>Year:</td>
				<td><input style="width:90px;" type="textbox" tabindex=4 name="year" value="<?php echo ($rows[0]["year"]==0000?"":$rows[0]["year"]);?>"></td>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>Owner:</td>
				<td colspan=2>
					<select id="ownerdd" tabindex=9 name="owner" onchange="updateOwner()">
						<option value=0>Select Owner...</option>
						<?php
							foreach ($oRows as $ownerdd) {
								($ownerdd['id'] == $rows[0]['owner']?$selected=' selected':$selected='');
								echo "<option value=".$ownerdd['id'].$selected.">".$ownerdd['name']."</option>";
							}
						?>
					</select>
					<?php
						if (!empty($rows[0]['owner'])) {
							echo "<a id='oEdit' class='show' href=''>Edit</a><a id='oSave' class='noscreen' href=''>Save</a>";
						}
					?>
				</td>
				<td rowspan=5><?php echo ($pRows[0]['filename']?"<img src='../vehicles/".$id."/".$pRows[0]['filename']."' width=200px />":'');?></td>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td><center>Links:</center></td>
			</tr>
			<tr>																	<!-- Row 2 -->
				<td nowrap><?php echo ($rows[0]['status']=='Sold'?'Sale':'Asking');?> Price:</td>
				<td><input style="width:165px;" type="textbox" tabindex=2 name="askprice" value="<?php 
					if (strpos($rows[0]['askprice'], '$') === FALSE && trim($rows[0]['askprice']) !== "") {
						echo '$'.number_format($rows[0]['askprice']);
					}
																?>"></td>
				<td>&nbsp;</td>
				<td>Make:</td>
				<td><input style="width:90px;" type="textbox" name="make" tabindex=5 value="<?php echo $rows[0]['make']?>"></td>
				<td>&nbsp;</td>
				<td>Phone:</td>
				<td><span id='phonenum' class='show'></span><input id='oPhone' type="text" class="noscreen" /></td>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>&nbsp;</td>
				<td nowrap><center><a href='<?php echo $home;?>'>For Sale</a></center></td>
			</tr>
			<tr>																	<!-- Row 3 -->
				<td nowrap>Status:</td>
				<td nowrap>
					<select name="status" id="status" onchange="statusChange()" style="min-width:84px;" tabindex=3>
					<?php
						$statuses=array('Draft','For Sale','Sold','Delete');
						foreach($statuses as $stat){
							($stat == $rows[0]['status']?$selected=' selected':$selected='');
							echo "<option value='".$stat."'".$selected.">".$stat."</option>\n";
						}
					?>
					</select>
					<select name="payment" id="payment" onchange="paymentType()" class="<?php echo ($rows[0]['status']!='Sold'?noscreen:'');?>">
						<?php
							$statuses=array('Cash','Payments','Trade');
							foreach($statuses as $stat){
								($stat==$rows[0]['payment']?$selected=' selected':$selected='');
								echo "<option value='".$stat."'".$selected.">".$stat."</option>\n";
							}
						?>
					</select>
				</td>
				<td>&nbsp;</td>
				<td>Model:</td>
				<td><input style="width:90px;" type="textbox" tabindex=6 name="model" value="<?php echo $rows[0]['model']?>"></td>
				<td>&nbsp;</td>
				<td>Email:</td>
				<td><span id='emailadd' class='show'></span><input id='oEmail' type="text" class="noscreen" /></td>
				<td colspan=3>&nbsp;</td>
				<td nowrap><center><a href='<?php echo $home;?>/admin'>Admin Home</a></center></td>
			</tr>
			<tr>																	<!-- Row 4 -->
				<td>
					<span id='buyer' class="<?php echo ($rows[0]['status']!='Sold'?noscreen:'');?>">Buyer:</span>
					<span id="lblinsured" class="block" name="lblinsured"><a href="mailto:info@insurancecenterofbuffalo.com?subject=Auto%20Insurance&body=<?php echo $vehicle;?>%20-%20VIN:%20<?php echo strtoupper($rows[0]['vin'])?>" title="Send Email to Insurance Center of Buffalo">Insured?</a>:</span>
				</td>
				<td nowrap>
					<input type="textbox" style="width:78px;" class="<?php echo ($rows[0]['status']!='Sold'?noscreen:'');?>" id='fname' name='fname' placeholder="First Name" value="">
					<input type="textbox" style="width:78px;" class="<?php echo ($rows[0]['status']!='Sold'?noscreen:'');?>" id='lname' name='lname' placeholder="Last Name" value="">
					<input type="checkbox" class="block" id="insured" name="insured" value='insured'<?php echo ($rows[0]['insured'] == 1?" checked='checked'":"")?>>
				</td>
				<td>&nbsp;</td>
				<td>Trim:</td>
				<td><input style="width:90px;" type="textbox" tabindex=7 name="trim" value="<?php echo $rows[0]['trim']?>"></td>
				<td colspan=4><center><font class='red' id='notice'>&nbsp;</font></center></td>
				<td colspan=3>&nbsp;</td>
			</tr>
			<tr>																	<!-- Row 5 -->
				<!--<td><span id='lblsoldnotes' class="noscreen">Sold Notes:</span></td>
				<td><input type="textbox" style="width:150px;" class="noscreen" id='soldnotes' name='soldnotes' value="<?php echo $rows[0]['paynotes'];?>"></td>-->
				<td><span id="lblsaledate" class="<?php echo ($rows[0]['status']!='Sold'?noscreen:'');?>">Sale Date:</span></td>
				<td><input type="date" id="saledate" class="<?php echo ($rows[0]['status']!='Sold'?noscreen:'');?>" style="width:167px;" name="saledate"></td>
				<td>&nbsp;</td>
				<td>Miles:</td>
				<td><input style="width:90px;" type="textbox" name="miles" tabindex=8 value="<?php echo number_format($rows[0]['miles'])?>"></td>
				<td>&nbsp;</td>
				<td colspan=2><center><input type="Submit" tabindex=12 name="SubmitAll" id="SubmitAll" value="Submit All Changes"></center></td>
				<td colspan=3>&nbsp;</td>
				<td><center><a href='<?php echo $logout;?>'>Logout</a></center></td>
			</tr>
		</table>
		
		<div class='center clear m-lrauto bold' style="width:90%;margin-top:25px;">
			<div class='m-bottom25 bgblue tb-p15 b-rad15 fleft bord5' style="width:46%;">
				Description:<br>
				<textarea class="boxsizingBorder" tabindex=10 style="padding:10px;margin-top:5px;width:90%;height:500px" name="pubnotes"><?php echo $rows[0]['pubnotes'];?></textarea>
			</div>
			<div class='fright' style="width:46%;">
				<div class='m-bottom25 bgblue tb-p15 b-rad15 bord5'>
					Internal Only Notes:<br>
					<textarea class="boxsizingBorder" tabindex=11 style="padding:10px;margin-top:5px;width:90%;height:175px" name="intnotes"><?php echo $rows[0]['intnotes']?></textarea>
				</div>
				<div class='m-top25 bgblue p15 b-rad15 bord5'>
					Basic HTML to Use in Description
					<table class='htmlTable'><tr><td class='b-right1'>
					&lt;div class='noprint'&gt; ... &lt;/div&gt;<hr />
					&lt;h1&gt; ... &lt;/h1&gt;<hr />
					&lt;p&gt; ... &lt;/p&gt;<hr />
					&lt;b&gt; ... &lt;/b&gt;<hr />
					&lt;i&gt; ... &lt;/i&gt;<hr />
					&lt;u&gt; ... &lt;/u&gt;<hr />
					&lt;font color="red"&gt; ... &lt;/font&gt;
					</td><td>
					Add to text area to hide when printing<hr />
					Heading (1 - 6, big to small)<hr />
					Paragraph of Text<hr />
					<b>Bold Text</b><hr />
					<i>Italic Text</i><hr />
					<u>Underline Text</u><hr />
					<font color="red">Color Text</font>
					</td></tr></table>
				</div>
			</div>
		</div>
		<hr width="80%" size="5" color="red" />
	</form>
	<div class='center clear m-lrauto bold' style="width:90%;">
		<div class='fleft' style="width:44%;min-width:450px;">
			<div id='expenses' class='bgblue p15 b-rad15 bord5' style="margin-bottom:50px;">
				Expenses:<br><center><table><tr><td width='80px'>Date</td><td width='205px'>Description</td><td>Cost</td></tr>
				<?php 
					if (!empty($eRows)) {
						foreach ($eRows as $exp) {
							$date = ($exp['date'] != '0000-00-00' && $exp['date'] != '1969-12-31'?date('m/d/Y',strtotime($exp['date'])):"");
							echo "<tr><td nowrap colspan=3><form action='".htmlspecialchars($_SERVER['PHP_SELF'])."' method='post'><input type='hidden' name='eid' value='".$exp['id']."'><input style='width:75px;' type='textbox' name='date' value='".$date."'>&nbsp;<input style='width:200px;' type='textbox' name='desc' value=\"".$exp['description']."\">&nbsp;<input style='width:50px;' type='textbox' name='cost' value='".$exp['cost']."'>&nbsp;<input type='submit' name='eupdate' value='Update'></form></td></tr>";
						}
						echo "<tr><td nowrap colspan=2 align=right>Total: </td><td class='red'>$".$eTotal[0]['Total']."</td></tr>";
					}
				?>
				<tr><td nowrap colspan=3><form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' method='post'><input type='hidden' name='vehicle' value='<?php echo $_GET['id']?>'><input style='width:75px;' type='textbox' name='date' value=''>&nbsp;<input style='width:200px;' type='textbox' name='desc' value=''>&nbsp;<input style='width:50px;' type='textbox' name='cost' value=''>&nbsp;<input type='submit' name='einsert' value='Insert'></form></td></tr>
				</table></center>
			</div>
			<div class='bgblue p15 b-rad15 bord5' style="margin-bottom:50px;">
				Files:<br>
				<div>
					<form action="upload_file.php?id=<?php echo $rows[0]['id'];?>" method="post" enctype="multipart/form-data">
					  <input name="files[]" type="file" multiple /><input type="submit" onclick='loading1()' value="Send files" />
					</form>
					<br><center><img src='../files/loading_anim.gif' class='noscreen' id='loading1' width=100px></center>
				</div>
				<hr class='m-top25 m-bottom25 center red' style="width:80%;" />
				<div><center>
					<?php 
						if ($fRows) {
							echo "<table class='photos'>";
							foreach ($fRows as $file) {
								$path="../vehicles/".$id."/".$file['filename'];
								echo "\n<tr><td nowrap align='right'><form action='".htmlspecialchars($_SERVER["PHP_SELF"])."' method='post'><a class='show' href='".$path."' target='_blank'>".$file['filename']."</a><input type='hidden' name='fullFile' value='".$path."'><input type='hidden' name='oldFile' value='".$file['filename']."'><input class='noscreen' type='textbox' style='width:150px' name='filename' value='".$file['filename']."''><input type='hidden' name='fid' value='".$file['id']."'>&nbsp;&nbsp;&nbsp;&nbsp;<a href='#' class='show fedit hide'>Edit</a>&nbsp;&nbsp;&nbsp;&nbsp;<input class='noscreen' type='submit' name='updateFile' value='Update'></form></td>\n<td><form action='".htmlspecialchars($_SERVER["PHP_SELF"])."' method='post'><input type='hidden' name='file' value='".$path."'><input type='hidden' name='fid' value='".$file['id']."'><input type='submit' name='delFile' value='Delete'></form></td></tr>\n";
								
							}
							echo "</table>";
						} else {echo ("\nThere are no files for this vehicle.");}
					?>
				</center></div>
			</div>
		</div>
		<div class='bgblue p15 b-rad15 fright bord5' style="margin-bottom:50px;width:43.5%;min-width:500px;">
			Photos:<br>
			<div>
				<form action="upload_file.php?id=<?php echo $rows[0]['id'];?>" method="post" enctype="multipart/form-data">
				  <input name="photos[]" type="file" multiple accept="image/*" /><input type="submit" onclick='loading2()' value="Send files" />
				</form>
				<br><center><img src='../files/loading_anim.gif' class='noscreen' id='loading2' width=100px></center>
			</div>
			<hr class='m-top25 m-bottom25 center red' style="width:80%;" />
			<div><center>
				<?php 
					if ($pRows) {
						echo "<table class='photos'>";
						foreach ($pRows as $image) {
							$path="../vehicles/".$id."/".$image['filename'];
							echo "\n<tr><td><a href='".$path."' target='_blank' id='".$image['filename']."'><img src='".$path."' width=200px /></a></td>\n<td nowrap><form action='".htmlspecialchars($_SERVER["PHP_SELF"])."' method='post'><input type='hidden' name='fullpic' value='".$path."'><input type='hidden' name='oldpic' value='".$image['filename']."'><input type='textbox' style='width:150px' name='picname' value='".$image['filename']."''><input type='hidden' name='pid' value='".$image['id']."'><input type='submit' name='updatepic' value='Update'></form></td>\n<td><form action='".htmlspecialchars($_SERVER["PHP_SELF"])."' method='post'><input type='hidden' name='pic' value='".$path."'><input type='hidden' name='pid' value='".$image['id']."'><input type='submit' name='delPic' value='Delete'></form></td></tr>\n";
						}
						echo "</table>";
					} else {echo ("\nThere are no photos for this vehicle.");}
				?>
			</center></div>
		</div>
	</div>
</body>
</html>