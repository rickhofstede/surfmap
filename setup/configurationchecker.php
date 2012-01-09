<?php
	/*******************************
	 # configurationchecker.php [SURFmap]
	 # Author: Rick Hofstede
	 # University of Twente, The Netherlands
	 #
	 # LICENSE TERMS: outlined in BSD-license.html
	 *******************************/
	
	require_once("../config.php");
	require_once("../connectionhandler.php");
	require_once("../sessionhandler.php");

	function dir_tree($dir) {
		$path = '';
	   	$stack[] = $dir;
	   	while ($stack) {
	    	$thisdir = array_pop($stack);
	       	if ($dircont = scandir($thisdir)) {
	        	$i=0;
	           	while (isset($dircont[$i])) {
	               	if ($dircont[$i] !== '.' && $dircont[$i] !== '..') {
	                	$current_file = "{$thisdir}/{$dircont[$i]}";
	                   	if (is_file($current_file)) {
	                    	$path[] = "{$thisdir}/{$dircont[$i]}";
	                   	} elseif (is_dir($current_file)) {
	                        $path[] = "{$thisdir}/{$dircont[$i]}";
	                       	$stack[] = $current_file;
	                   	}
	               	}
	               	$i++;
	           	}
	       	}
	   	}
		return $path;
	}
	
	$nfsenConfigReadable = 0;
	$nfsenSocketOK = 0;
	$nfsenSourceDirOK = 0;
	
	// 1. NfSen configuration file (nfsen.conf) availability
	if (is_readable($NFSEN_CONF)) {
		$nfsenConfigReadable = 1;
		
		$nfsenConfig = readNfSenConfig();
		require_once($nfsenConfig['HTMLDIR']."/conf.php");
		
		// 2. Check NfSen socket
		if (@file_exists($COMMSOCKET)) {
			$nfsenSocketOK = 1;
		}
		
		// 3. Check NfSen data directory
		if (@file_exists($nfsenConfig['PROFILEDATADIR'])) {
			$nfsenSourceDirOK = 1;
		}
	}
	
	try {
		// 4. Check Geocoder database connection
		$PDODrivers = PDO::getAvailableDrivers();
		if (in_array("sqlite", $PDODrivers)) {
			$geocoderDBFile = "sqlite:../$GEOCODER_DB_SQLITE3";
			$geocoderDBConnection = new PDO($geocoderDBFile);
		} else if (in_array("sqlite2", $PDODrivers)) {
			$geocoderDBFile = "sqlite:../$GEOCODER_DB_SQLITE2";
			$geocoderDBConnection = new PDO($geocoderDBFile);
		} else {
		}
		
		if (isset($geocoderDBConnection)) {
			$geocoderDatabaseConnectionOK = 1;

			// 5. Check Geocoder database writability
			$geocoderDBConnection->exec("INSERT INTO geocoder VALUES (".$geocoderDBConnection->quote("_TEST_").", 1.0, -1.0)");
			$queryResult = $geocoderDBConnection->query("SELECT * FROM geocoder WHERE location = ".$geocoderDBConnection->quote("_TEST_"));
			$row = $queryResult->fetch(PDO::FETCH_ASSOC);
			unset($queryResult);

			$deleteCount = $geocoderDBConnection->exec("DELETE FROM geocoder WHERE location = ".$geocoderDBConnection->quote("_TEST_"));
			
			if ($row && $deleteCount == 1) {
				$geocoderDatabaseOK = 1;
			} else {
				$geocoderDatabaseOK = 0;
			}
		} else {
			$geocoderDatabaseConnectionOK = 0;
			$geocoderDatabaseOK = 0;
		}
	} catch (PDOException $e) {
		$geocoderDatabaseConnectionOK = 0;
		$geocoderDatabaseOK = 0;
	}
	
	// 6. Check IP2Location DB path
	$ip2LocationPath = (substr($IP2LOCATION_PATH, 0, 1) === "/") ? $IP2LOCATION_PATH : "../".$IP2LOCATION_PATH; // Check for absolute or relative path
	if (@file_exists($ip2LocationPath)) $ip2LocationDBPathOK = 1;
	else $ip2LocationDBPathOK = 0;
	
	// 7. Check MaxMind DB path
	$maxMindPath = (substr($MAXMIND_PATH, 0, 1) === "/") ? $MAXMIND_PATH : "../".$MAXMIND_PATH; // Check for absolute or relative path
	if (@file_exists($maxMindPath)) $maxmindDBPathOK = 1;
	else $maxmindDBPathOK = 0;
	
	// 8. Check file permissions
	$dir = dir_tree("..");
	$filePermOK = 1;
	$filesWithWrongPerm = "";
	foreach($dir as $entry) {
		$permIntVal = intval(substr(sprintf('%o', @fileperms($entry)), -4));
		
		if ((strpos($entry, "../geocoder/") === 0 && !is_Writable($entry)) || !is_readable($entry))  {
			if (strpos($entry, "../") == 0) {
				$entry = substr($entry, 3);
			}
			
			if ($filesWithWrongPerm == "") {
				$filesWithWrongPerm = $entry."__".$permIntVal;
			} else {
				$filesWithWrongPerm .= "___".$entry."__".$permIntVal;
			}	
			$filePermOK = 0;
		}
	}
	
	// 9. Check additional NetFlow source selector syntax
	$additionalSrcSelectorExploded = explode(";", $NFSEN_DEFAULT_SOURCES);
	if ($NFSEN_DEFAULT_SOURCES == "" ||
			(substr_count($NFSEN_DEFAULT_SOURCES, ",") == 0 && substr_count($NFSEN_DEFAULT_SOURCES, ":") == 0 &&
			sizeof($additionalSrcSelectorExploded) == substr_count($NFSEN_DEFAULT_SOURCES, ";") + 1) && !in_array("", $additionalSrcSelectorExploded)) {
		$additionalSrcSelectorSyntaxOK = 1;
	} else {
		$additionalSrcSelectorSyntaxOK = 0;
	}
	
	// 10. Check map center coordinates syntax
	if (substr_count($MAP_CENTER, ",") == 1 && substr_count($MAP_CENTER, ".") <= 2) $mapCenterSyntaxOK = 1;
	else $mapCenterSyntaxOK = 0;
	
	// 11. Check internal domain syntax
	$internalDomainExploded = explode(";", $INTERNAL_DOMAINS);
	$internalDomainSyntaxOK = 1;
	if (sizeof($internalDomainExploded) > 0) {
		foreach($internalDomainExploded as $domain) {
			if (substr_count($domain, ".") < 1 || substr_count($domain, "/") != 1) {
				$internalDomainSyntaxOK = 0;
				break;
			}
		}
	}
	
	// 12. Check availability of 'mbstring' PHP module (for MaxMind API)
	if (extension_loaded("mbstring")) {
		$mbstringModuleOK = 1;
	} else {
		$mbstringModuleOK = 0;
	}
	
	// 13. External IP address and location
	$extIP = (!getenv("SERVER_ADDR")) ? "127.0.0.1" : getenv("SERVER_ADDR");
	if ($extIP === "127.0.0.1") {
		$extIPNAT = true;
	} else {
		$extIPNAT = false;
		$internalDomainNets = explode(";", $INTERNAL_DOMAINS);
		foreach($internalDomainNets as $subNet) {
			if (ipAddressBelongsToNet($extIP, $subNet)) {
				$extIPNAT = true;
				break;
			}
		}
	}

	/*
	 * If the found (external) IP address of the server is the localhost
	 * address or a NATed address, try do find it using WhatIsMyIP
	 */
	$extIPError = "";
	if ($extIPNAT === true) {
		$NATIP = $extIP;
		try {
			if (extension_loaded("curl")) {
				for ($i = 0; $i < 3; $i++) {
					$curl_handle = curl_init();
					curl_setopt($curl_handle, CURLOPT_URL, "http://whatismyip.org/");
					curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 0);
					$extIP = curl_exec($curl_handle);
					curl_close($curl_handle);
					
					if ($extIP === "Too frequent!" || $extIP === "") {
						sleep(10);
						continue;
					} else {
						break;
					}
				}
			}

			/*
			 * The first condition is included in the second condition, but it's kept
			 * here to keep in mind that this String can be returned by the service.
			 *
			 * If $extIP == $NATIP, cURL is probably not installed/activated.
			 */
			if ($extIP === "Too frequent!" || substr_count($extIP, ".") != 3  || $extIP == $NATIP) {
				$extIP = $NATIP;
				$extIPError = "Unable to retrieve external IP address";
			}
		} catch (Exception $e) {}
	}

	
	// Check whether the (eventually) discovered external IP address is still a NATed one
	$extIPNAT = false;
	if (isset($internalDomainNets)) {
		foreach($internalDomainNets as $subNet) {
			if (ipAddressBelongsToNet($extIP, $subNet)) {
				$extIPNAT = true;
				break;
			}
		}
	}
	
	if ($extIPNAT === false && $GEOLOCATION_DB === "IP2Location" && $ip2LocationDBPathOK === 1) {
		$GEO_database = new ip2location();
		$GEO_database->open($ip2LocationPath);
		$data = $GEO_database->getAll($extIP);
		
		$extIPCountry = $data->countryLong;
		if ($extIPCountry == "-") $extIPCountry = "(Unknown)";
		$extIPRegion = $data->region;
		if ($extIPRegion == "-") $extIPRegion = "(Unknown)";
		$extIPCity = $data->city;
		if ($extIPCity == "-") $extIPCity = "(Unknown)";
	} else if ($extIPNAT === false && $GEOLOCATION_DB === "MaxMind" && $maxmindDBPathOK === 1) {
		$GEO_database = geoip_open($maxMindPath, GEOIP_STANDARD);
		$data = geoip_record_by_addr($GEO_database, $extIP);
		
		if (isset($data->country_name)) {
			$extIPCountry = strtoupper($data->country_name);
		}
		if (!isset($extIPCountry) || $extIPCountry == "") $extIPCountry = "(Unknown)";

		if (isset($data->country_code) && isset($data->region)
				&& array_key_exists($data->country_code, $GEOIP_REGION_NAME)
				&& array_key_exists($data->region, $GEOIP_REGION_NAME[$data->country_code])) {
			$extIPRegion = strtoupper($GEOIP_REGION_NAME[$data->country_code][$data->region]);
		}
		if (!isset($extIPRegion) || $extIPRegion == "") $extIPRegion = "(Unknown)";

		if (isset($data->city)) {
			$extIPCity = strtoupper($data->city);
		}
		if (!isset($extIPCity) || $extIPCity == "") $extIPCity = "(Unknown)";
	} else {
		$extIPCountry = "(Unknown)";
		$extIPRegion = "(Unknown)";
		$extIPCity = "(Unknown)";
	}
	
	$extIPCountry = stripAccentedCharacters($extIPCountry);
	$extIPRegion = stripAccentedCharacters($extIPRegion);
	$extIPCity = stripAccentedCharacters($extIPCity);
	if ($extIPCountry === "(Unknown)") {
		$extIPLocationOK = 0;
	} else {
		$extIPLocationOK = 1;
	}
	
	if ($extIPCity != "(Unknown)") {
		$latLng = geocode($extIPCity);
	} else if ($extIPRegion != "(Unknown)") {
		$latLng = geocode($extIPRegion);
	} else if ($extIPCountry != "(Unknown)") {
		$latLng = geocode($extIPCountry);
	}
	
	$locationString = $extIPCountry.",".$extIPRegion.",".$extIPCity;
	if (isset($latLng) && is_array($latLng)) {
		$locationString .= ",".$latLng[0].",".$latLng[1];
	} else {
		$locationString .= ",(Unknown),(Unknown)";
	}
	
	/**
	 * Starts calls to the Google Maps API GeoCoder. It is derived from the 'geocode()'
	 * method in [index.php].
	 * Return:
	 *		array(lat, lng) on success, or 'false' (bool) on failure
	 */	
	function geocode($place) {
		global $FORCE_HTTPS;
		
		$requestURL = "://maps.google.com/maps/api/geocode/xml?address=" . urlencode($place) ."&sensor=false";
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
		
		if ($status == "OVER_QUERY_LIMIT") {
			time_nanosleep(0, 1000000000);
			geocode($place);
		}
		
		return ($status == "OK" && isset($lat) && isset($lng)) ? array($lat, $lng) : false;
	}
	
