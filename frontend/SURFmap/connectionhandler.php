<?php
	/*******************************
	 # connectionhandler.php [SURFmap]
	 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
	 # University of Twente, The Netherlands
	 #
	 # LICENSE TERMS: outlined in BSD-license.html
	 *******************************/
	
	require_once("MaxMind/geoipcity.inc");
	require_once("IP2Location/ip2location.class.php");
	
	class ConnectionHandler {
		var $GeocoderDatabase;
		
		/*
		 * Constructs a new ConnectionHandler object.
		 */
		function __construct ($logHandler, $sessionHandler) {
			global $USE_GEOCODER_DB, $GEOCODER_DB_SQLITE3;
			
			$this->logHandler = $logHandler;
			$this->sessionHandler = $sessionHandler;
			
			if ($USE_GEOCODER_DB) {
				try {
					if (in_array("sqlite", PDO::getAvailableDrivers())) {
						$this->GeocoderDatabase = new PDO("sqlite:$GEOCODER_DB_SQLITE3");
					} else {
					}
				} catch(PDOException $e) {}
			}
		}
				
	   /*
		* Returns the NfSen query results.
		*/
		function retrieveDataNfSen () {
			global $sessionData, $SORT_FLOWS_BY_START_TIME;
			
			// Queries
			if ($_SESSION['SURFmap']['nfsenOption'] == 0) {
				$run = "-R nfcapd.".$_SESSION['SURFmap']['date1'].$_SESSION['SURFmap']['hours1'].$_SESSION['SURFmap']['minutes1'].
						":nfcapd.".$_SESSION['SURFmap']['date2'].$_SESSION['SURFmap']['hours2'].$_SESSION['SURFmap']['minutes2'].
						" -c ".$_SESSION['SURFmap']['entryCount'];
			} else {
				$run = "-R nfcapd.".$_SESSION['SURFmap']['date1'].$_SESSION['SURFmap']['hours1'].$_SESSION['SURFmap']['minutes1'].
						":nfcapd.".$_SESSION['SURFmap']['date2'].$_SESSION['SURFmap']['hours2'].$_SESSION['SURFmap']['minutes2'].
						" -n ".$_SESSION['SURFmap']['entryCount']." -s record/".$_SESSION['SURFmap']['nfsenStatOrder'].
						" -A proto,srcip,srcport,dstip,dstport";
			}

			if ($_SESSION['SURFmap']['nfsenOption'] == 0 && $SORT_FLOWS_BY_START_TIME == 1) {
				$run .= " -m";
			}

			$cmd_opts['args'] = "-T $run -o long";
			$cmd_opts['profile'] = $_SESSION['SURFmap']['nfsenProfile'];
			$cmd_opts['type'] = $_SESSION['SURFmap']['nfsenProfileType'];
			$cmd_opts['srcselector'] = $_SESSION['SURFmap']['nfsenSelectedSources'];	
			$cmd_opts['filter'] = array($_SESSION['SURFmap']['flowFilter']);

			// Execute NfSen query
			$cmd_out = nfsend_query("run-nfdump", $cmd_opts);
			$sessionData->query = "** nfdump ".$cmd_out['arg'];
			
			if (isset($cmd_out['nfdump']) && $cmd_out["exit"] > 0) {
				$sessionData->errorCode = 1; // filter error
				
				if (count($cmd_out['nfdump']) > 0) {
					$sessionData->errorMessage = $cmd_out['nfdump'][0];
					
					if ($sessionData->errorMessage == "Killed") {
						$sessionData->errorCode = 8; // flow query killed
						$this->sessionHandler->setDatesAndTimes(true);
					}
				}

				$sessionData->flowRecordCount = 0;
				return;
			} else if (isset($_SESSION['error']) && isset($_SESSION['error'][0])) {
				$sessionData->errorCode = 6; // profile error
				$sessionData->errorMessage = $_SESSION['error'][0];
				$sessionData->flowRecordCount = 0;
				return;				
			} else if (!isset($cmd_out['nfdump']) || sizeof($cmd_out['nfdump']) == 1) {
				$sessionData->errorCode = 5; // no flow records error
				$sessionData->flowRecordCount = 0;
				return;
			}
			
			$NetFlowData = array();
			if ($_SESSION['SURFmap']['nfsenOption'] == 0) { // List flows
				// Calculate flowRecordCount, for the case that less flow records are returned than the actual entryCount
				$sessionData->flowRecordCount = sizeof($cmd_out['nfdump']) - 5; // Five lines are always present (header & footer)
				
				for ($i = 1; $i <= $sessionData->flowRecordCount; $i++) {
					$flow = new FlowRecord();
					$line_array = explode(" ", stripSpecialCharacters($cmd_out['nfdump'][$i]));

					$factor_array = array();
					if (sizeof($line_array) > 11) {
						for ($j = 8; $j < sizeof($line_array); $j++) {
							if (!is_numeric($line_array[$j])) array_push($factor_array, $j);
						}
					}

					$hosts[0] = explode(":", $line_array[4]);
					$hosts[1] = explode(":", $line_array[5]);
					$flow->ipv4_src = $hosts[0][0];
					$flow->ipv4_dst = $hosts[1][0];

					// Handle situation in which either packets, octets or both have a MEGA or GIGA factor
					if (sizeof($factor_array) == 1) {
						if ($line_array[$factor_array[0]] == "M") {
							$line_array[$factor_array[0] - 1] = $line_array[$factor_array[0] - 1] * 1000000;
						} else if ($line_array[$factor_array[0]] == "G") {
							$line_array[$factor_array[0] - 1] = $line_array[$factor_array[0] - 1] * 1000000000;
						}

						if ($factor_array[0] > 9) { // 'Bytes' has factor
							$flow->packets = $line_array[8];
							$flow->octets = $line_array[9];
						} else { // 'Packets' has factor
							$flow->packets = $line_array[8];
							$flow->octets = $line_array[10];
						}

					} else if (sizeof($factor_array) == 2) {
						for ($k = 0; $k < sizeof($factor_array); $k++) {			
							if ($line_array[$factor_array[$k]] == "M") {
								$line_array[$factor_array[$k] - 1] = $line_array[$factor_array[$k] - 1] * 1000000;
							} else if ($line_array[$factor_array[$k]] == "G") {
								$line_array[$factor_array[$k] - 1] = $line_array[$factor_array[$k] - 1] * 1000000000;
							}
						}

						$flow->packets = $line_array[8];
						$flow->octets = $line_array[10];					
					} else {
						$flow->packets = $line_array[8];
						$flow->octets = $line_array[9];
					}

					$flow->duration = $line_array[2];
					$flow->protocol = $line_array[3];
					$flow->port_src = $hosts[0][1];
					$flow->port_dst = $hosts[1][1];
					array_push($NetFlowData, $flow);
				}
			} else { // Stat TopN
				// Calculate flowRecordCount, for the case that less flow records are returned than the actual entryCount
				$sessionData->flowRecordCount = sizeof($cmd_out['nfdump']) - 8; // 8 lines are always present (header & footer)
				
				for ($i = 3; $i < $sessionData->flowRecordCount + 3; $i++) {
					$flow = new FlowRecord();

					$line_array = explode(" ", stripSpecialCharacters($cmd_out['nfdump'][$i]));

					$factor_array = array();
					if (sizeof($line_array) > 11) {
						for ($j = 8; $j < sizeof($line_array); $j++) {
							if (!is_numeric($line_array[$j])) array_push($factor_array, $j);
						}
					}

					$hosts[0] = explode(":", $line_array[4]);
					$hosts[1] = explode(":", $line_array[5]);
					$flow->ipv4_src = $hosts[0][0];
					$flow->ipv4_dst = $hosts[1][0];

					// Handle situation in which either packets, octets or both have a GIGA or MEGA factor
					if (sizeof($factor_array) == 1) {
						if ($line_array[$factor_array[0]] == "M") {
							$line_array[$factor_array[0] - 1] = $line_array[$factor_array[0] - 1] * 1000000;
						} else if ($line_array[$factor_array[0]] == "G") {
							$line_array[$factor_array[0] - 1] = $line_array[$factor_array[0] - 1] * 1000000000;
						}

						if ($factor_array[0] > 9) { // 'Bytes' has factor
							$flow->packets = $line_array[8];
							$flow->octets = $line_array[9];
						} else { // 'Packets' has factor
							$flow->packets = $line_array[8];
							$flow->octets = $line_array[10];
						}

					} else if (sizeof($factor_array) == 2) {
						for ($k = 0; $k < sizeof($factor_array); $k++) {			
							if ($line_array[$factor_array[$k]] == "M") {
								$line_array[$factor_array[$k] - 1] = $line_array[$factor_array[$k] - 1] * 1000000;
							} else if ($line_array[$factor_array[$k]] == "G") {
								$line_array[$factor_array[$k] - 1] = $line_array[$factor_array[$k] - 1] * 1000000000;
							}
						}

						$flow->packets = $line_array[8];
						$flow->octets = $line_array[10];					
					} else {
						$flow->packets = $line_array[8];
						$flow->octets = $line_array[9];
					}

					$flow->duration = $line_array[2];
					$flow->protocol = $line_array[3];
					$flow->port_src = $hosts[0][1];
					$flow->port_dst = $hosts[1][1];
					$flow->flows = $line_array[sizeof($line_array) - 1];
					array_push($NetFlowData, $flow);
				}
			}

			return $NetFlowData;
		}
		
		/*
		 * Retrieves data from a Geolocation data provider, which is
		 * either 'IP2Location' or 'MaxMind'.
		 */
		function retrieveDataGeolocation ($NetFlowData) {
			global $GEOLOCATION_DB, $IP2LOCATION_PATH, $MAXMIND_PATH, $GEOIP_REGION_NAME, // $GEOIP_REGION_NAME is part of the MaxMind API
					$INTERNAL_DOMAINS, $INTERNAL_DOMAINS_COUNTRY, $INTERNAL_DOMAINS_REGION, $INTERNAL_DOMAINS_CITY;

			$GeoData = array();
			$internalDomainNets = explode(";", $INTERNAL_DOMAINS);

			for ($i = 0; $i < count($NetFlowData); $i++) {
				$source = $NetFlowData[$i]->ipv4_src;
				$destination = $NetFlowData[$i]->ipv4_dst;

				/*
				 * Check whether a NATed setup was used. If so, use the geolocation data provided
				 * in the configuration file. Otherwise, use a geolocation service.
				 */
				$srcNAT = false;
				$dstNAT = false;
				
				foreach ($internalDomainNets as $subNet) {
					if (ipAddressBelongsToNet($source, $subNet)) {
						$srcNAT = true;
						break;
					}
				}
				foreach ($internalDomainNets as $subNet) {
					if (ipAddressBelongsToNet($destination, $subNet)) {
						$dstNAT = true;
						break;
					}
				}
				unset($subNet);
				
				for ($j = 0; $j < 2; $j++) { // Source and destination
					if (($j == 0 && $srcNAT === true) || ($j == 1 && $dstNAT === true)) { // Source or destination uses a NATed setup
						$country = strtoupper($INTERNAL_DOMAINS_COUNTRY);
						if ($country == "") $country = "(UNKNOWN)";
						
						$region = strtoupper($INTERNAL_DOMAINS_REGION);
						if ($region == "") $region = "(UNKNOWN)";
						
						$city = strtoupper($INTERNAL_DOMAINS_CITY);
						if ($city == "") $city = "(UNKNOWN)";
					} else if ($GEOLOCATION_DB == "IP2Location") {
						$GEO_database = new ip2location();
						$GEO_database->open($IP2LOCATION_PATH);
						
						if ($j == 0) $data = $GEO_database->getAll($source);
						else $data = $GEO_database->getAll($destination);
						
						$country = $data->countryLong;
						if ($country == "-") $country = "(UNKNOWN)";
						
						$region = $data->region;
						if ($region == "-") $region = "(UNKNOWN)";
						
						$city = $data->city;
						if ($city == "-") $city = "(UNKNOWN)";
					} else if ($GEOLOCATION_DB == "MaxMind") {
						$GEO_database = geoip_open($MAXMIND_PATH, GEOIP_STANDARD);
						
						if ($j == 0) $record = geoip_record_by_addr($GEO_database, $source);
						else $record = geoip_record_by_addr($GEO_database, $destination);
						
						if (isset($record->country_name)) {
							$country = strtoupper($record->country_name);
						}
						if (!isset($country) || $country == "") $country = "(UNKNOWN)";

						if (isset($record->country_code) && isset($record->region)
								&& array_key_exists($record->country_code, $GEOIP_REGION_NAME)
								&& array_key_exists($record->region, $GEOIP_REGION_NAME[$record->country_code])) {
							$region = strtoupper($GEOIP_REGION_NAME[$record->country_code][$record->region]);
						}
						if (!isset($region) || $region == "") $region = "(UNKNOWN)";

						if (isset($record->city)) {
							$city = strtoupper($record->city);
						}
						if (!isset($city) || $city == "") $city = "(UNKNOWN)";
					} else {
						$country = "";
						$region = "";
						$city = "";
					}
					
					$country = fixCommaSeparatedNames(stripAccentedCharacters($country));
					$region = fixCommaSeparatedNames(stripAccentedCharacters($region));
					$city = fixCommaSeparatedNames(stripAccentedCharacters($city));					
					$GeoData[$i][$j] = array("COUNTRY" => $country, "REGION" => $region, "CITY" => $city);
					
					// Reset variables for next iteration
					unset($country, $region, $city);
				}
			}

			return $GeoData;
		}
		
		/*
		 * Retrieves data from the Geocoder caching database (optional).
		 */
		function retrieveDataGeocoderDB ($GeoData) {
			global $USE_GEOCODER_DB, $GEOCODER_DB_TABLE_NAME, $sessionData;
			
			$GeoCoderData = array();

			for ($i = 0; $i < count($GeoData); $i++) {
				$coordinates = new FlowCoordinates();

				for ($j = 0; $j < 2; $j++) { // source / destination
					foreach ($GeoData[$i][$j] as $key => $value) {
						// $key can be "COUNTRY", "REGION" or "CITY"
						$place = $GeoData[$i][$j][$key];
						if ($key === "COUNTRY") {
							$level = 0;
						} else if ($key === "REGION") {
							$level = 1;
						} else {
							$level = 2;
						}
						
						if ($place === "-" || stripos($place, "NKNOWN")) {
							/*
							 * Place name is undefined. If it concerns a country name,
							 * the whole entry will be ignored later on. If it concerns
							 * a region or city name, the coordinates of the layer on
							 * top of it will be used.
							 */
							$coordinates->writeVariable($j, $level, array(0, 0));
						} else if ($USE_GEOCODER_DB) {
							if ($key === "COUNTRY") {
								$entry = $place;
							} else if ($key === "REGION") {
								$entry = $GeoData[$i][$j]['COUNTRY'].", ".$GeoData[$i][$j]['REGION'];
							} else if (stripos($GeoData[$i][$j]['REGION'], "NKNOWN")) { // Region & city undefined
								$entry = $GeoData[$i][$j]['COUNTRY'].", ".$GeoData[$i][$j]['CITY'];
							} else { // Region & city undefined
								$entry = $GeoData[$i][$j]['COUNTRY'].", ".$GeoData[$i][$j]['REGION'].", ".$GeoData[$i][$j]['CITY'];
							}

							$queryResult = $this->GeocoderDatabase->query("SELECT latitude, longitude FROM geocoder WHERE location = ".$this->GeocoderDatabase->quote($entry));
							$row = $queryResult->fetch(PDO::FETCH_ASSOC);
							unset($queryResult);

							if ($row) { // Country name was found in our GeoCoder database
								$coordinates->writeVariable($j, $level, array($row['latitude'], $row['longitude']));
							} else {
								$coordinates->writeVariable($j, $level, array(-1, -1));
							}
						} else {
							$coordinates->writeVariable($j, $level, array(-1, -1));
						}
					}				
					unset($key, $value);
					
					$coordinates->srcHost = $coordinates->srcCity;
					$coordinates->dstHost = $coordinates->dstCity;
				}
				array_push($GeoCoderData, $coordinates);
			}
			
			// Check geocoder request history for current day
			if ($USE_GEOCODER_DB) {
				$queryResult = $this->GeocoderDatabase->query("SELECT * FROM history WHERE date = ".$this->GeocoderDatabase->quote(date("Y-m-d")));
				$row = $queryResult->fetch(PDO::FETCH_ASSOC);
				unset($queryResult);
				
				if ($row === false) { // No entry in DB
					$sessionData->geocoderRequestsSuccess = 0;
					$sessionData->geocoderRequestsError = 0;
					$sessionData->geocoderRequestsSkip = 0;
					$sessionData->geocoderRequestsBlock = 0;					
				} else {
					$sessionData->geocoderRequestsSuccess = $row['requestsSuccess'];
					$sessionData->geocoderRequestsError = $row['requestsError'];
					$sessionData->geocoderRequestsSkip = $row['requestsSkip'];
					$sessionData->geocoderRequestsBlock = $row['requestsBlock'];
				}
			}

			return $GeoCoderData;
		}
	}
	
	/*
	 * Reads settings from the NfSen configuration
	 * file nfsen.conf (example location: '/etc/nfsen.conf').
	 * In case paths are contained as a setting value, the last slash ('/)
	 * will be stripped. Returns an array with the setting tuples, or 'false'
	 * in case the NfSen config could not be read.
	 */		
	function readNfSenConfig () {
		global $NFSEN_CONF;

		$configValues = array();
		$comment = "#";

		if ($fp = fopen($NFSEN_CONF, "r")) {
			while (!feof($fp)) {
				$line = trim(fgets($fp));
				if ($line && !preg_match("/^$comment/", $line) && strpos($line, "=") && strpos($line, ";")) {
			    	$optionTuple = explode("=", $line);
					$option = substr(trim($optionTuple[0]), 1);
					$value = trim($optionTuple[1]);
					$value = substr($value, 0, strlen($value) - 1); // remove ';'

					$subVarPos = strpos($value, "\${");
					if ($subVarPos) {
						$subVarPos = $subVarPos;
						$subVar = substr($value, $subVarPos, strpos($value, "}", $subVarPos) - $subVarPos + 1);
						$value = str_replace($subVar, $configValues[substr($subVar, 2, strlen($subVar) - 3)], $value); // remove '${' and '}'
					}
					$value = str_replace("\"", "", $value);
					$value = str_replace("'", "", $value);
					$value = str_replace("//", "/", $value);
					
					if (substr($value, strlen($value) - 1) == "/") {
						$value = substr($value, 0, strlen($value) - 1);
					}
			    	$configValues[$option] = $value;
			 	}
			}
			fclose($fp);
		} else {
			syslog(LOG_INFO, "[SURFmap | ERROR] NfSen configuration file ($NFSEN_CONF) couldn't be found or opened. Please check for file existence and permissions");
		}

		return (sizeof($configValues) == 0) ? false : $configValues;
	}	
	
	/*
	 * Verify whether the source files for the specified time window(s) exist.
	 * Parameters:
	 *		source - name of the source
	 *		date - date in the following format 'YYYYMMDD'
	 *		hours - date in the following format 'HH' (with leading zeros)
	 *		minutes - date in the following format 'MM' (with leading zeros)
	 */
	function sourceFilesExist ($source, $date, $hours, $minutes) {
		global $nfsenConfig, $sessionData;
		
		// Use 'live' profile data if shadow profile has been selected
		if ($_SESSION['SURFmap']['nfsenProfileType'] === "real") {
			$actualProfile = $_SESSION['SURFmap']['nfsenProfile'];
			$actualSource = $source;
		} else {
			$actualProfile = "live";
			$actualSource = "*";
		}
		
		$directory = (substr($nfsenConfig['PROFILEDATADIR'], strlen($nfsenConfig['PROFILEDATADIR']) - 1) === "/") ? $nfsenConfig['PROFILEDATADIR'] : $nfsenConfig['PROFILEDATADIR']."/";
		$directory .= $actualProfile."/".$actualSource."/";
		
		$fileName = generateFileName($date, $hours, $minutes);
		$files = glob($directory.$fileName);
		
		return (count($files) >= 1 && @file_exists($files[0]));
	}	

	/*
	 * Removes special characters (e.g. tabs, EndOfTransmission).
	 */
	function stripSpecialCharacters ($text) {
		$prepared_text = "";
		
		// Remove unused characters.
		for ($i = 0; $i < strlen($text); $i++) {
			if (ord(substr($text, $i, 1)) < 32 || (ord(substr($text, $i, 1)) == 32 && ord(substr($prepared_text, strlen($prepared_text) - 1, 1)) == 32) || (ord(substr($text, $i, 1)) > 32 && ord(substr($text, $i, 1)) < 46) || (ord(substr($text, $i, 1)) > 58 && ord(substr($text, $i, 1)) < 65) || ord(substr($text, $i, 1)) > 122) {
				continue;
			} else {
				$prepared_text .= substr($text, $i, 1);
			}
		}
		
		return $prepared_text;
	}

	/*
	 * Replaces accented characters by their unaccented equivalents.
	 */
	function stripAccentedCharacters ($string) {
		$search =  explode(",","Ç,Ḉ,Æ,Œ,Á,É,Í,Ó,Ú,À,È,Ì,Ò,Ù,Ä,Ë,Ï,Ö,Ü,Ÿ,Â,Ê,Î,Ô,Ȗ,Å,Ã,Ñ,Ø,Ý,Ț,Ů,Ž,Č,Ď,Ě,Ň,Ř,Š,ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,ø,ý,ã,ñ");
		$replace = explode(",","C,C,AE,OE,A,E,I,O,U,A,E,I,O,U,A,E,I,O,U,Y,A,E,I,O,U,A,A,N,O,Y,T,U,Z,C,D,E,N,R,S,C,AE,OE,A,E,I,O,U,A,E,I,O,U,A,E,I,O,U,Y,A,E,I,O,U,A,O,Y,A,N");

		return str_replace($search, $replace, utf8_encode($string));
	}

	/*
	 * Changes comma-separated names to a non-comma-separated variant.
	 * Example: "KOREA, REPUBLIC OF" will be changed to "REPUBLIC OF KOREA".
	 * Parameters:
	 *		name - name that needs to be fixed.
	 */
	function fixCommaSeparatedNames ($name) {
		$commaPos = strpos($name, ",");
		if ($commaPos === false) {
			return $name;
		} else {
			$newName = substr($name, $commaPos + 2); // +2 to remove trailing white space
			$newName .= " ".substr($name, 0, $commaPos);
			return $newName;
		}
	}

?>