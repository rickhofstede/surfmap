<?php
/******************************************************
 # getgeocoderdata.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/

require_once("../../config.php");
header("content-type: application/json");

$result = array();

if (isset($_POST['request'])) {
    $requests = $_POST['request'];
} else {
    $result['status'] = 1;
    echo json_encode($result);
    die();
}

global $USE_GEOCODER_DB;

if ($USE_GEOCODER_DB) {
    try {
    	if (in_array("sqlite", PDO::getAvailableDrivers())) {
    		$Geocoder_DB = new PDO("sqlite:../../$GEOCODER_DB_SQLITE3");
    	}
    } catch(PDOException $e) {}
}

$result['geocoder_data'] = array();

foreach ($requests as $request) {
    // Length of $split_request determines level (1 -> country, 2 -> region, 3 -> city)
    $split_request = explode(";", $request);
    $place = $split_request[count($split_request) - 1]; // Last element in array is actual place name
    
    if ($place === "-" || stripos($place, "NKNOWN")) {
        array_push($result['geocoder_data'], array($request => array('lat' => 0, 'lng' => 0)));
    } else if ($USE_GEOCODER_DB) {
		$queryResult = $Geocoder_DB->query("SELECT latitude, longitude FROM geocoder WHERE location = ".$Geocoder_DB->quote($request));
		$row = $queryResult->fetch(PDO::FETCH_ASSOC);
		unset($queryResult);
        
		if ($row) { // Country name was found in our GeoCoder database
            array_push($result['geocoder_data'], array($request => array('lat' => $row['latitude'], 'lng' => $row['longitude'])));
		} else {
            array_push($result['geocoder_data'], array($request => array('lat' => -1, 'lng' => -1)));
		}
    } else {
        array_push($result['geocoder_data'], array($request => array('lat' => -1, 'lng' => -1)));
    }
}
			
// Check geocoder request history for current day
if ($USE_GEOCODER_DB) {
	$queryResult = $Geocoder_DB->query("SELECT * FROM history WHERE date = ".$Geocoder_DB->quote(date("Y-m-d")));
	$row = $queryResult->fetch(PDO::FETCH_ASSOC);
	unset($queryResult);
				
	if ($row === false) { // No entry in DB
        // $sessionData->geocoderRequestsSuccess = 0;
        // $sessionData->geocoderRequestsError = 0;
        // $sessionData->geocoderRequestsSkip = 0;
        // $sessionData->geocoderRequestsBlock = 0;
	} else {
        // $sessionData->geocoderRequestsSuccess = $row['requestsSuccess'];
        // $sessionData->geocoderRequestsError = $row['requestsError'];
        // $sessionData->geocoderRequestsSkip = $row['requestsSkip'];
        // $sessionData->geocoderRequestsBlock = $row['requestsBlock'];
	}
}

$result['status'] = 0;
echo json_encode($result);
die();

?>