?>

<!DOCTYPE html>
<html>
	<head>
		<title>SURFmap Configuration Checker</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<link type="text/css" rel="stylesheet" href="../jquery/css/start/jquery-ui-1.8.16.custom.css" />
		<link type="text/css" rel="stylesheet" href="../css/surfmap.css" />
		<script type="text/javascript" src="<?php if ($FORCE_HTTPS) {echo 'https';} else {echo 'http';} ?>://maps.google.com/maps/api/js?sensor=false"></script>
		<script type="text/javascript" src="../jquery/js/jquery-1.6.2.min.js"></script>
		<script type="text/javascript" src="../jquery/js/jquery-ui-1.8.16.custom.min.js"></script>
		<script type="text/javascript" src="../js/jqueryutil.js"></script>
		<style type="text/css">
			body {
				font-family: Verdana;
				font-size: 80%;
			}
			.checkitem {
				border-style: solid;
				border-width: 1px;
				margin: 10px;
				width: 600px;
			}
			.checkitem_success {
				background-color:rgb(0,255,128);
			}
			.checkitem_failure {
				background-color:rgb(255,102,102);
			}
			.checkitem_skip {
				background-color:rgb(102,204,255);
			}
			
		</style>
	</head>
	<body>
		<h1>SURFmap Configuration Checker</h1>
		
		<p >This application checks most of the main configuration options of the SURFmap application. For each 
			of these options the currently configured value (between brackets) and the status are shown. Three 
			status options are possible:</p>
		<ol>
			<li>Green (OK). The configuration seems to be set correctly.</li>
			<li>Red (Failure). An error was found. The mentioned option is required for your setup. 
				Please try another configuration.</li>
			<li>Blue (Skip). The configuration option was not set correctly, but it is not required 
				for your setup. You can therefore ignore this.</li>
		</ol>
		
		<p>The <i>Setup Guidelines</i> section below shows some proposed setting values, based
			on your machine's location.</p>
		
		<p>In order to help you to fix your configuration problem, some hints are provided with each of
			the configuration options below. To find these hints, please click on the corresponding option
			and a help window will appear.</p>
			
		<div id="setupguidelines" class="checkitem"><b>Setup guidelines</b><br /><br />You can use the following settings in config.php:<br /><br /></div>
		<div id="checkitem1" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem1Title + '##' + checkItem1Text);">1. NfSen configuration file (<?php echo $NFSEN_CONF; ?>) availability.</div>
		<div id="checkitem2" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem2Title + '##' + checkItem2Text);">2. NfSen communication socket available.</div>
		<div id="checkitem3" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem3Title + '##' + checkItem3Text);">3. NfSen source directory available.</div>
		<div id="checkitem4" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem4Title + '##' + checkItem4Text);">4. GeoCoder database connection (<?php if (isset($geocoderDBFile)) { echo $geocoderDBFile; } else { echo ""; } ?>) available.</div>
		<div id="checkitem5" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem5Title + '##' + checkItem5Text);">5. GeoCoder database writability OK ('<?php echo $geocoderDBFile; ?>' should be writable).</div>
		<div id="checkitem6" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem6Title + '##' + checkItem6Text);">6. IP2Location database (<?php echo $ip2LocationPath; ?>) available.</div>
		<div id="checkitem7" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem7Title + '##' + checkItem7Text);">7. MaxMind database (<?php echo $maxMindPath; ?>) available.</div>
		<div id="checkitem8" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem8Title + '##' + checkItem8Text);">8. Permissions of all directory contents OK.</div>
		<div id="checkitem9" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem9Title + '##' + checkItem9Text);">9. Additional NetFlow source selector (<?php echo $NFSEN_DEFAULT_SOURCES; ?>) syntax OK.</div>
		<div id="checkitem10" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem10Title + '##' + checkItem10Text);">10. Map center coordinates (<?php echo $MAP_CENTER; ?>) syntax OK.</div>
		<div id="checkitem11" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem11Title + '##' + checkItem11Text);">11. Internal domain (<?php echo $INTERNAL_DOMAINS; ?>) syntax OK.</div>
		<div id="checkitem12" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem12Title + '##' + checkItem12Text);">12. PHP 'mbstring' module available.</div>
		<div id="checkitem13" class="checkitem" onclick="generateDialog('configurationCheckerHelp', checkItem13Title + '##' + checkItem13Text);">13. External IP address: <?php if ($extIPError !== "") echo $extIPError; else echo $extIP; ?><br />Geolocated country: <?php echo $extIPCountry; ?><br />Geolocated region: <?php echo $extIPRegion; ?><br />Geolocated city: <?php echo $extIPCity; ?></div>
				
		<div id="dialog"></div>
		<div id="configdata" style="display:none;"><?php echo $locationString; ?></div>
		
		<script type="text/javascript">
			var useGeocoderDB = <?php echo $USE_GEOCODER_DB; ?>;
			var geolocationDB = "<?php echo $GEOLOCATION_DB; ?>";
		
			var nfsenConfigReadable = <?php echo $nfsenConfigReadable; ?>;
			var nfsenSocketOK = <?php echo $nfsenSocketOK; ?>;
			var nfsenSourceDirOK = <?php echo $nfsenSourceDirOK; ?>;
			var geocoderDatabaseConnectionOK = <?php echo $geocoderDatabaseConnectionOK; ?>;
			var geocoderDatabaseOK = <?php echo $geocoderDatabaseOK; ?>;
			var ip2LocationDatabaseOK = <?php echo $ip2LocationDBPathOK; ?>;
			var maxmindDatabaseOK = <?php echo $maxmindDBPathOK; ?>;
		
			var filePermissionsOK = <?php echo $filePermOK; ?>;
			var filesWithWrongPerm = "<?php echo $filesWithWrongPerm; ?>".split("___");
			
			var additionalSrcSelectorSyntaxOK = <?php echo $additionalSrcSelectorSyntaxOK; ?>;
			var mapCenterSyntaxOK = <?php echo $mapCenterSyntaxOK; ?>;
			var internalDomainSyntaxOK = <?php echo $internalDomainSyntaxOK; ?>;
			
			var mbstringModuleOK = <?php echo $mbstringModuleOK; ?>;
			
			var extIPNAT = <?php if ($extIPNAT === true) echo "true"; else echo "false"; ?>;
			var extIPError = "<?php echo $extIPError; ?>";
			var extIPLocationOK = <?php echo $extIPLocationOK; ?>;
			var extIPCountry = "<?php echo $extIPCountry; ?>";
			var extIPRegion = "<?php echo $extIPRegion; ?>";
			var extIPCity = "<?php echo $extIPCity; ?>";
			var extIPCoordinates = "<?php if (isset($latLng) && is_array($latLng)) { echo $latLng[0].','.$latLng[1]; } else { echo ''; } ?>";

			// Setup guidelines
			if (extIPNAT || extIPError != "") {
				document.getElementById("setupguidelines").style.display = "none";
			} else if (extIPCountry != "(Unknown)") {
				document.getElementById("setupguidelines").innerHTML += "$INTERNAL_DOMAINS_COUNTRY=\"" + extIPCountry + "\";<br />";
			}
			if (extIPRegion != "(Unknown)") {
				document.getElementById("setupguidelines").innerHTML += "$INTERNAL_DOMAINS_REGION=\"" + extIPRegion + "\";<br />";
			}
			if (extIPCity != "(Unknown)") {
				document.getElementById("setupguidelines").innerHTML += "$INTERNAL_DOMAINS_CITY=\"" + extIPCity + "\";<br />";
			}
			if (extIPCoordinates != "") {
				document.getElementById("setupguidelines").innerHTML += "$MAP_CENTER=\"" + extIPCoordinates + "\";<br />";
			}

			// 1. NfSen configuration file (nfsen.conf) availability
			if (nfsenConfigReadable == 1) {
				document.getElementById("checkitem1").className += " checkitem_success";
			} else {
				document.getElementById("checkitem1").className += " checkitem_failure";
			}
		
			// 2. Check NfSen socket
			if (nfsenSocketOK == 1) {
				document.getElementById("checkitem2").className += " checkitem_success";
			} else {
				document.getElementById("checkitem2").className += " checkitem_failure";
			}
			
			// 3. Check NfSen data directory
			if (nfsenSourceDirOK == 1) {
				document.getElementById("checkitem3").className += " checkitem_success";
			} else {
				document.getElementById("checkitem3").className += " checkitem_failure";
			}
			
			// 4. Check Geocoder database connection
			if (geocoderDatabaseConnectionOK == 1) {
				document.getElementById("checkitem4").className += " checkitem_success";
			} else if (useGeocoderDB == 1 && geocoderDatabaseConnectionOK == 0) {
				document.getElementById("checkitem4").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem4").className += " checkitem_skip";
			}
			
			// 5. Check Geocoder database writability
			if (geocoderDatabaseOK == 1) {
				document.getElementById("checkitem5").className += " checkitem_success";
			} else if (useGeocoderDB == 1 && geocoderDatabaseOK == 0) {
				document.getElementById("checkitem5").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem5").className += " checkitem_skip";
			}
			
			// 6. Check IP2Location DB path
			if (ip2LocationDatabaseOK == 1) {
				document.getElementById("checkitem6").className += " checkitem_success";
			} else if (geolocationDB == "IP2Location" && ip2LocationDatabaseOK == 0) {
				document.getElementById("checkitem6").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem6").className += " checkitem_skip";
			}
			
			// 7. Check MaxMind DB path
			if (maxmindDatabaseOK == 1) {
				document.getElementById("checkitem7").className += " checkitem_success";
			} else if (geolocationDB == "MaxMind" && maxmindDatabaseOK == 0) {
				document.getElementById("checkitem7").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem7").className += " checkitem_skip";
			}
			
			// 8. Check file permissions
			if (filePermissionsOK == 1) {
				document.getElementById("checkitem8").className += " checkitem_success";
			} else {
				document.getElementById("checkitem8").className += " checkitem_failure";
				document.getElementById("checkitem8").innerHTML += "<br /><br />Files with wrong permissions:<br />---<br />";
				for (i in filesWithWrongPerm) {
					// Delimiter between tuples: '___'
					// Delimiter inside tuple (between file name/path and permissions): '__'
					currentTuple = filesWithWrongPerm[i].split("__");
					document.getElementById("checkitem8").innerHTML += currentTuple[0] + " (" + currentTuple[1] + ")<br />";
				}
			}
			
			// 9. Check additional NetFlow source selector syntax
			if (additionalSrcSelectorSyntaxOK == 1) {
				document.getElementById("checkitem9").className += " checkitem_success";
			} else {
				document.getElementById("checkitem9").className += " checkitem_failure";
			}
			
			// 10. Check map center coordinates syntax
			if (mapCenterSyntaxOK == 1) {
				document.getElementById("checkitem10").className += " checkitem_success";
			} else {
				document.getElementById("checkitem10").className += " checkitem_failure";
			}
			
			// 11. Check internal domain syntax
			if (internalDomainSyntaxOK == 1) {
				document.getElementById("checkitem11").className += " checkitem_success";
			} else {
				document.getElementById("checkitem11").className += " checkitem_failure";
			}
			
			// 12. Check availability of 'mbstring' PHP module (for MaxMind API)
			if (mbstringModuleOK == 1) {
				document.getElementById("checkitem12").className += " checkitem_success";
			} else if (geolocationDB == "MaxMind" && mbstringModuleOK == 0) {
				document.getElementById("checkitem12").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem12").className += " checkitem_skip";
			}
			
			// 13. External IP address and location
			if (extIPLocationOK == 1) {
				document.getElementById("checkitem13").className += " checkitem_success";
			} else {
				document.getElementById("checkitem13").className += " checkitem_failure";
			}
			
			var checkItem1Title = "NfSen configuration file (nfsen.conf) availability";
			var checkItem1Text = "Check whether the NfSen configuration file is either not available at all, or not readable by the user executing your Web server.";
			
			var checkItem2Title = "NfSen communication socket";
			var checkItem2Text = "The path to the NfSen communication socket can normally be found in NfSen's [conf.php] file, which is located in the NfSen's main directory. The default variable name is '$COMMSOCKET'.";
			
			var checkItem3Title = "NfSen data directory";
			var checkItem3Text = "This path should lead to the root NfSen's network data capture folder and should be configured properly in [nfsen.conf] (NfSen main configuration file).";
			
			var checkItem4Title = "GeoCoder database connection";
			var checkItem4Text = "In case a GeoCoder (caching) database is selected to be used, a proper database connection should be configured. Since SQLite technology is used by SURFmap, the file paths in [$GEOCODER_DB_SQLITE2] and [$GEOCODER_DB_SQLITE3] should be valid.";
			
			var checkItem5Title = "GeoCoder database writability";
			var checkItem5Text = "The (SQLite) database file should have the correct permissions in order to be writable.";

			var checkItem6Title = "IP2Location database";
			var checkItem6Text = "In case you selected 'IP2Location' as your geolocation database (in [$GEOLOCATION_DB]), please verify the path to the database file. You can use both absolute and relative paths.";
			
			var checkItem7Title = "MaxMind database";
			var checkItem7Text = "In case you selected 'MaxMind' as your geolocation database (in [$GEOLOCATION_DB]), please verify the path to the database file. You can use both absolute and relative paths.";
			
			var checkItem8Title = "File permissions";
			var checkItem8Text = "All files need to be (at least) readable by your Web server. Otherwise the PHP engine will not be able to successfully execute and process the SURFmap source files. Please make sure that all permissions are set correctly. Please note that some Web server configurations require PHP files to be executable in order to be processed.";
			
			var checkItem9Title = "Additional Netflow source selector syntax";
			var checkItem9Text = "This setting should consist of an empty String in case only the primary source needs to be used (so no additional source). If more than one additional source is used, they should be separated by a semi-colon (;).";

			var checkItem10Title = "Map center syntax";
			var checkItem10Text = "This setting should consist consist of two (decimal) values, separated by a comma (,). The first value represents the latitude coordinate, the second value represents the longitude coordinate. The latitude coordinate can have a value on the interval <-90.0, 90.0>, the longitude coordinate can have a value on the interval <-180.0, 180.0>";
			
			var checkItem11Title = "Internal domain syntax";
			var checkItem11Text = "This setting contains the internal domain of the NetFlow exporter. Multiple domains need to be separated by a semi-colon (;). To be certain that the syntax is OK (the Configuration Checker only checks a few possible errors), please check it in the 'Filter' field of NfSen's \"Details\" page.";

			var checkItem12Title = "PHP 'mbstring' module";
			var checkItem12Text = "In case you selected 'MaxMind' as your geolocation database, you should have PHP's 'mbstring' module installed, in order to get the MaxMind API to work.";

			var checkItem13Title = "External IP address and location";
			var checkItem13Text = "If your PHP configuration contains your public IP address, it is likely to be geolocatable. In that case, it is shown here. If the locations are unknown, you need to geolocate it manually.";
		</script>
	</body>
</html>