<?php

    /**
     * Retrieve vehicle details from NHTSA API using a VIN
     * 
     * This function accepts a VIN string which is passed to the 
     * National Highway Traffic Safety Administration's API to be decoded 
     * and return details about the vehicle.
     *
     * Additionally, this function optionally accepts an array parameter or 
     * multiple string parameters to specify which details/keys to return 
     * from the array. If no secondary parameter is given, the entire array 
     * is returned.
     *
     * The function will always include an 'Error' key that contains 0 on 
     * success or an array of error messages on failure.
     *
     * @access public
     * @link https://github.com/dynamiccookies/vin-decoder vin-decoder.php
     * @link https://vpic.nhtsa.dot.gov/api/ NHTSA API
     * @author Chad A Davis <github.com/dynamiccookies>
     * @license http://www.opensource.org/licenses/mit-license.html MIT License
     * @copyright 2021 @author
     * @version 0.2.0 - Please remember to check for the latest version
     * @param string    $vin             Vehicle Identification Number
     * @param mixed  ...$keys (optional) Key(s) from the NHTSA array as one or 
     *                                   many strings, or an array of strings
     *
     * @return object Vehicle
     */

	function decodeVIN($vin, ...$keys) {
		$message = '';
		$search  = '';
		$url     = 'https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVinValues/' . $vin . '?format=json';
		$vehicle = array();

		$ch      = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$results = json_decode(curl_exec($ch), true);
		curl_close($ch);

		if ($results) {
			$message = $results['Message'];
			$search  = $results['SearchCriteria'];
			$results = $results['Results'][0];
			
			if (!$results['ErrorCode'] == '0') {
				$vehicle['Error']    = explode(';', $results['ErrorText']);
				$vehicle['Searched'] = str_replace('VIN(s): ', '', $search);

				return $vehicle;
			} else {$vehicle['Error'] = 0;}

			$results['Make'] = ucwords(strtolower($results['Make']));

			if (!$keys) {
				$results['Error']   = 0;
				$results['Message'] = $message;

				return $results;
			} else {
				foreach ($keys as $key) {$vehicle[$key] = $results[$key];}

				return $vehicle;
			}
		} else {return false;}
	}
?>
