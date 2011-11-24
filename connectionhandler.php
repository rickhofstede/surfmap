<?php
	/*******************************
	 * connectionhandler.php [SURFmap]
	 * Author: Rick Hofstede
	 * University of Twente, The Netherlands
	 *******************************/
	
	require_once("geoPlugin/geoplugin.class.php");
	require_once("MaxMind/geoipcity.inc");
	require_once("IP2Location/ip2location.class.php");
	
	class ConnectionHandler {
		var $GeocoderDatabase;
		
		/**
		 * Constructs a new ConnectionHandler object.
		 */
		function __construct($logHandler) {
			global $USE_GEOCODER_DB, $GEOCODER_DB_SQLITE2, $GEOCODER_DB_SQLITE3;
			
			$this->logHandler = $logHandler;
			
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
			global $sessionData, $SORT_FLOWS_BY_START_TIME;
			
			// Queries
			if($_SESSION['SURFmap']['nfsenOption'] == 0) {
				$run = "-R nfcapd.".$_SESSION['SURFmap']['date1'].$_SESSION['SURFmap']['hours1'].$_SESSION['SURFmap']['minutes1'].
						":nfcapd.".$_SESSION['SURFmap']['date2'].$_SESSION['SURFmap']['hours2'].$_SESSION['SURFmap']['minutes2'].
						" -c ".$_SESSION['SURFmap']['entryCount'];
			} else {
				$run = "-R nfcapd.".$_SESSION['SURFmap']['date1'].$_SESSION['SURFmap']['hours1'].$_SESSION['SURFmap']['minutes1'].
						":nfcapd.".$_SESSION['SURFmap']['date2'].$_SESSION['SURFmap']['hours2'].$_SESSION['SURFmap']['minutes2'].
						" -n ".$_SESSION['SURFmap']['entryCount']." -s record/".$_SESSION['SURFmap']['nfsenStatOrder'].
						" -A proto,srcip,srcport,dstip,dstport";
			}

			if($_SESSION['SURFmap']['nfsenOption'] == 0 && $SORT_FLOWS_BY_START_TIME == 1) {
				$run .= " -m";
			}

			$cmd_opts['args'] = "-T $run -o long";
			$cmd_opts['profile'] = $_SESSION['SURFmap']['nfsenProfile'];
			$cmd_opts['type'] = $_SESSION['SURFmap']['nfsenProfileType'];
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
			
			$NetFlowData = array();
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

			return $NetFlowData;
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
	 * Reads settings from the NfSen configuration
	 * file nfsen.conf (example location: '/etc/nfsen.conf').
	 * In case paths are contained as a setting value, the last slash ('/)
	 * will be stripped. Returns an array with the setting tuples, or 'false'
	 * in case the NfSen config could not be read.
	 */		
	function readNfSenConfig() {
		global $NFSEN_CONF;

		$configValues = array();
		$comment = "#";

		if($fp = fopen($NFSEN_CONF, "r")) {
			while(!feof($fp)) {
				$line = trim(fgets($fp));
				if($line && !ereg("^$comment", $line) && strpos($line, "=") && strpos($line, ";")) {
			    	$optionTuple = explode("=", $line);
					$option = substr(trim($optionTuple[0]), 1);
					$value = trim($optionTuple[1]);
					$value = substr($value, 0, strlen($value) - 1); // remove ';'

					$subVarPos = strpos($value, "\${");
					if($subVarPos) {
						$subVarPos = $subVarPos;
						$subVar = substr($value, $subVarPos, strpos($value, "}", $subVarPos) - $subVarPos + 1);
						$value = str_replace($subVar, $configValues[substr($subVar, 2, strlen($subVar) - 3)], $value); // remove '${' and '}'
					}
					$value = str_replace("\"", "", $value);
					$value = str_replace("'", "", $value);
					$value = str_replace("//", "/", $value);
					
					if(substr($value, strlen($value) - 1) == "/") {
						$value = substr($value, 0, strlen($value) - 1);
					}
			    	$configValues[$option] = $value;
			 	}
			}
			fclose($fp);
		} else {
			error_log("[SURFmap | ERROR] NfSen configuration file ($NFSEN_CONF) couldn't be found or opened. Please check for file existence and permissions.");
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
	function sourceFilesExist($source, $date, $hours, $minutes) {
		global $nfsenConfig, $sessionData;
		
		// Use 'live' profile data if shadow profile has been selected
		if($_SESSION['SURFmap']['nfsenProfileType'] === "real") {
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
		$search =  explode(",","Ç,Ḉ,Æ,Œ,Á,É,Í,Ó,Ú,À,È,Ì,Ò,Ù,Ä,Ë,Ï,Ö,Ü,Ÿ,Â,Ê,Î,Ô,Ȗ,Å,Ã,Ñ,Ø,Ý,Ț,Ů,Ž,Č,Ď,Ě,Ň,Ř,Š,ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,ø,ý,ã,ñ");
		$replace = explode(",","C,C,AE,OE,A,E,I,O,U,A,E,I,O,U,A,E,I,O,U,Y,A,E,I,O,U,A,A,N,O,Y,T,U,Z,C,D,E,N,R,S,C,AE,OE,A,E,I,O,U,A,E,I,O,U,A,E,I,O,U,Y,A,E,I,O,U,A,O,Y,A,N");

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