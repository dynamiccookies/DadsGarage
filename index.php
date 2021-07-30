<?php

	if (!isset($_SESSION)) {session_start();}

	$_SESSION['include'] = true;
	require_once 'includes/header.php';

	$_SESSION['include'] = true;
	require_once 'includes/include.php';
	
	// Log date/time and IP data
	if (!file_exists('logs')) {mkdir('logs');}
	file_put_contents('logs/traffic.log', date('Y-m-d H:i:s') . ', ' . $_SERVER['REMOTE_ADDR'] . "\n", FILE_APPEND);

	// Build and write opening body, h1, and div tag HTML to page
	echo "<body id='main' class='darkbg'><h1 style='text-align:center;color:white;'>Vehicles For Sale:</h1>\n<div class='holder'>\n";

	// Loop through each vehicle row - $rows variable pulled from 'include.php' file
	foreach ($rows as $row) {

		// If vehicle's status equals 'For Sale', vehicle will be printed to screen
		if ($row['status'] == 'For Sale') {

			// Get photo data by vehicle ID
			$pSelect1->bindParam(':vid', $row['id']);
			$pSelect1->execute();
			$photo1 = $pSelect1->fetchAll(PDO::FETCH_ASSOC);

			// If an image file exists for the vehicle, build the path - Else default to the 'noimage.jpg' file page
			if (isset($photo1[0]['filename'])) $src = 'files/' . $row['id'] . '/' . $photo1[0]['filename'];
			else $src = 'images/noimage.jpg';

// NEED TO REVIEW - Replacing zeroes isn't necessary if they can't be entered
			// If no year is entered, it default to four zeroes - If that happens, remove them from the name
			$name = str_replace('0000 ', '', $row['year'] . ' ' . $row['make'] . ' ' . $row['model'] . ' ' . $row['trim']);

			// If an asking price exists, build it into a formatted string - Else leave it blank
			if (trim($row['askprice']) != '0') $asking = ' - $' . $row['askprice'];
			else $asking = '';
			
			// Build and write formatted HTML to page for vehicle image, title, and asking price
			echo "<div class='vehicle'>\n<a href='vehicle.php?id=" . $row['id'] . "'>";
			echo "<img src='" . $src . "'></a><br />\n<a href='vehicle.php?id=" . $row['id'] . "'>" . $name . $asking . " </a>\n</div>\n\n";
		}
	}

	// Build and write closing div, body, and html tag HTML to page
	echo '</div>\n</body>\n</html>';
?>
