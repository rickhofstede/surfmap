<?php
	/*******************************
	 # backend.php [SURFmap]
	 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
	 # University of Twente, The Netherlands
	 #
	 # LICENSE TERMS: outlined in BSD-license.html
	 *******************************/

	require_once("config.php");
	require_once("objects.php");
	require_once("connectionhandler.php");
	require_once("loghandler.php");
	require_once("sessionhandler.php");
	require_once("servertransaction.php");
	
	$nfsenConfig = readNfSenConfig();
	
	require_once($nfsenConfig['HTMLDIR']."/conf.php");
	require_once($nfsenConfig['HTMLDIR']."/nfsenutil.php");

	chdir(dirname(__FILE__));

	$arguments = getopt("p:t:s:");
	$profile = (isset($arguments['p'])) ? $arguments['p'] : "";
	$profileType = (isset($arguments['t'])) ? $arguments['t'] : "";
	$allSources = (isset($arguments['s'])) ? $arguments['s'] : "";

	$logHandler = new LogHandler();
	$connectionHandler = new ConnectionHandler($logHandler);
	$sessionData = new SessionData();
	$sessionHandler = new SessionHandler($logHandler, $profile, $profileType, $allSources);

	$sessionData->NetFlowData = $connectionHandler->retrieveDataNfSen();
	$sessionData->geoLocationData = $connectionHandler->retrieveDataGeolocation($sessionData->NetFlowData);
	$sessionData->geoCoderData = $connectionHandler->retrieveDataGeocoderDB($sessionData->geoLocationData);

	$geocodingQueue = array();
	for ($i = 0; $i < sizeof($sessionData->geoCoderData); $i++) {
		$entry = $sessionData->geoLocationData[$i][0]['COUNTRY'];
		if ($sessionData->geoCoderData[$i]->srcCountry[0] === -1 && strpos($entry, "nknown") === false && !in_array($entry, $geocodingQueue)) {
			array_push($geocodingQueue, $entry);
		}
		
		$entry = $sessionData->geoLocationData[$i][1]['COUNTRY'];
		if ($sessionData->geoCoderData[$i]->dstCountry[0] === -1 && strpos($entry, "nknown") === false && !in_array($entry, $geocodingQueue)) {
			array_push($geocodingQueue, $entry);
		}
		
		$entry = $sessionData->geoLocationData[$i][0]['COUNTRY'].", ".$sessionData->geoLocationData[$i][0]['REGION'];
		if ($sessionData->geoCoderData[$i]->srcRegion[0] === -1 && strpos($entry, "nknown") === false && !in_array($entry, $geocodingQueue)) {
			array_push($geocodingQueue, $entry);
		}
		
		$entry = $sessionData->geoLocationData[$i][1]['COUNTRY'].", ".$sessionData->geoLocationData[$i][1]['REGION'];
		if ($sessionData->geoCoderData[$i]->dstRegion[0] === -1 && strpos($entry, "nknown") === false && !in_array($entry, $geocodingQueue)) {
			array_push($geocodingQueue, $entry);
		}
		
		if (strpos($entry, "nknown") === false) {
			$entry = $sessionData->geoLocationData[$i][0]['COUNTRY'].", ".$sessionData->geoLocationData[$i][0]['REGION'].", ".$sessionData->geoLocationData[$i][0]['CITY'];
		} else {
			$entry = $sessionData->geoLocationData[$i][0]['COUNTRY'].", ".$sessionData->geoLocationData[$i][0]['CITY'];
		}
		if ($sessionData->geoCoderData[$i]->srcCity[0] === -1 && strpos($entry, "nknown") === false && !in_array($entry, $geocodingQueue)) {
			array_push($geocodingQueue, $entry);
		}
		
		if (strpos($entry, "nknown") === false) {
			$entry = $sessionData->geoLocationData[$i][1]['COUNTRY'].", ".$sessionData->geoLocationData[$i][1]['REGION'].", ".$sessionData->geoLocationData[$i][1]['CITY'];
		} else {
			$entry = $sessionData->geoLocationData[$i][1]['COUNTRY'].", ".$sessionData->geoLocationData[$i][1]['CITY'];
		}
		if ($sessionData->geoCoderData[$i]->dstCity[0] === -1 && strpos($entry, "nknown") === false && !in_array($entry, $geocodingQueue)) {
			array_push($geocodingQueue, $entry);
		}
	}
	
	if (count($geocodingQueue) > 100) {
		// Geocode 100 places at maximum
		$geocodingQueue = array_slice($geocodingQueue, 0, 100);
	}

	$successfulGeocodingRequests = 0;
	$erroneousGeocodingRequests = 0;
	$skippedGeocodingRequests = 0;
	$blockedGeocodingRequests = 0;

	foreach ($geocodingQueue as $place) {
		if ($sessionData->geocoderRequestsSuccess + $sessionData->geocoderRequestsError + $sessionData->geocoderRequestsSkip + 
				$successfulGeocodingRequests + $erroneousGeocodingRequests + $skippedGeocodingRequests <= 2250) {
			$requestURL = "://maps.google.com/maps/api/geocode/xml?address=" . urlencode(str_replace("'", "\'", $place)) . "&sensor=false";
			if ($FORCE_HTTPS) {
				$requestURL = "https".$requestURL;
			} else {
				$requestURL = "http".$requestURL;
			}

			// Prefer cURL over the 'simplexml_load_file' command, for increased stability
			if (extension_loaded("curl")) {
				$curl_handle = curl_init();
				curl_setopt($curl_handle, CURLOPT_URL, $requestURL);
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 0);
				
				if ($USE_PROXY === 1) {
					curl_setopt($curl_handle, CURLOPT_PROXYTYPE, 'HTTP');
					curl_setopt($curl_handle, CURLOPT_PROXY, $PROXY_IP);
					curl_setopt($curl_handle, CURLOPT_PROXYPORT, $PROXY_PORT);
					
					if ($PROXY_USERNAME_PASSWORD === 1) {
						curl_setopt($curl_handle, CURLOPT_PROXYUSERPWD, $PROXY_USERNAME_PASSWORD);
					}
				}
				
				$result = curl_exec($curl_handle);
				curl_close($curl_handle);
				$xml = simplexml_load_string($result);
			} else {
				$xml = simplexml_load_file($requestURL);
			}

			$status = $xml->status;
			if (isset($xml->result->geometry)) {
				$lat = $xml->result->geometry->location->lat;
			    $lng = $xml->result->geometry->location->lng;
			}

			if ($status == "OK" && isset($lat) && isset($lng)) {
				storeGeocodedLocation($place, $lat, $lng);
				$successfulGeocodingRequests++;
			} else if ($status == "OVER_QUERY_LIMIT") {
				time_nanosleep(0, 999999999);
				array_push($geocodingQueue, $place);
				$blockedGeocodingRequests++;
			} else {
				if(isset($output)) {
					$output .= "##Geocoding error: $place (reason: $status)";
				} else {
					$output = "Geocoding error: $place (reason: $status)";
				}
				$erroneousGeocodingRequests++;
			}

			time_nanosleep(0, 500000000);
		} else {
			$skippedGeocodingRequests++;
		}
	}
	unset($place);
	
	storeGeocodingStat(0, $successfulGeocodingRequests);
	storeGeocodingStat(1, $erroneousGeocodingRequests);
	storeGeocodingStat(2, $skippedGeocodingRequests);
	storeGeocodingStat(3, $blockedGeocodingRequests);
	
	if(isset($output)) {
		$output .= "##successful: $successfulGeocodingRequests, erroneous: $erroneousGeocodingRequests, skipped: $skippedGeocodingRequests, total: ".sizeof($geocodingQueue).", flow records: ".$sessionData->flowRecordCount;
	} else {
		$output = "successful: $successfulGeocodingRequests, erroneous: $erroneousGeocodingRequests, skipped: $skippedGeocodingRequests, total: ".sizeof($geocodingQueue).", flow records: ".$sessionData->flowRecordCount;
	}
	echo $output;
	
?>
