<?php
	/*******************************
	 * sessionhandler.php [SURFmap]
	 * Author: Rick Hofstede
	 * University of Twente, The Netherlands
	 *******************************/
	
	class SessionHandler {
		
		/**
		 * Constructs a new SessionHandler object.
		 */
		function __construct($logHandler, $profile = "", $profileType = "", $allSources = "") {
			global $sessionData;
			
			$this->logHandler = $logHandler;

			if(!session_id()) session_start();
			
			// Prepare session variable
			if(!isset($_SESSION['SURFmap']['entryCount'])) $_SESSION['SURFmap']['entryCount'] = -1;
			if(!isset($_SESSION['SURFmap']['filter'])) $_SESSION['SURFmap']['filter'] = "";
			if(!isset($_SESSION['SURFmap']['nfsenOption'])) $_SESSION['SURFmap']['nfsenOption'] = -1;
			if(!isset($_SESSION['SURFmap']['nfsenStatOrder'])) $_SESSION['SURFmap']['nfsenStatOrder'] = "-1";
			if(!isset($_SESSION['SURFmap']['nfsenProfile'])) $_SESSION['SURFmap']['nfsenProfile'] = "";
			if(!isset($_SESSION['SURFmap']['nfsenProfileType'])) $_SESSION['SURFmap']['nfsenProfileType'] = "";
			if(!isset($_SESSION['SURFmap']['nfsenAllSources'])) $_SESSION['SURFmap']['nfsenAllSources'] = "";
			if(!isset($_SESSION['SURFmap']['nfsenSelectedSources'])) $_SESSION['SURFmap']['nfsenSelectedSources'] = "";
			if(!isset($_SESSION['SURFmap']['nfsenPreviousProfile'])) $_SESSION['SURFmap']['nfsenPreviousProfile'] = "";
			if(!isset($_SESSION['SURFmap']['refresh'])) $_SESSION['SURFmap']['refresh'] = 0;
			
			if(!isset($_SESSION['SURFmap']['date1'])) $_SESSION['SURFmap']['date1'] = "-1";
			if(!isset($_SESSION['SURFmap']['date2'])) $_SESSION['SURFmap']['date2'] = "-1";
			if(!isset($_SESSION['SURFmap']['hours1'])) $_SESSION['SURFmap']['hours1'] = "-1";
			if(!isset($_SESSION['SURFmap']['hours2'])) $_SESSION['SURFmap']['hours2'] = "-1";
			if(!isset($_SESSION['SURFmap']['minutes1'])) $_SESSION['SURFmap']['minutes1'] = "-1";
			if(!isset($_SESSION['SURFmap']['minutes2'])) $_SESSION['SURFmap']['minutes2'] = "-1";
			if(!isset($_SESSION['SURFmap']['mapCenter'])) $_SESSION['SURFmap']['mapCenter'] = "-1";
			if(!isset($_SESSION['SURFmap']['zoomLevel'])) $_SESSION['SURFmap']['zoomLevel'] = "-1"; // Google Maps zoom level

			// Prevent frontend from refreshing page every 5 minutes
			$_SESSION['refresh'] = 0;
			
			if($profile !== "" && $profileType !== "" && $allSources !== "") {
				$_SESSION['SURFmap']['nfsenProfile'] = $profile;
				$_SESSION['SURFmap']['nfsenProfileType'] = ($profileType & 4) > 0 ? "shadow" : "real";
				$_SESSION['SURFmap']['nfsenAllSources'] = $allSources;
			}
			
			$this->setEntryCount();
			$this->setNfSenOption();
			$this->setNfSenStatOrder();
			$this->setFilter();
			$this->setNfSenProfileAndSources();
			$this->setDatesAndTimes();

			session_write_close();
		}
		
		/**
		 * Writes the 'entryCount' for this session to the session variable.
		 */		
		function setEntryCount() {
			global $DEFAULT_FLOW_RECORD_COUNT, $GEOLOCATION_DB;
			
			if(PHP_SAPI === "cli") {
				$_SESSION['SURFmap']['entryCount'] = 500;
			} else if(isset($_GET['amount']) && ereg_replace("[^0-9]", "", $_GET['amount']) > 0) {
				$_SESSION['SURFmap']['entryCount'] = ereg_replace("[^0-9]", "", $_GET['amount']);
			} else if($_SESSION['SURFmap']['entryCount'] == -1) { // initialization value
				if($DEFAULT_FLOW_RECORD_COUNT > 0) {
					$_SESSION['SURFmap']['entryCount'] = $DEFAULT_FLOW_RECORD_COUNT;
				} else if($GEOLOCATION_DB == "IP2Location" || $GEOLOCATION_DB == "MaxMind") {
					$_SESSION['SURFmap']['entryCount'] = 50;
				} else {
					$_SESSION['SURFmap']['entryCount'] = 20;
				}
			}
		}
		
		/**
		 * Writes the 'nfsenOption' for this session to the session variable.
		 */		
		function setNfSenOption() {
			global $DEFAULT_QUERY_TYPE;
			
			if(PHP_SAPI === "cli") {
				$_SESSION['SURFmap']['nfsenOption'] = 0;
			} else if(isset($_GET['nfsenoption'])) {
				$_SESSION['SURFmap']['nfsenOption'] = $_GET['nfsenoption'];
			} else if($_SESSION['SURFmap']['nfsenOption'] == -1) { // initialization value
				$_SESSION['SURFmap']['nfsenOption'] = $DEFAULT_QUERY_TYPE;
			}
		}
		
		/**
		 * Writes the 'nfsenStatOrder' for this session to the session variable.
		 */		
		function setNfSenStatOrder() {
			global $DEFAULT_QUERY_TYPE_STAT_ORDER;
			
			if(isset($_GET['nfsenoption']) && $_GET['nfsenoption'] == 1 && isset($_GET['nfsenstatorder'])) {
				$_SESSION['SURFmap']['nfsenStatOrder'] = $_GET['nfsenstatorder'];
			} else if($_SESSION['SURFmap']['nfsenStatOrder'] == "-1") { // initialization value
				$_SESSION['SURFmap']['nfsenStatOrder'] = $DEFAULT_QUERY_TYPE_STAT_ORDER;
			}
		}
		
		/**
		 * Writes the 'filter' for this session to the session variable.
		 */		
		function setFilter() {
			global $INTERNAL_DOMAINS, $HIDE_INTERNAL_DOMAIN_TRAFFIC, $sessionData;
			
			if(isset($_GET['filter'])) {
				$_SESSION['SURFmap']['filter'] = $_GET['filter'];
				$_SESSION['SURFmap']['filter'] = str_replace(";", "", $_SESSION['SURFmap']['filter']);
			}
			
			// If filter input contains "ipv6", change it to "not ipv6"
			if(strpos($_SESSION['SURFmap']['filter'], "ipv6") && !strpos($_SESSION['SURFmap']['filter'], "not ipv6")) {
				$_SESSION['SURFmap']['filter'] = substr_replace($_SESSION['SURFmap']['filter'], "not ipv6", strpos($_SESSION['SURFmap']['filter'], "ipv6"));
			}
			
			// ***** 1. Prepare filters *****
			if(strlen($INTERNAL_DOMAINS) != 0) {
				$internalDomains = explode(";", $INTERNAL_DOMAINS);
				foreach ($internalDomains as $domain) {
					if(isset($static_filter_internal_domain_traffic)) {
						$static_filter_internal_domain_traffic .= " and not (src net ".$domain." and dst net ".$domain.")";
					} else {
						$static_filter_internal_domain_traffic = "not (src net ".$domain." and dst net ".$domain.")";
					}
				}
			}

			$static_filter_broadcast_traffic = "not host 255.255.255.255";
			$static_filter_multicast_traffic = "not net 224.0/4";
			$static_filter_ipv6_traffic = "not ipv6";
			$static_filters = array();
			
			// ***** 2. Collect filters if needed *****
			if($HIDE_INTERNAL_DOMAIN_TRAFFIC && isset($static_filter_internal_domain_traffic) && strpos($_SESSION['SURFmap']['filter'], $static_filter_internal_domain_traffic) === false) {
				array_push($static_filters, $static_filter_internal_domain_traffic);
			}
			if(strpos($_SESSION['SURFmap']['filter'], $static_filter_broadcast_traffic) === false) {
				array_push($static_filters, $static_filter_broadcast_traffic);
			}
			if(strpos($_SESSION['SURFmap']['filter'], $static_filter_multicast_traffic) === false) {
				array_push($static_filters, $static_filter_multicast_traffic);
			}
			if(strpos($_SESSION['SURFmap']['filter'], $static_filter_ipv6_traffic) === false) {
				array_push($static_filters, $static_filter_ipv6_traffic);
			}

			$combined_static_filter = "";
			for($i = 0; $i < sizeof($static_filters); $i++) {
				if(strlen($combined_static_filter) == 0) {
					$combined_static_filter = $static_filters[$i];
				} else {
					$combined_static_filter .= " and ".$static_filters[$i];
				}
			}
			
			if(sizeof($static_filters) > 0) {
				if($_SESSION['SURFmap']['filter'] == "") {
					$_SESSION['SURFmap']['filter'] = $combined_static_filter;
				} else {
					$_SESSION['SURFmap']['filter'].= " and ".$combined_static_filter;
				}
			}
			
			// ***** 3. Remove static filters from display filter *****
			/*
			 * This should be done separately from the procedures above,
			 * since the static filters can also have been passed by HTTP GET
			 */
			$sessionData->nfsenDisplayFilter = $_SESSION['SURFmap']['filter'];
			if(strpos($sessionData->nfsenDisplayFilter, $static_filter_internal_domain_traffic) === 0) {
				$sessionData->nfsenDisplayFilter = str_replace($static_filter_internal_domain_traffic, "", $sessionData->nfsenDisplayFilter);
			} else {
				$sessionData->nfsenDisplayFilter = str_replace(" and ".$static_filter_internal_domain_traffic, "", $sessionData->nfsenDisplayFilter);
			}
			if(strpos($sessionData->nfsenDisplayFilter, $static_filter_broadcast_traffic) === 0) {
				$sessionData->nfsenDisplayFilter = str_replace($static_filter_broadcast_traffic, "", $sessionData->nfsenDisplayFilter);
			} else {
				$sessionData->nfsenDisplayFilter = str_replace(" and ".$static_filter_broadcast_traffic, "", $sessionData->nfsenDisplayFilter);
			}
			if(strpos($sessionData->nfsenDisplayFilter, $static_filter_multicast_traffic) === 0) {
				$sessionData->nfsenDisplayFilter = str_replace($static_filter_multicast_traffic, "", $sessionData->nfsenDisplayFilter);
			} else {
				$sessionData->nfsenDisplayFilter = str_replace(" and ".$static_filter_multicast_traffic, "", $sessionData->nfsenDisplayFilter);
			}
			if(strpos($sessionData->nfsenDisplayFilter, $static_filter_ipv6_traffic) === 0) {
				$sessionData->nfsenDisplayFilter = str_replace($static_filter_ipv6_traffic, "", $sessionData->nfsenDisplayFilter);
			} else {
				$sessionData->nfsenDisplayFilter = str_replace(" and ".$static_filter_ipv6_traffic, "", $sessionData->nfsenDisplayFilter);
			}
		}			
		
		/**
		 * Writes the session variables related to the NfSen profile and its
		 * sources.
		 */		
		function setNfSenProfileAndSources() {
			global $NFSEN_DEFAULT_SOURCES, $sessionData;

			if(isset($_SESSION['profileswitch'])) {
				$_SESSION['SURFmap']['nfsenProfile'] = $_SESSION['profileswitch'];
			}
			
			if(isset($_SESSION['profileinfo']['type'])) {
				$_SESSION['SURFmap']['nfsenProfileType'] = ($_SESSION['profileinfo']['type'] & 4) > 0 ? "shadow" : "real";
			}
			
			if($_SESSION['SURFmap']['nfsenPreviousProfile'] === "") { // initialization value
				$_SESSION['SURFmap']['nfsenPreviousProfile'] = $_SESSION['SURFmap']['nfsenProfile'];
			} else if($_SESSION['SURFmap']['nfsenProfile'] !== $_SESSION['SURFmap']['nfsenPreviousProfile']) {
				// Reset selected NfSen sources on profile change
				$_SESSION['SURFmap']['nfsenAllSources'] = "";
				$_SESSION['SURFmap']['nfsenSelectedSources'] = "";
			}
			
			if(isset($_SESSION['profileinfo']['channel']) && $_SESSION['SURFmap']['nfsenAllSources'] === "") { // initialization value
				// Only collect all sources when not done yet
				foreach($_SESSION['profileinfo']['channel'] as $source) {
					if(strlen($_SESSION['SURFmap']['nfsenAllSources']) != 0) {
						$_SESSION['SURFmap']['nfsenAllSources'] .= ":";
					}
					$_SESSION['SURFmap']['nfsenAllSources'] .= $source['name'];
				}
			}

			if(isset($_GET['nfsensources'])) {
				$_SESSION['SURFmap']['nfsenSelectedSources'] = "";
				foreach($_GET['nfsensources'] as $source) {
					if(strlen($_SESSION['SURFmap']['nfsenSelectedSources']) != 0) {
						$_SESSION['SURFmap']['nfsenSelectedSources'] .= ":";
					}
					$_SESSION['SURFmap']['nfsenSelectedSources'] .= $source;
				}
			} else if($_SESSION['SURFmap']['nfsenSelectedSources'] === "") { // initialization value
				if(strlen($NFSEN_DEFAULT_SOURCES) > 0) {
					// Check for ";" is only for dealing with syntax in older versions
					$defaultSources = explode(":", str_replace(";", ":", $NFSEN_DEFAULT_SOURCES));
					
					// Check whether configured default sources exist
					foreach($defaultSources as $source) {
						if(strpos($_SESSION['SURFmap']['nfsenAllSources'], $source) !== false) {
							if(strlen($_SESSION['SURFmap']['nfsenSelectedSources']) != 0) {
								$_SESSION['SURFmap']['nfsenSelectedSources'] .= ":";
							}
							$_SESSION['SURFmap']['nfsenSelectedSources'] .= $source;
						}
					}
				}

				// If none of the configured default sources was available or no default source was configured at all
				if($_SESSION['SURFmap']['nfsenSelectedSources'] === "") {
					$_SESSION['SURFmap']['nfsenSelectedSources'] = $_SESSION['SURFmap']['nfsenAllSources'];
				}
			}
			
			if(strpos($_SESSION['SURFmap']['nfsenSelectedSources'], ":") === false) {
				$sessionData->firstNfSenSource = $_SESSION['SURFmap']['nfsenSelectedSources'];
			} else {
				$sessionData->firstNfSenSource = substr($_SESSION['SURFmap']['nfsenSelectedSources'], 0, strpos($_SESSION['SURFmap']['nfsenSelectedSources'], ":"));
			}
			
			// Set 'nfsenPreviousProfile' session variable after source initialization to current profile
			$_SESSION['SURFmap']['nfsenPreviousProfile'] = $_SESSION['SURFmap']['nfsenProfile'];
		}		
		
		/**
		 * Writes the session variables related to dates and times of the
		 * current session.
		 */		
		function setDatesAndTimes() {
			global $sessionData;
			
			// Latest date/time slot (depending on files available by nfcapd)
			$sessionData->latestDate = generateDateString(5);
			$latestTime = generateTimeString(5);
			$sessionData->latestHour = substr($latestTime, 0, 2);
			$sessionData->latestMinute = substr($latestTime, 3, 2);
			
			// In case the source files do not exist (yet) for a 5 min. buffer time, create timestamps based on 10 min. buffer time
			if(!sourceFilesExist($sessionData->firstNfSenSource, $sessionData->latestDate, 
					$sessionData->latestHour, $sessionData->latestMinute)) {
				$sessionData->latestDate = generateDateString(10);
				$latestTime = generateTimeString(10);
				$sessionData->latestHour = substr($latestTime, 0, 2);
				$sessionData->latestMinute = substr($latestTime, 3, 2);
			}

			// Dates (ordering is based on priorities)
			if(isset($_GET['autorefresh']) || $_SESSION['SURFmap']['date1'] == "-1") {  // initialization value
				$sessionData->originalDate1Window = substr($sessionData->latestDate, 0, 4)."/".substr($sessionData->latestDate, 4, 2)."/".substr($sessionData->latestDate, 6, 2);
				$sessionData->originalDate2Window = $sessionData->originalDate1Window;
				
				$_SESSION['SURFmap']['date1'] = $sessionData->latestDate;
				$_SESSION['SURFmap']['date2'] = $sessionData->latestDate;
			} else if(isset($_GET['datetime1']) || isset($_GET['datetime2'])) {
				if(isset($_GET['datetime1'])) {
					$date = explode(" ", $_GET['datetime1']);
					$dateParts = explode("/", $date[0]);
					
					$sessionData->originalDate1Window = $date[0];

					$year = $dateParts[2];
					$month = $dateParts[0];
					$day = $dateParts[1];
					
					$_SESSION['SURFmap']['date1'] = $year.$month.$day;
				} 
				if(isset($_GET['datetime2'])) {
					$date = explode(" ", $_GET['datetime2']);
					$dateParts = explode("/", $date[0]);
					
					$sessionData->originalDate2Window = $date[0];
					
					$year = $dateParts[2];
					$month = $dateParts[0];
					$day = $dateParts[1];

					$_SESSION['SURFmap']['date2'] = $year.$month.$day;
				}
			} else {
				$sessionData->originalDate1Window = substr($_SESSION['SURFmap']['date1'], 0, 4)."/".substr($_SESSION['SURFmap']['date1'], 4, 2)."/".substr($_SESSION['SURFmap']['date1'], 6, 2);
				$sessionData->originalDate2Window = substr($_SESSION['SURFmap']['date2'], 0, 4)."/".substr($_SESSION['SURFmap']['date2'], 4, 2)."/".substr($_SESSION['SURFmap']['date2'], 6, 2);
			}
			
			// Times (ordering is based on priorities)
			if(isset($_GET['autorefresh']) || $_SESSION['SURFmap']['hours1'] == "-1" || $_SESSION['SURFmap']['minutes1'] == "-1") { // initialization value
				$sessionData->originalTime1Window = $sessionData->latestHour.":".$sessionData->latestMinute;
				$sessionData->originalTime2Window = $sessionData->originalTime1Window;
				
				$_SESSION['SURFmap']['hours1'] = $sessionData->latestHour;
				$_SESSION['SURFmap']['minutes1'] = $sessionData->latestMinute;
				$_SESSION['SURFmap']['hours2'] = $sessionData->latestHour;
				$_SESSION['SURFmap']['minutes2'] = $sessionData->latestMinute;
			} else if(isset($_GET['datetime1']) || isset($_GET['datetime2'])) {
				if(isset($_GET['datetime1'])) {
					$time = explode(" ", $_GET['datetime1']);
					$timeParts = explode(":", $time[1]);
					
					$sessionData->originalTime1Window = $time[1];
					
					$_SESSION['SURFmap']['hours1'] = $timeParts[0];
					$_SESSION['SURFmap']['minutes1'] = $timeParts[1];
				} 
				if(isset($_GET['datetime2'])) {
					$time = explode(" ", $_GET['datetime2']);
					$timeParts = explode(":", $time[1]);
					
					$sessionData->originalTime2Window = $time[1];
					
					$_SESSION['SURFmap']['hours2'] = $timeParts[0];
					$_SESSION['SURFmap']['minutes2'] = $timeParts[1];
				}
			} else {
				$sessionData->originalTime1Window = $_SESSION['SURFmap']['hours1'].":".$_SESSION['SURFmap']['minutes1'];
				$sessionData->originalTime2Window = $_SESSION['SURFmap']['hours2'].":".$_SESSION['SURFmap']['minutes2'];
			}
			
			// If the source files for the first time selector do not exist
			if(!sourceFilesExist($sessionData->firstNfSenSource, $_SESSION['SURFmap']['date1'],
					$_SESSION['SURFmap']['hours1'], $_SESSION['SURFmap']['minutes1'])) {
				$sessionData->errorCode = 2;					
				$this->logHandler->writeError("Selected time window (1) does not exist (".$_SESSION['SURFmap']['date1'].$_SESSION['SURFmap']['hours1'].$_SESSION['SURFmap']['minutes1'].")");
				
				$_SESSION['SURFmap']['date1'] = $sessionData->latestDate;
				$_SESSION['SURFmap']['hours1'] = $sessionData->latestHour;
				$_SESSION['SURFmap']['minutes1'] = $sessionData->latestMinute;
			}

			// If the source files for the second time selector do not exist
			if(!sourceFilesExist($sessionData->firstNfSenSource, $_SESSION['SURFmap']['date2'],
					$_SESSION['SURFmap']['hours2'], $_SESSION['SURFmap']['minutes2'])) {
				// If this is true, both selected date/time windows do not exist
				if($sessionData->errorCode == 2) $sessionData->errorCode = 4;
				
				$this->logHandler->writeError("Selected time window (2) does not exist (".$_SESSION['SURFmap']['date2'].$_SESSION['SURFmap']['hours2'].$_SESSION['SURFmap']['minutes2'].")");
				
				$_SESSION['SURFmap']['date2'] = $sessionData->latestDate;
				$_SESSION['SURFmap']['hours2'] = $sessionData->latestHour;
				$_SESSION['SURFmap']['minutes2'] = $sessionData->latestMinute;
			}

			if(!isTimeRangeIsPositive($_SESSION['SURFmap']['date1'], $_SESSION['SURFmap']['hours1'], 
					$_SESSION['SURFmap']['minutes1'], $_SESSION['SURFmap']['date2'],
					$_SESSION['SURFmap']['hours2'], $_SESSION['SURFmap']['minutes2'])) {
				$this->logHandler->writeInfo("Selected date/time range is not valid (".
						$_SESSION['SURFmap']['date1'].$_SESSION['SURFmap']['hours1'].
						$_SESSION['SURFmap']['minutes1']." - ".$_SESSION['SURFmap']['date2'].
						$_SESSION['SURFmap']['hours2'].$_SESSION['SURFmap']['minutes2']."). ".
						"Swapping dates/times");
						
				$tempDate1 = $_SESSION['SURFmap']['date1'];
				$tempHours1 = $_SESSION['SURFmap']['hours1'];
				$tempMinutes1 = $_SESSION['SURFmap']['minutes1'];
				
				$_SESSION['SURFmap']['date1'] = $_SESSION['SURFmap']['date2'];
				$_SESSION['SURFmap']['hours1'] = $_SESSION['SURFmap']['hours2'];
				$_SESSION['SURFmap']['minutes1'] = $_SESSION['SURFmap']['minutes2'];

				$_SESSION['SURFmap']['date2'] = $tempDate1;
				$_SESSION['SURFmap']['hours2'] = $tempHours1;
				$_SESSION['SURFmap']['minutes2'] = $tempMinutes1;
				
				$this->logHandler->writeInfo("New date/time range: ".
						$_SESSION['SURFmap']['date1'].$_SESSION['SURFmap']['hours1'].
						$_SESSION['SURFmap']['minutes1']." - ".$_SESSION['SURFmap']['date2'].
						$_SESSION['SURFmap']['hours2'].$_SESSION['SURFmap']['minutes2']."");
			}
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
		global $nfsenConfig;
		
		$year = substr($date, 0, 4);
		$month = substr($date, 4, 2);
		$day = substr($date, 6, 2);
		
		/*
		 Possible layouts:
		 0 		       no hierachy levels - flat layout
		 1 %Y/%m/%d    year/month/day
		 2 %Y/%m/%d/%H year/month/day/hour
		*/
		switch(intval($nfsenConfig['SUBDIRLAYOUT'])) {
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

?>