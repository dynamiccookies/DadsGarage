<?php
	session_start();
	$site = "forsale";
	include($_SERVER['DOCUMENT_ROOT']."/".$site."/files/header.php");
	$success = 'noscreen ';
	$logout = "secure.php?logout=1&index=".$site;
	try {
		$db = new PDO('mysql:dbname=***REMOVED***;host=***REMOVED******REMOVED***', '***REMOVED***', '***REMOVED***');
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
	if ($_GET['id']) {
		$where = "WHERE ID=".$_GET['id'];
		$pwhere = "WHERE vehicle=".$_GET['id'];
		$ewhere = "WHERE ID=".$_SESSION['eid'];
	} else {
		$where = '';
		$pwhere = '';
		$eid = '';
	}

	//Users Table
	$selectUsers = $db->prepare("SELECT * FROM users WHERE username=:name");
	//$insert = $db->prepare("INSERT INTO users (username,hash) VALUES (:user,:hash)");
	$updateUsers = $db->prepare("UPDATE users SET hash = :pass WHERE username = :name");
	
	//Vehicles - Prepare query to insert year, make, model, & trim as new record into database
	$insert = $db->prepare("INSERT INTO vehicles (year,make,model,trim) VALUES (:year,:make,:model,:trim)");
	
	//Vehicles - Prepare query to update all fields (except purchprice and purchdate) where ID=$_GET['ID']
	$update = $db->prepare("UPDATE vehicles SET vin=:vin, year=:year, make=:make, model=:model, trim=:trim, miles=:miles, owner=:owner, askprice=:askprice, intnotes=:intnotes, pubnotes=:pubnotes, status=:status, insured=:insured, payment=:payment, paynotes=:paynotes ".$where);
	
	//Vehicles - Prepare query to select (if $_GET[;ID;] exists, return all info for only that vehicle; else return all info for all vehicles) [may want to break into two 
	//select statements for efficiency]
	$select = $db->prepare("SELECT * FROM vehicles ".$where." ORDER BY year ASC");
	$select->execute();																//Execute select query
	$rows = $select->fetchAll(PDO::FETCH_ASSOC);									//Fill array with select query results

	//Owners Table
	$oFields = array('name', 'email', 'phone');														//Used for Insert/Update
	$oSelect = $db->prepare("SELECT * FROM owners");												//Create query to select all owners
	$oSelect1 = $db->prepare("SELECT * FROM owners WHERE id=:oid");
	$oSelect->execute();																			//Execute query
	$oRows = $oSelect->fetchAll(PDO::FETCH_ASSOC);													//Fill array with results

	if (($_GET['ophone'] || $_GET['oemail']) && isset($_GET['id'])) {
		$oUpdate = $db->prepare("UPDATE owners SET email='".$_GET['oemail']."',phone='".$_GET['ophone']."' WHERE id=".$rows[0]['owner']);		//Create query to update owners
		$oUpdate->execute();																			//Execute query
		echo "<meta http-equiv=refresh content=\"0; URL=http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'].'?id='.$_GET['id'].'">';
	}
	
	//Photos Table
	$pFields = array('vehicle', 'filename', 'order');												//Used for Insert/Update
	$pinsert = $db->prepare("INSERT INTO photos (vehicle,filename) VALUES (:vehicle,:filename)");	//Create query to insert photo
	$pUpdate = $db->prepare("UPDATE photos SET filename = :filename WHERE id = :pid");
	$pDelete = $db->prepare("DELETE FROM photos WHERE id=:pid");									//Create query to delete photo
	$pSelect1 = $db->prepare("SELECT * FROM photos WHERE vehicle=:vid ORDER BY filename");			//Select first photo
	$pSelect = $db->prepare("SELECT * FROM photos ".$pwhere." ORDER BY filename ASC");				//Create query to select photos for vehicle ID
	if ($_GET['id']) {$pSelect->execute();																			//Execute query
	$pRows = $pSelect->fetchAll(PDO::FETCH_ASSOC);}													//Fill array with results
	
	//Files Table
	$finsert = $db->prepare("INSERT INTO files (vehicle,filename) VALUES (:vehicle,:filename)");	//Create query to insert photo
	$fUpdate = $db->prepare("UPDATE files SET filename = :filename WHERE id = :fid");
	$fDelete = $db->prepare("DELETE FROM files WHERE id=:fid");										//Create query to delete photo
	$fSelect = $db->prepare("SELECT * FROM files ".$pwhere." ORDER BY filename ASC");				//Create query to select photos for vehicle ID
	if ($_GET['id']) {$fSelect->execute();																			//Execute query
	$fRows = $fSelect->fetchAll(PDO::FETCH_ASSOC);}													//Fill array with results
	
	//Expenses Table
	$eFields = array('vehicle,date,description,cost');
	$eInsert = $db->prepare("INSERT INTO expenses (vehicle,date,description,cost) VALUES (:vehicle,:date,:desc,:cost)");					//Create query to insert exp
	$eUpdate = $db->prepare("UPDATE expenses SET date = :date, description = :desc, cost = :cost WHERE id = :eid");
	$eDelete = $db->prepare("DELETE FROM expenses WHERE id=:eid");									//Create query to delete photo
	$eSelect = $db->prepare("SELECT * FROM expenses ".$pwhere." ORDER BY date ASC");				//Create query to select photos for vehicle ID
	if ($_GET['id']) {$eSelect->execute();															//Execute query
	$eRows = $eSelect->fetchAll(PDO::FETCH_ASSOC);}													//Fill array with results
	$eTots = $db->prepare("SELECT SUM(cost) as 'Total' FROM expenses ".$pwhere);
	$eTots->execute();
	$eTotal = $eTots->fetchAll(PDO::FETCH_ASSOC);
?>