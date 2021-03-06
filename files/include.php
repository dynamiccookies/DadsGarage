<?php
	session_start();
	ini_set('display_errors', $debug);
	register_shutdown_function(function(){
		$last_error = error_get_last();
		if (!empty($last_error) && $last_error['type'] & (E_ERROR | E_COMPILE_ERROR | E_PARSE | E_CORE_ERROR | E_USER_ERROR)){
			$path = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
			$path = substr($path, strrpos($path, '/')+1);
			if ($path=='admin') {$path='../files/settings.php';} elseif ($path=='files') {$path='settings.php';} else {$path='files/settings.php';}
			echo "<meta http-equiv=refresh content=\"5; URL=".$path."\">";
			echo "<p style='font-weight:bold;font-size:24px;color:red;'>Settings are missing!<br/>Redirecting there now.</p>";
			exit(1);
		}
	});
	//Set path to 'files'
	$files=substr(getcwd(), strrpos(getcwd(), '/') + 1);
	if ($files=='admin') {$files='../files/';} elseif ($files=='files') {$files='';} else {$files='files/';}

	//Set path to 'admin'
	$admin=substr(getcwd(), strrpos(getcwd(), '/') + 1);
	if ($admin=='files') {$admin='../admin/';} elseif ($admin=='admin') {$admin='';} else {$admin='admin/';}
	
	define('included', TRUE);

	require_once($files.'header.php');
	require($files.'conn.php');

	$success = 'noscreen ';
	$logout = $admin.'secure.php?logout=1';

	//This is not needed. Use ternary operators on each statement (completed) and remove this if/else.
	//if ($_GET['id']) {
		//$where = "WHERE ID=".$_GET['id'];
		//$pwhere = "WHERE vehicle=".$_GET['id'];
		//$ewhere = "WHERE ID=".$_SESSION['eid'];
	//} else {
		//$where = '';
		//$pwhere = '';
		//$eid = '';
	//}

	//Users Table
	$selectUsers = $db->prepare("SELECT * FROM users WHERE username=:name");
	$selectAllUsers = $db->prepare("SELECT * FROM users ORDER BY fname ASC");
	$insertUsers = $db->prepare("INSERT INTO users (username,hash,fname,lname,isadmin) VALUES (:user,:pass,:fname,:lname,:isadmin)");
	$updateUsers = $db->prepare("UPDATE users SET hash = :pass WHERE username = :name");
	$deleteUser = $db->prepare("DELETE FROM users WHERE id=:id");
	$selectAllUsers->execute();
	$users=$selectAllUsers->fetchAll(PDO::FETCH_ASSOC);

	//Vehicles - Prepare query to insert year, make, model, & trim as new record into database
	$insert = $db->prepare("INSERT INTO vehicles (year,make,model,trim) VALUES (:year,:make,:model,:trim)");
	
	//Vehicles - Prepare query to update all fields (except purchprice and purchdate) where ID=$_GET['ID']
	//$update = $db->prepare("UPDATE vehicles SET vin=:vin, year=:year, make=:make, model=:model, trim=:trim, miles=:miles, owner=:owner, askprice=:askprice, status=:status, insured=:insured, payment=:payment, paynotes=:paynotes ".$where);
	$update = $db->prepare("UPDATE vehicles SET vin=:vin, year=:year, make=:make, model=:model, trim=:trim, miles=:miles, owner=:owner, askprice=:askprice, status=:status, insured=:insured, payment=:payment, paynotes=:paynotes ".($_GET['id']?"WHERE ID=".$_GET['id']:''));
	$updateDesc = $db->prepare("UPDATE vehicles SET pubnotes=:pubnotes WHERE ID=".$_GET['id']);
	$updateInternal = $db->prepare("UPDATE vehicles SET intnotes=:intnotes WHERE ID=".$_GET['id']);
	
	//Vehicles - Prepare query to select (if $_GET[;ID;] exists, return all info for only that vehicle; else return all info for all vehicles) [may want to break into two 
	//select statements for efficiency]
	//$select = $db->prepare("SELECT * FROM vehicles ".$where." ORDER BY year ASC");
	$select = $db->prepare("SELECT * FROM vehicles ".($_GET['id']?"WHERE ID=".$_GET['id']:'')." ORDER BY year ASC");
	$select->execute();																//Execute select query
	$rows = $select->fetchAll(PDO::FETCH_ASSOC);									//Fill array with select query results

	//Owners Table
	$oFields = array('name', 'email', 'phone');														//Used for Insert/Update
	$oInsert = $db->prepare("INSERT INTO owners (name,email,phone) VALUES (:name,:email,:phone)");	//Add owners
	$oSelect = $db->prepare("SELECT * FROM owners");												//Create query to select all owners
	$oSelect1 = $db->prepare("SELECT * FROM owners WHERE id=:oid");
	$deleteOwner = $db->prepare("DELETE FROM owners WHERE id=:id");
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
	//$pSelect = $db->prepare("SELECT * FROM photos ".$pwhere." ORDER BY filename ASC");				//Create query to select photos for vehicle ID
	$pSelect = $db->prepare("SELECT * FROM photos ".($_GET['id']?"WHERE vehicle=".$_GET['id']:'')." ORDER BY filename ASC");	//Create query to select photos for vehicle ID
	if ($_GET['id']) {$pSelect->execute();																			//Execute query
	$pRows = $pSelect->fetchAll(PDO::FETCH_ASSOC);}													//Fill array with results
	
	//Files Table
	$finsert = $db->prepare("INSERT INTO files (vehicle,filename) VALUES (:vehicle,:filename)");	//Create query to insert photo
	$fUpdate = $db->prepare("UPDATE files SET filename = :filename WHERE id = :fid");
	$fDelete = $db->prepare("DELETE FROM files WHERE id=:fid");										//Create query to delete photo
	//$fSelect = $db->prepare("SELECT * FROM files ".$pwhere." ORDER BY filename ASC");				//Create query to select photos for vehicle ID
	$fSelect = $db->prepare("SELECT * FROM files ".($_GET['id']?"WHERE vehicle=".$_GET['id']:'')." ORDER BY filename ASC");				//Create query to select photos for vehicle ID
	if ($_GET['id']) {$fSelect->execute();																			//Execute query
	$fRows = $fSelect->fetchAll(PDO::FETCH_ASSOC);}													//Fill array with results
	
	//Expenses Table
	$eFields = array('vehicle,date,description,cost');
	$eInsert = $db->prepare("INSERT INTO expenses (vehicle,date,description,cost) VALUES (:vehicle,:date,:desc,:cost)");					//Create query to insert exp
	$eUpdate = $db->prepare("UPDATE expenses SET date = :date, description = :desc, cost = :cost WHERE id = :eid");
	$eDelete = $db->prepare("DELETE FROM expenses WHERE id=:eid");									//Create query to delete photo
	//$eSelect = $db->prepare("SELECT * FROM expenses ".$pwhere." ORDER BY date ASC");				//Create query to select photos for vehicle ID
	$eSelect = $db->prepare("SELECT * FROM expenses ".($_GET['id']?"WHERE vehicle=".$_GET['id']:'')." ORDER BY date ASC");				//Create query to select photos for vehicle ID
	if ($_GET['id']) {$eSelect->execute();															//Execute query
	$eRows = $eSelect->fetchAll(PDO::FETCH_ASSOC);}													//Fill array with results
	//$eTots = $db->prepare("SELECT SUM(cost) as 'Total' FROM expenses ".$pwhere);
	$eTots = $db->prepare("SELECT SUM(cost) as 'Total' FROM expenses ".($_GET['id']?"WHERE vehicle=".$_GET['id']:''));
	$eTots->execute();
	$eTotal = $eTots->fetchAll(PDO::FETCH_ASSOC);
	?>

