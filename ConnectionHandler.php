<?php
	/*******************************
	 * ConnectionHandler.php [SURFmap]
	 * Author: Rick Hofstede
	 * University of Twente, The Netherlands
	 *******************************/
	
	class ConnectionHandler {
		var $GeocoderDatabase;
		
		/**
		 * Constructs a new ConnectionHandler object.
		 */
		function __construct() {
			global $USE_GEOCODER_DB, $GEOCODER_DB_SQLITE2, $GEOCODER_DB_SQLITE3;
			
			if($USE_GEOCODER_DB) {
				try {
					$PDODrivers = PDO::getAvailableDrivers();
					if(in_array("sqlite", $PDODrivers)) {
						$this->GeocoderDatabase = new PDO("sqlite:$GEOCODER_DB_SQLITE3");
					} else if(in_array("sqlite2", $PDODrivers)) {
						$this->GeocoderDatabase = new PDO("sqlite2:$GEOCODER_DB_SQLITE2");
					} else {
						
					}
				} catch(PDOException $e) {}
			}
		}
		
	   /**
		* Returns the NfSen query results.
		*/
		function retrieveDataNfSen() {
			global $sessionData, $DEMO_MODE, $DEFAULT_QUERY_TYPE, $DEFAULT_QUERY_TYPE_STAT_ORDER,
				   	$GEOLOCATION_DB, $INTERNAL_DOMAINS, $HIDE_INTERNAL_DOMAIN_TRAFFIC, 
					$NFSEN_DEFAULT_SOURCES, $NFSEN_PRIMARY_SRC_SELECTOR, $NFSEN_ADDITIONAL_SRC_SELECTORS,
					$SORT_FLOWS_BY_START_TIME, $DEFAULT_FLOW_RECORD_COUNT, $infoLogQueue, $errorLogQueue;
			$NetFlowData = array();

			// Queries
			if($_SESSION['SURFmap']['nfsenOption'] == 0) {
				$run = " -R nfcapd.".$_SESSION['SURFmap']['date1'].$_SESSION['SURFmap']['hours1'].$_SESSION['SURFmap']['minutes1'].
						":nfcapd.".$_SESSION['SURFmap']['date2'].$_SESSION['SURFmap']['hours2'].$_SESSION['SURFmap']['minutes2'].
						" -c ".$_SESSION['SURFmap']['entryCount'];
			} else {
				$run = " -R nfcapd.".$_SESSION['SURFmap']['date1'].$_SESSION['SURFmap']['hours1'].$_SESSION['SURFmap']['minutes1'].
						":nfcapd.".$_SESSION['SURFmap']['date2'].$_SESSION['SURFmap']['hours2'].$_SESSION['SURFmap']['minutes2'].
						" -n ".$_SESSION['SURFmap']['entryCount']." -s record/".$_SESSION['SURFmap']['nfsenStatOrder'].
						" -A proto,srcip,srcport,dstip,dstport";
			}

			if($_SESSION['SURFmap']['nfsenOption'] == 0 && $SORT_FLOWS_BY_START_TIME == 1) {
				$run .= " -m";
			}

			$cmd_opts['args'] = "-T $run -o long";
			$cmd_opts['profile'] = $_SESSION['profileswitch'];
			$cmd_opts['type'] = ($_SESSION['profileinfo']['type'] & 4) > 0 ? 'shadow' : 'real';
			$cmd_opts['srcselector'] = $_SESSION['SURFmap']['nfsenSelectedSources'];	
			$cmd_opts['filter'] = array($_SESSION['SURFmap']['filter']);

			// Execute NfSen query
			$cmd_out = nfsend_query("run-nfdump", $cmd_opts);
			$sessionData->query = "** nfdump ".$cmd_out['arg'];
			
			if(isset($cmd_out['nfdump']) && $cmd_out["exit"] > 0) {
				$sessionData->errorCode = 1; // filter error
				
				if(count($cmd_out['nfdump']) > 0) {
					$sessionData->errorMessage = $cmd_out['nfdump'][0];
				}
				
				$sessionData->flowRecordCount = 0;
				return;
			} else if(isset($_SESSION['error']) && isset($_SESSION['error'][0])) {
				$sessionData->errorCode = 6; // profile error
				$sessionData->errorMessage = $_SESSION['error'][0];
				$sessionData->flowRecordCount = 0;
				return;				
			} else if(!isset($cmd_out['nfdump']) || sizeof($cmd_out['nfdump']) == 1) {
				$sessionData->errorCode = 5; // no flow records error
				$sessionData->flowRecordCount = 0;
				return;
			}

			if($_SESSION['SURFmap']['nfsenOption'] == 0) { // List flows
				// Calculate flowRecordCount, for the case that less flow records are returned than the actual entryCount
				$sessionData->flowRecordCount = sizeof($cmd_out['nfdump']) - 5; // Five lines are always present (header & footer)
				
				for($i = 1; $i <= $sessionData->flowRecordCount; $i++) {
					$flow = new NetFlowFlow();
					$line_array = split(" ", stripSpecialCharacters($cmd_out['nfdump'][$i]));

					$factor_array = array();
					if(sizeof($line_array) > 11) {
						for($j = 8; $j < sizeof($line_array); $j++) {
							if(!is_numeric($line_array[$j])) array_push($factor_array, $j);
						}
					}

					$hosts[0] = split(":", $line_array[4]);
					$hosts[1] = split(":", $line_array[5]);
					$flow->ipv4_src = $hosts[0][0];
					$flow->ipv4_dst = $hosts[1][0];

					// Handle situation in which either packets, octets or both have a MEGA or GIGA factor
					if(sizeof($factor_array) == 1) {
						if($line_array[$factor_array[0]] == "M") {
							$line_array[$factor_array[0] - 1] = $line_array[$factor_array[0] - 1] * 1000000;
						} else if($line_array[$factor_array[0]] == "G") {
							$line_array[$factor_array[0] - 1] = $line_array[$factor_array[0] - 1] * 1000000000;
						}

						if($factor_array[0] > 9) { // 'Bytes' has factor
							$flow->packets = $line_array[8];
							$flow->octets = $line_array[9];
						} else { // 'Packets' has factor
							$flow->packets = $line_array[8];
							$flow->octets = $line_array[10];
						}

					} else if(sizeof($factor_array) == 2) {
						for($k = 0; $k < sizeof($factor_array); $k++) {			
							if($line_array[$factor_array[$k]] == "M") {
								$line_array[$factor_array[$k] - 1] = $line_array[$factor_array[$k] - 1] * 1000000;
							} else if($line_array[$factor_array[$k]] == "G") {
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
				
				for($i = 3; $i < $sessionData->flowRecordCount + 3; $i++) {
					$flow = new NetFlowFlow();

					$line_array = split(" ", stripSpecialCharacters($cmd_out['nfdump'][$i]));

					$factor_array = array();
					if(sizeof($line_array) > 11) {
						for($j = 8; $j < sizeof($line_array); $j++) {
							if(!is_numeric($line_array[$j])) array_push($factor_array, $j);
						}
					}

					$hosts[0] = split(":", $line_array[4]);
					$hosts[1] = split(":", $line_array[5]);
					$flow->ipv4_src = $hosts[0][0];
					$flow->ipv4_dst = $hosts[1][0];

					// Handle situation in which either packets, octets or both have a GIGA or MEGA factor
					if(sizeof($factor_array) == 1) {
						if($line_array[$factor_array[0]] == "M") {
							$line_array[$factor_array[0] - 1] = $line_array[$factor_array[0] - 1] * 1000000;
						} else if($line_array[$factor_array[0]] == "G") {
							$line_array[$factor_array[0] - 1] = $line_array[$factor_array[0] - 1] * 1000000000;
						}

						if($factor_array[0] > 9) { // 'Bytes' has factor
							$flow->packets = $line_array[8];
							$flow->octets = $line_array[9];
						} else { // 'Packets' has factor
							$flow->packets = $line_array[8];
							$flow->octets = $line_array[10];
						}

					} else if(sizeof($factor_array) == 2) {
						for($k = 0; $k < sizeof($factor_array); $k++) {			
							if($line_array[$factor_array[$k]] == "M") {
								$line_array[$factor_array[$k] - 1] = $line_array[$factor_array[$k] - 1] * 1000000;
							} else if($line_array[$factor_array[$k]] == "G") {
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

			$nfsen_details = "";
			if(is_array($cmd_out['nfdump'])) {
				foreach($cmd_out['nfdump'] as $line) {
					$nfsen_details = $nfsen_details.$line."<br>";
				}
			}

			$sessionData->NetFlowData = $NetFlowData;
			$sessionData->nfsenDetails = stripASCIISOH($nfsen_details);
		}
		
		/**
		 * Retrieves data from a Geolocation data provider, which is
		 * in the case of SURFmap either 'IP2Location' or 'GeoPlugin'.
		 */
		function retrieveDataGeolocation($entryCount, $NetFlowData) {
			global $GEOLOCATION_DB, $IP2LOCATION_PATH, $MAXMIND_PATH, $GEOIP_REGION_NAME, // $GEOIP_REGION_NAME is part of the MaxMind API
					$INTERNAL_DOMAINS, $INTERNAL_DOMAINS_COUNTRY, $INTERNAL_DOMAINS_REGION, $INTERNAL_DOMAINS_CITY;

			$GeoData = array();
			$internalDomainNets = explode(";", $INTERNAL_DOMAINS);

			for($i = 0; $i < $entryCount; $i++) {
				$source = $NetFlowData[$i]->ipv4_src;
				$destination = $NetFlowData[$i]->ipv4_dst;

				/*
				 * Check whether a NATed setup was used. If so, use the geolocation data provided
				 * in the configuration file. Otherwise, use a geolocation service.
				 */
				$srcNAT = false;
				$dstNAT = false;
				
				foreach($internalDomainNets as $subNet) {
					if(ipAddressBelongsToNet($source, $subNet)) {
						$srcNAT = true;
						break;
					}
				}
				foreach($internalDomainNets as $subNet) {
					if(ipAddressBelongsToNet($destination, $subNet)) {
						$dstNAT = true;
						break;
					}
				}
				
				for($j = 0; $j < 2; $j++) { // Source and destination
					if(($j == 0 && $srcNAT === true) || ($j == 1 && $dstNAT === true)) { // Source or destination uses a NATed setup
						$country = strtoupper($INTERNAL_DOMAINS_COUNTRY);
						if($country == "") $country = "(Unknown)";
						$region = strtoupper($INTERNAL_DOMAINS_REGION);
						if($region == "") $region = "(Unknown)";
						$city = strtoupper($INTERNAL_DOMAINS_CITY);
						if($city == "") $city = "(Unknown)";
					} else if($GEOLOCATION_DB == "IP2Location") {
						$GEO_database = new ip2location();
						$GEO_database->open($IP2LOCATION_PATH);
						
						if($j == 0) $data = $GEO_database->getAll($source);
						else $data = $GEO_database->getAll($destination);
						
						$country = $data->countryLong;
						if($country == "-") $country = "(Unknown)";
						$region = $data->region;
						if($region == "-") $region = "(Unknown)";
						$city = $data->city;
						if($city == "-") $city = "(Unknown)";
					} else if($GEOLOCATION_DB == "geoPlugin") {
						$GEO_database = new geoPlugin();
						
						if($j == 0) $GEO_database->locate($source);
						else $GEO_database->locate($destination);

						$country = strtoupper($GEO_database->countryName);
						if($country == "") $country = "(Unknown)";
						$region = strtoupper($GEO_database->region);
						if($region == "") $region = "(Unknown)";
						$city = strtoupper($GEO_database->city);
						if($city == "") $city = "(Unknown)";
					} else if($GEOLOCATION_DB == "MaxMind") {
						$GEO_database = geoip_open($MAXMIND_PATH, GEOIP_STANDARD);
						
						if($j == 0) $record = geoip_record_by_addr($GEO_database, $source);
						else $record = geoip_record_by_addr($GEO_database, $destination);
						
						if(isset($record->country_name)) {
							$country = strtoupper($record->country_name);
						}
						if(!isset($country) || $country == "") $country = "(Unknown)";

						if(isset($record->country_code) && isset($record->region)
								&& array_key_exists($record->country_code, $GEOIP_REGION_NAME)
								&& array_key_exists($record->region, $GEOIP_REGION_NAME[$record->country_code])) {
							$region = strtoupper($GEOIP_REGION_NAME[$record->country_code][$record->region]);
						}
						if(!isset($region) || $region == "") $region = "(Unknown)";

						if(isset($record->city)) {
							$city = strtoupper($record->city);
						}
						if(!isset($city) || $city == "") $city = "(Unknown)";
					} else {
						$country = "";
						$region = "";
						$city = "";
					}
					
					$GeoData[$i][$j] = array("COUNTRY" => stripAccentedCharacters($country), "REGION" => stripAccentedCharacters($region), "CITY" => stripAccentedCharacters($city));
					$country = "";
					$region = "";
					$city = "";
				}
			}

			return $GeoData;
		}
		
		/**
		 * Retrieves data from the Geocoder caching database (optional).
		 */
		function retrieveDataGeocoderDB($GeoData, $entryCount) {
			global $USE_GEOCODER_DB, $GEOCODER_DB_TABLE_NAME;
			
			$GeoCoderData = array();

			for($i = 0; $i < $entryCount; $i++) { // all considered flows ($entryCount)
				$coordinates = new FlowCoordinates();

				for($j = 0; $j < 2; $j++) { // source / destination
					// Country
					$countryName = $GeoData[$i][$j]['COUNTRY'];
					if($countryName != "-") { // country name is defined
						if($USE_GEOCODER_DB) {
							$queryResult = $this->GeocoderDatabase->query("SELECT latitude, longitude FROM geocoder WHERE location = ".$this->GeocoderDatabase->quote($countryName));
							$row = $queryResult->fetch(PDO::FETCH_ASSOC);

							if($row) { // Country name was found in our GeoCoder database
								$coordinates->writeVariable($j, 0, array($row['latitude'], $row['longitude']));
							} else {
								$coordinates->writeVariable($j, 0, array(-1, -1));
							}
						} else {
							$coordinates->writeVariable($j, 0, array(-1, -1));
						}
					} else { // country name is undefined; this entry will be ignored later on
						$coordinates->writeVariable($j, 0, array(0, 0));
					}

					// Region
					$regionName = $GeoData[$i][$j]['REGION'];
					if($regionName != "-") { // region name is defined 
						if($USE_GEOCODER_DB) {
							$queryResult = $this->GeocoderDatabase->query("SELECT latitude, longitude FROM geocoder WHERE location = ".$this->GeocoderDatabase->quote($countryName.", ".$regionName));
							$row = $queryResult->fetch(PDO::FETCH_ASSOC);
							
							if($row) { // Region name was found in our GeoCoder database
								$coordinates->writeVariable($j, 1, array($row['latitude'], $row['longitude']));
							} else {
								$coordinates->writeVariable($j, 1, array(-1, -1));
							}
						} else {
							$coordinates->writeVariable($j, 1, array(-1, -1));
						}
					} else { // region name is undefined, taking country's coordinates
						$coordinates->writeVariable($j, 1, array(0, 0));
					}

					// City
					$cityName = $GeoData[$i][$j]['CITY'];
					if($cityName != "-") { // city name is defined	
						if($USE_GEOCODER_DB) {
							$queryResult = $this->GeocoderDatabase->query("SELECT latitude, longitude FROM geocoder WHERE location = ".$this->GeocoderDatabase->quote($countryName.", ".$cityName));
							$row = $queryResult->fetch(PDO::FETCH_ASSOC);
							
							if($row) { // City name was found in our GeoCoder database
								$coordinates->writeVariable($j, 2, array($row['latitude'], $row['longitude']));
							} else {
								$coordinates->writeVariable($j, 2, array(-1, -1));
							}
						} else {
							$coordinates->writeVariable($j, 2, array(-1, -1));
						}
					} else { // city name is undefined, taking region's coordinates
						$coordinates->writeVariable($j, 2, array(0, 0));
					}
					
					$coordinates->srcHost = $coordinates->srcCity;
					$coordinates->dstHost = $coordinates->dstCity;
				}
				array_push($GeoCoderData, $coordinates);
			}

			return $GeoCoderData;
		}
		
	}
	
	/**
	 * Generates a date String (yyyymmdd) from either 1) a date selector in the
	 * SURFmap interface, or 2) the last available date for which an nfcapd dump
	 * file is available on the file system.
	 * Parameters:
	 *		bufferTime - buffer time between the real time and the most recent
	 *						profile update, in minutes (default: 5)
	 */
	function generateDateString($bufferTime) {
		$unprocessed_date = date("Ymd");

		// If time is in interval [00:00, 00:{bufferTime}>, the date has to contain the previous day (and eventually month and year)
		if(date("H") == 00 && date("i") < $bufferTime) {
			$year = substr($unprocessed_date, 0, 4);
			$month = substr($unprocessed_date, 4, 2);
			$day = substr($unprocessed_date, 6, 2);

			if($month == 01 && $day == 01) {
				$year--;
				$month = 12;
				$day = 31;
			} else if(checkdate($month, $day - 1, $year)) {
				$day--;
			} else if(checkdate($month - 1, 31, $year)) {
				$day = 31;
				$month--;
			} else if(checkdate($month - 1, 30, $year)) {
				$day = 30;
				$month--;
			} else if(checkdate($month - 1, 29, $year)) {
				$day = 29;
				$month--;
			} else if(checkdate($month - 1, 28, $year)) {
				$day = 28;
				$month--;
			}

			if(strlen($day) < 2) $day = "0".$day;
			if(strlen($month) < 2) $month = "0".$month;

			$date = $year.$month.$day;
		} else {
			$date = $unprocessed_date;
		}
		
		return $date;
	}

	/**
	 * Generates a time String (hhmm) from either 1) a time selector in the
	 * SURFmap interface, or 2) the last available time for which an nfcapd dump
	 * file is available on the file system.
	 * Parameters:
	 *		bufferTime - buffer time between the real time and the most recent
	 *						profile update, in minutes (default: 5)
	 */
	function generateTimeString($bufferTime) {
		$hours = date("H");
		$minutes = date("i") - (date("i") % 5);

		if($minutes < $bufferTime) {
			if($hours != 00) {
				$hours--; // 'previous' hour of "00" is "23"
			} else {
				$hours = 23;
			}

			$minutes = 60 - ($bufferTime - $minutes);
		} else {
			$minutes = $minutes - $bufferTime;
		}
		
		if(strlen($hours) < 2) $hours = "0".$hours;
		if(strlen($minutes) < 2) $minutes = "0".$minutes;

		return $hours.":".$minutes;
	}

	/**
	 * Generates a file name based on the specified file name format (in config.php)
	 * and the specified parameters.
	 * Parameters:
	 *		date - Date for the file name (should be of the following format: yyyyMMdd)
	 *		hours - Hours for the file name (should be of the following format: hh)
	 *		minutes - Minutes for the file name (should be of the following format: mm)
	 */
	function generateFileName($date, $hours, $minutes) {
		global $NFSEN_SUBDIR_LAYOUT;
		
		$year = substr($date, 0, 4);
		$month = substr($date, 4, 2);
		$day = substr($date, 6, 2);
		
		/*
		 Possible layouts:
		 0 		       no hierachy levels - flat layout
		 1 %Y/%m/%d    year/month/day
		 2 %Y/%m/%d/%H year/month/day/hour
		*/
		switch($NFSEN_SUBDIR_LAYOUT) {
			case 0:		$fileName = "nfcapd.".$date.$hours.$minutes;
						break;
						
			case 1:		$fileName = $year."/".$month."/".$day."/nfcapd.".$date.$hours.$minutes;
						break;
						
			case 2:		$fileName = $year."/".$month."/".$day."/".$hours."/nfcapd.".$date.$hours.$minutes;
						break;
					
			default:	$fileName = "nfcapd.".$date.$hours.$minutes;
						break;
		}
		
		return $fileName;
	}
	
	/**
	 * Checks whether the 2nd timestamp is later (in time) than the first timestamp.
	 */	
	function isTimeRangeIsPositive($date1, $hours1, $minutes1, $date2, $hours2, $minutes2) {
		$result = false;

		// the resulting time stamp is in GMT (instead of GMT+1), but that shouldn't be a problem; only the difference between the time stamps is important
		if(mktime($hours1, $minutes1, 0, substr($date1, 4, 2), substr($date1, 6, 2), 
				substr($date1, 0, 4)) <= mktime($hours2, $minutes2, 0, substr($date2, 4, 2), substr($date2, 6, 2), 
				substr($date2, 0, 4))) {
			$result = true;		
		}
		
		return $result;
	}

	/**
	 * Checks whether the specified IPv4 address belongs to the specified IP
	 * address range (net).
	 * Parameters:
	 *		ipAddress - IPv4 address in octet notation (e.g. '192.168.1.1')
	 * 		ipNet - IPv4 subnet range, in nfdump filter notation
	 */
	function ipAddressBelongsToNet($ipAddress, $ipNet) {
		if(substr_count($ipAddress, ".") != 3) return false; // A valid IPv4 address should have 3 dots
		if(substr_count($ipAddress, ".") < 1 && substr_count($ipAddress, "/") != 1) return false; // A valid IPv4 subNet should have at least 1 dot and exactly 1 slash
		
		$ipAddressOctets = explode(".", $ipAddress);
		$netMaskIndex = strpos($ipNet, "/");
		
		// Add ".0" in order to obtain a subnet notation with 3 dots
		for($i = 0; $i < (3 - substr_count($ipAddress, ".")); $i++) {
			str_replace("/", ".0/", $ipNet);
		}

		$subNetOctets = explode(".", substr($ipNet, 0, $netMaskIndex));
		$netMask = intval(substr($ipNet, $netMaskIndex + 1));
		
		$completeOctets = floor($netMask / 8); // Check all 'complete' octets
		for($i = 0; $i < $completeOctets; $i++) {
			if($ipAddressOctets[$i] != $subNetOctets[$i]) {
				return false;
			}
		}
		
		$incompleteOctetSize = $netMask % 8; // Check whether an 'incomplete' octet is present in the net mask and what its size is
		if(($incompleteOctetSize) > 0) {
			$binIPAddress = decbin($ipAddressOctets[$completeOctets]);
			$binSubNet = decbin($subNetOctets[$completeOctets]);
			
			if(bindec(substr(decbin($ipAddressOctets[$completeOctets]), 0, $incompleteOctetSize)) !=
					bindec(substr(decbin($subNetOctets[$completeOctets]), 0, $incompleteOctetSize))) return false;
		}

		return true;
	}

	/*
	 * Verify whether the source files for the specified time window(s) exist.
	 * Parameters:
	 *		source - name of the source
	 *		date - date in the following format 'YYYYMMDD'
	 *		hours - date in the following format 'HH' (with leading zeros)
	 *		minutes - date in the following format 'MM' (with leading zeros)
	 */
	function sourceFilesExist($source, $date, $hours, $minutes) {
		global $NFSEN_SOURCE_DIR, $sessionData;
		
		// Use 'live' profile data if shadow profile has been selected
		if($sessionData->nfsenProfileType === "real") {
			$actualProfile = $sessionData->nfsenProfile;
			$actualSource = $source;
		} else {
			$actualProfile = "live";
			$actualSource = "*";
		}
		
		$directory = (substr($NFSEN_SOURCE_DIR, strlen($NFSEN_SOURCE_DIR) - 1) === "/") ? $NFSEN_SOURCE_DIR : $NFSEN_SOURCE_DIR."/";
		$directory .= $actualProfile."/".$actualSource."/";
		
		$fileName = generateFileName($date, $hours, $minutes);
		$files = glob($directory.$fileName);
		
		return (count($files) >= 1 && @file_exists($files[0]));
	}

	/**
	 * Removes special characters (e.g. tabs, EndOfTransmission).
	 */
	function stripSpecialCharacters($text) {
		$prepared_text = "";
		
		// Remove unused characters.
		for($i = 0; $i < strlen($text); $i++) {
			if(ord(substr($text, $i, 1)) < 32 || (ord(substr($text, $i, 1)) == 32 && ord(substr($prepared_text, strlen($prepared_text) - 1, 1)) == 32) || (ord(substr($text, $i, 1)) > 32 && ord(substr($text, $i, 1)) < 46) || (ord(substr($text, $i, 1)) > 58 && ord(substr($text, $i, 1)) < 65) || ord(substr($text, $i, 1)) > 122) continue;
			else $prepared_text .= substr($text, $i, 1);
		}
		
		return $prepared_text;
	}

	/**
	 * Replaces accented characters by their unaccented equivalents.
	 */
	function stripAccentedCharacters($string) {
		$search =  explode(",","Ç,Ḉ,Æ,Œ,Á,É,Í,Ó,Ú,À,È,Ì,Ò,Ù,Ä,Ë,Ï,Ö,Ü,Ÿ,Â,Ê,Î,Ô,Ȗ,Å,Ã,Ø,Ý,Ț,Ů,Ž,Č,Ď,Ě,Ň,Ř,Š,ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,ø,ý,ã");
		$replace = explode(",","C,C,AE,OE,A,E,I,O,U,A,E,I,O,U,A,E,I,O,U,Y,A,E,I,O,U,A,A,O,Y,T,U,Z,C,D,E,N,R,S,C,AE,OE,A,E,I,O,U,A,E,I,O,U,A,E,I,O,U,Y,A,E,I,O,U,A,O,Y,A");

		return str_replace($search, $replace, utf8_encode($string));
	}

	/**
	 * Removes all characters which have an ASCII value of 1.
	 */
	function stripASCIISOH($details) {
		$prepared_text = "";
		
		for($i = 0; $i < strlen($details); $i++) {
			if(ord(substr($details, $i, 1)) == 1) continue;
			else $prepared_text .= substr($details, $i, 1);
		}
		
		return $prepared_text;
	}

?>