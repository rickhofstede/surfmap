<?php
	/*******************************
	 * index.php [SURFmap]
	 * Author: Rick Hofstede
	 * University of Twente, The Netherlands
	 *******************************/

	require_once("config.php");
	require_once("geoPlugin/geoplugin.class.php");
	require_once("MaxMind/geoipcity.inc");
	require_once("IP2Location/ip2location.class.php");	
	require_once("ConnectionHandler.php");
	require_once($NFSEN_DIR."/conf.php");
	require_once($NFSEN_DIR."/nfsenutil.php");

	$version = "v2.1 dev (20111111)";

	// Initialize session
	if(!isset($_SESSION['SURFmap'])) $_SESSION['SURFmap'] = array();
	
	$infoLogQueue = new LogQueue();
	$errorLogQueue = new LogQueue();
	$connectionHandler = new ConnectionHandler();

	$sessionData = new SessionData();
	$connectionHandler->retrieveDataNfSen($sessionData);
	
	$geoData = $connectionHandler->retrieveDataGeolocation($sessionData->flowRecordCount, $sessionData->NetFlowData);
	$geoCoderData = $connectionHandler->retrieveDataGeocoderDB($geoData, $sessionData->flowRecordCount);
	
	function stringifyNetFlowData($NetFlowData, $NetFlowDataRecords, $type) {
		$delimiter = "__";
		$sub_delimiter = "_"; 
		$result_string = "";

		if($type == "IP") {
			for($i = 0; $i < $NetFlowDataRecords; $i++) {
				$src_IP = $NetFlowData[$i]->ipv4_src;
				$dst_IP = $NetFlowData[$i]->ipv4_dst;
				
				if($i == ($NetFlowDataRecords - 1)) $result_string .= $src_IP.$sub_delimiter.$dst_IP;
				else $result_string .= $src_IP.$sub_delimiter.$dst_IP.$delimiter;
			}
		} else if($type == "PACKETS") {
			for($i = 0; $i < $NetFlowDataRecords; $i++) {
				if($i == ($NetFlowDataRecords - 1)) $result_string .= $NetFlowData[$i]->packets;
				else $result_string .= $NetFlowData[$i]->packets.$delimiter;
			}
		} else if($type == "OCTETS") {
			for($i = 0; $i < $NetFlowDataRecords; $i++) {
				if($i == ($NetFlowDataRecords - 1)) $result_string .= $NetFlowData[$i]->octets;
				else $result_string .= $NetFlowData[$i]->octets.$delimiter;
			}
		} else if($type == "DURATION") {
			for($i = 0; $i < $NetFlowDataRecords; $i++) {
				if($i == ($NetFlowDataRecords - 1)) $result_string .= $NetFlowData[$i]->duration;
				else $result_string .= $NetFlowData[$i]->duration.$delimiter;
			}
		} else if($type == "PROTOCOL") {
			for($i = 0; $i < $NetFlowDataRecords; $i++) {
				if($i == ($NetFlowDataRecords - 1)) $result_string .= $NetFlowData[$i]->protocol;
				else $result_string .= $NetFlowData[$i]->protocol.$delimiter;
			}	
		} else if($type == "PORT") {
			for($i = 0; $i < $NetFlowDataRecords; $i++) {
				if($i == ($NetFlowDataRecords - 1)) $result_string .= $NetFlowData[$i]->port_src.$sub_delimiter.$NetFlowData[$i]->port_dst;
				else $result_string .= $NetFlowData[$i]->port_src.$sub_delimiter.$NetFlowData[$i]->port_dst.$delimiter;
			}
		} else if($type == "FLOWS") {
			for($i = 0; $i < $NetFlowDataRecords; $i++) {
				if($i == ($NetFlowDataRecords - 1)) $result_string .= $NetFlowData[$i]->flows;
				else $result_string .= $NetFlowData[$i]->flows.$delimiter;
			}																
		} else echo "Type error in stringifyNetFlowData() [PHP]";

		return $result_string;
	}
	
	function stringifyGeoData($array, $NetFlowDataRecords, $type) {
		$delimiter = "__";
		$sub_delimiter = "_"; 
		$result_string = "";
		
		if($type == "COUNTRY") {
			for($i = 0; $i < $NetFlowDataRecords; $i++) {
				if($i == ($NetFlowDataRecords - 1)) $result_string .= $array[$i][0]['COUNTRY'].$sub_delimiter.$array[$i][1]['COUNTRY'];
				else $result_string .= $array[$i][0]['COUNTRY'].$sub_delimiter.$array[$i][1]['COUNTRY'].$delimiter;
			}
		} else if ($type == "REGION") {
			for($i = 0; $i < $NetFlowDataRecords; $i++) {
				if($i == ($NetFlowDataRecords - 1)) $result_string .= $array[$i][0]['REGION'].$sub_delimiter.$array[$i][1]['REGION'];
				else $result_string .= $array[$i][0]['REGION'].$sub_delimiter.$array[$i][1]['REGION'].$delimiter;
			}
		} else if ($type == "CITY") {
			for($i = 0; $i < $NetFlowDataRecords; $i++) {
				if($i == ($NetFlowDataRecords - 1)) $result_string .= $array[$i][0]['CITY'].$sub_delimiter.$array[$i][1]['CITY'];
				else $result_string .= $array[$i][0]['CITY'].$sub_delimiter.$array[$i][1]['CITY'].$delimiter;
			}							
		} else {
			echo "Type error in stringifyGeoData() [PHP]";
		}
		
		return $result_string;
	}
	
	function stringifyGeoCoderData($type) {
		global $geoCoderData, $sessionData;
		
		$delimiter = "___";
		$sub_delimiter = "__";
		$sub_sub_delimiter = "_"; 
		$result_string = "";
		
		if($type == "COUNTRY") {
			for($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				if($i == ($sessionData->flowRecordCount - 1)) $result_string .= $geoCoderData[$i]->srcCountry[0].$sub_sub_delimiter.$geoCoderData[$i]->srcCountry[1].$sub_delimiter.$geoCoderData[$i]->dstCountry[0].$sub_sub_delimiter.$geoCoderData[$i]->dstCountry[1];
				else $result_string .= $geoCoderData[$i]->srcCountry[0].$sub_sub_delimiter.$geoCoderData[$i]->srcCountry[1].$sub_delimiter.$geoCoderData[$i]->dstCountry[0].$sub_sub_delimiter.$geoCoderData[$i]->dstCountry[1].$delimiter;
			}
		} else if ($type == "REGION") {
			for($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				if($i == ($sessionData->flowRecordCount - 1)) $result_string .= $geoCoderData[$i]->srcRegion[0].$sub_sub_delimiter.$geoCoderData[$i]->srcRegion[1].$sub_delimiter.$geoCoderData[$i]->dstRegion[0].$sub_sub_delimiter.$geoCoderData[$i]->dstRegion[1];
				else $result_string .= $geoCoderData[$i]->srcRegion[0].$sub_sub_delimiter.$geoCoderData[$i]->srcRegion[1].$sub_delimiter.$geoCoderData[$i]->dstRegion[0].$sub_sub_delimiter.$geoCoderData[$i]->dstRegion[1].$delimiter;
			}
		} else if ($type == "CITY") {
			for($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				if($i == ($sessionData->flowRecordCount - 1)) $result_string .= $geoCoderData[$i]->srcCity[0].$sub_sub_delimiter.$geoCoderData[$i]->srcCity[1].$sub_delimiter.$geoCoderData[$i]->dstCity[0].$sub_sub_delimiter.$geoCoderData[$i]->dstCity[1];
				else $result_string .= $geoCoderData[$i]->srcCity[0].$sub_sub_delimiter.$geoCoderData[$i]->srcCity[1].$sub_delimiter.$geoCoderData[$i]->dstCity[0].$sub_sub_delimiter.$geoCoderData[$i]->dstCity[1].$delimiter;
			}
		} 
		return $result_string;
	}

	function ReportLog($message) {
		// dummy function to avoid PHP errors
	}

	class LogQueue {
		var $queue;
		
		function addToQueue($message) {
			if(strlen($this->queue) == 0) {
				$this->queue = $message;
			} else {
				$this->queue .= "_".$message;
			}
		}
		
	}

	class SessionData {
		var $flowRecordCount;
		var $query;
		var $latestDate;
		var $latestHour;
		var $latestMinute;
		var $originalDate1Window;
		var $originalTime1Window;
		var $originalDate2Window;
		var $originalTime2Window;
		
		var $NetFlowData;
		var $nfsenDetails;
		var $nfsenProfile;
		var $nfsenProfileType;
		var $nfsenDisplayFilter; // Contains filter without internal domains
		var $firstNfSenSource = "";
		var $geoData;
		var $geoCoderData;
		
		/*
		 * 	0: no error
		 *	1: filter error
		 *	2: invalid date/time window (selector 1)
		 *	3: invalid date/time window (selector 2)
		 *	4: invalid date/time window (selector 1+2)
		 *  5: (unused)
		 *  6: profile error
		 */
		var $errorCode = 0;
		var $errorMessage = "";
	}
	
	class NetFlowFlow {
		var $ipv4_src;
		var $ipv4_dst;
		var $port_src;
		var $port_dst;
		var $protocol;
		var $packets;
		var $octets;
		var $duration;
		var $flows; // is not a NetFlow field, but used later on
	}

	class FlowCoordinates {
		// all variables consist of an array (size of 2, with latitude and longitude)
		var $srcCountry;
		var $srcRegion;
		var $srcCity;
		var $srcHost;
		
		var $dstCountry;
		var $dstRegion;
		var $dstCity;
		var $dstHost;
		
		function writeVariable($endPoint, $level, $value) {
			if($endPoint == 0) { // source
				switch($level) {
					case 0:	$this->srcCountry = $value;
							break;
					
					case 1: $this->srcRegion = $value;
							break;
							
					case 2:	$this->srcCity = $value;
							break;
							
					case 3:	$this->srcHost = $value;
							break;
							
					default:break;
				}
			} else { // destination
				switch($level) {
					case 0:	$this->dstCountry = $value;
							break;
					
					case 1: $this->dstRegion = $value;
							break;
							
					case 2:	$this->dstCity = $value;
							break;
							
					case 3:	$this->dstHost = $value;
							break;
							
					default:break;
				}
			}
		}
	}

?>

<!DOCTYPE html>
<html>
<head>
   	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>SURFmap -- A Network Monitoring Tool Based on the Google Maps API</title>
	<link type="text/css" rel="stylesheet" href="jquery/css/start/jquery-ui-1.8.16.custom.css" />
	<link type="text/css" rel="stylesheet" href="css/jquery.alerts.css" /> <!-- http://abeautifulsite.net/blog/2008/12/jquery-alert-dialogs/ -->
	<link type="text/css" rel="stylesheet" href="css/surfmap.css" />
	<script type="text/javascript" src="<?php if($FORCE_HTTPS) {echo 'https';} else {echo 'http';} ?>://maps.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript" src="jquery/js/jquery-1.6.2.min.js"></script>
	<script type="text/javascript" src="jquery/js/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="js/jquery.alerts.js"></script>
	<script type="text/javascript" src="js/jquery.multiselect.min.js"></script>
	<script type="text/javascript" src="js/jqueryutil.js"></script>
	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" src="js/maputil.js"></script>
	<script type="text/javascript" src="js/markermanager.js"></script>
	<script type="text/javascript" src="js/objects.js"></script>
	<script type="text/javascript" src="js/util.js"></script>
	<script type="text/javascript">
		var COUNTRY = 0; var REGION = 1; var CITY = 2; var HOST = 3;
		var map, markerManager, geocoder, infoWindow, currentZoomLevel, currentSURFmapZoomLevel;
		var initialZoomLevel = <?php if($_SESSION['SURFmap']['zoomLevel'] != "-1") { echo $_SESSION['SURFmap']['zoomLevel']; } else { echo '2'; } ?>;
		var demoModeInitialSURFmapZoomLevel = <?php echo $DEMO_MODE_DEFAULT_ZOOM_LEVEL; ?>;
		var mapCenter = "<?php if($_SESSION['SURFmap']['mapCenter'] != "-1") {echo $_SESSION['SURFmap']['mapCenter'];} else {echo $MAP_CENTER;} ?>";
			mapCenter = new google.maps.LatLng(parseFloat(mapCenter.substring(0, mapCenter.indexOf(","))), parseFloat(mapCenter.substring(mapCenter.indexOf(",") + 1)));
		var mapCenterWithoutGray; // Map center, for which the map doesn't show gray areas
		var minZoomLevel = 2;
		var maxZoomLevel = 13;

		var flowRecords = [];
		var lines = new Array(4); // 4 zoom levels
		var lineProperties = new Array(4); // 4 zoom levels
		var lineOverlays = new Array(); // Contains the actual map overlays (lines, not markers)
		var markers = new Array(4); // 4 zoom levels
		var markerProperties = new Array(4); // 4 zoom levels	

		var green = "#00cc00";
		var yellow = "#ffff00";
		var orange = "#ff6600";
		var red = "#ff0000";
		var black = "#000000";
		var lineColors = 4;
		var lineColorClassification = [];
		
		/* NfSen settings */
		var nfsenQuery = "<?php echo $sessionData->query; ?>";
		var nfsenProfile = "<?php echo $sessionData->nfsenProfile; ?>"
		var nfsenFilter = "<?php echo $_SESSION['SURFmap']['filter']; ?>";
		var nfsenDisplayFilter = "<?php echo $sessionData->nfsenDisplayFilter; ?>";
		var nfsenAllSources = "<?php echo $_SESSION['SURFmap']['nfsenAllSources']; ?>".split(":");
		var nfsenSelectedSources = "<?php echo $_SESSION['SURFmap']['nfsenSelectedSources']; ?>".split(":");
		
		var date1 = "<?php echo $_SESSION['SURFmap']['date1']; ?>";
		var date2 = "<?php echo $_SESSION['SURFmap']['date2']; ?>";
		var hours1 = "<?php echo $_SESSION['SURFmap']['hours1']; ?>";
		var hours2 = "<?php echo $_SESSION['SURFmap']['hours2']; ?>";
		var minutes1 = "<?php echo $_SESSION['SURFmap']['minutes1']; ?>";
		var minutes2 = "<?php echo $_SESSION['SURFmap']['minutes2']; ?>";
		var latestDate = "<?php echo $sessionData->latestDate; ?>";
		var latestHour = "<?php echo $sessionData->latestHour; ?>";
		var latestMinute = "<?php echo $sessionData->latestMinute; ?>";
		var errorCode = "<?php echo $sessionData->errorCode; ?>";
		var errorMessage = "<?php echo $sessionData->errorMessage; ?>";
		
		var INFO_logQueue = [];
		var ERROR_logQueue = [];
		var DEBUG_logQueue = [];
		var GEOCODING_queue = [];
		var SESSION_queue = [];

		var entryCount = <?php echo $_SESSION['SURFmap']['entryCount']; ?>;
		var flowRecordCount = <?php echo $sessionData->flowRecordCount; ?>;
		var applicationVersion = "<?php echo $version; ?>"; // SURFmap version number
		var demoMode = <?php echo $DEMO_MODE; ?>; // 0: Disabled; 1: Enabled
		var demoModePageTitle = "<?php echo $DEMO_MODE_PAGE_TITLE; ?>";
		var autoOpenMenu = <?php echo $AUTO_OPEN_MENU; ?>; // 0: Disabled; 1: Enabled
		var debugLogging = <?php echo $LOG_DEBUG; ?>;
		var showWarningOnFileError = <?php echo $SHOW_WARNING_ON_FILE_ERROR; ?>;
		
		var autoRefresh = <?php echo $_SESSION['SURFmap']['refresh']; ?>;
		var autoRefreshID = -1;
	
		var GEOLOCATION_DB = "<?php echo $GEOLOCATION_DB; ?>";
		var IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION = "<?php echo $IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION; ?>";
		
		var greenIcon = new google.maps.MarkerImage("images/green_marker.png", new google.maps.Size(20, 34));
		
		// NfSen parameters
		var nfsenStatOrder = "<?php echo $_SESSION['SURFmap']['nfsenStatOrder']; ?>"; // flows, packets or octets
		nfsenStatOrder = (nfsenStatOrder == "" ? "flows" : nfsenStatOrder);
		var nfsenOption = <?php echo $_SESSION['SURFmap']['nfsenOption']; ?>; // nfsenOption; 0: List flows; nfsenOption 1: Top StatN

		var protocols = ["Reserved", "ICMP", "IGMP", "GGP", "Encapsulated IP", "ST", "TCP", "UCL", "EGP", "IGP", "BBN-RCC-MON", "NVP-II", "PUP", "ARGUS", "EMCON", "XNET", "CHAOS", "UDP", "MUX", "DCN-MEAS", "HMP", "PRM", "XNS-IDP", "trUNK-1", "trUNK-2", "LEAF-1", "LEAF-2", "RDP", "IRTP", "ISO-TP4", "NETBLT", "MFE-NSP", "MERIT-INP", "SEP", "3PC", "IDPR", "XTP", "DDP", "IDPR-CMTP", "TP++", "IL", "SIP", "SDRP", "SIP-SR", "SIP-FRAG", "IDRP", "RSVP", "GRE", "MHRP", "BNA", "SIPP-ESP", "SIPP-AH", "I-NLSP", "SWIPE", "NHRP", "Unassigned", "Unassigned", "Unassigned", "Unassigned", "Unassigned", "Unassigned", "Any host internal protocol", "CFTP", "Any local network", "SAT-EXPAK", "KRYPTOLAN", "RVD", "IPPC", "Any distributed file system", "SAT-MON", "VISA", "IPCV", "CPNX", "CPHB", "WSN", "PVP", "BR-SAT-MON", "SUN-ND", "WB-MON", "WB-EXPAK", "ISO-IP", "VMTP", "SECURE-VMTP", "VIVES", "TTP", "NSFNET-IGP", "DGP", "TCF", "IGRP", "OSPFIGP", "Sprite-RPC", "LARP", "MTP", "AX.25", "IFIP", "MICP", "SCC-SP", "ETHERIP", "ENCAP", "Any private encryption scheme", "GMTP"];
		
		// --- Geocoding parameters
		var delay = 100;
		var geocodingQueue = [];
		var geocodedPlaces = [];
		var totalGeocodingRequests = 0; // total number of geocoding requests
		var successfulGeocodingRequests = 0; // successful geocoding requests
		var erroneousGeocodingRequests = 0; // erroneous geocoding requests
		var outputGeocodingErrorMessage = 0; // indicates if an geocoding error message has been shown to the user (this should happen only once)
		var WRITE_DATA_TO_GEOCODER_DB = <?php echo $WRITE_DATA_TO_GEOCODER_DB; ?>;
		// --- End of Geocoding parameters
		
	   /**
		* Stores the specified message in the appropriate log queue (depending on type).
		* Parameters:
		*	type - can be either 'INFO', 'ERROR', 'DEBUG'
		*	message - the log message that has to be written to the log file
		*/
		function addToLogQueue(type, message) {
			if(type == "INFO") {
				INFO_logQueue.push(message);
			} else if(type == "ERROR") {
				ERROR_logQueue.push(message);
			} else if(type == "DEBUG") {
				DEBUG_logQueue.push(message);
			} else {
			}
		}
		
	   /**
		* Processes all server transactions. These transactions are using the source file
		* 'servertransaction.php'. Three server transactions have been defined:
		*		1: Writing error messages to the log file
		*		2: Writing informational messages to the log file
		*		3: Writing a geocoded place's coordinates to a DBMS
		* The priorities of the transactions are exactly as listed above.
		*/
		function serverTransactions() {
			var somethingToSend = 0;
			while(ERROR_logQueue.length > 0 || DEBUG_logQueue.length > 0 || INFO_logQueue.length > 0 || GEOCODING_queue.length > 0 || SESSION_queue.length > 0) {
				var data, logType, message;
				if(ERROR_logQueue.length > 0) {
					message = ERROR_logQueue.shift();
					logType = "ERROR";
				} else if(DEBUG_logQueue.length > 0) {
					message = DEBUG_logQueue.shift();
					logType = "DEBUG";
				} else if(INFO_logQueue.length > 0) {
					message = INFO_logQueue.shift();
					logType = "INFO";
				}
				
				if(logType == "ERROR" || logType == "DEBUG" || logType == "INFO") {
					somethingToSend = 1;
					data = "transactionType=log&message=" + message.replace(" ", "_") + "&logType=" + logType + "&id=" + Math.random();
				} else if(GEOCODING_queue.length > 0) {
					somethingToSend = 1;
					var placeToStore = GEOCODING_queue.shift();
					data = "transactionType=geocoder&location=" + placeToStore.place.replace(" ", "_") + "&lat=" + placeToStore.lat + "&lng=" + placeToStore.lng + "&id=" + Math.random();
				} else if(SESSION_queue.length > 0) {
					somethingToSend = 1;
					var sessionData = SESSION_queue.shift();
					data = "transactionType=session&type=" + sessionData.type + "&value=" + sessionData.value + "&id=" + Math.random();
				}

				// If there is something to send to the server
				if(somethingToSend == 1) {
					$.ajax({
						type: "GET",
						url: "servertransaction.php",
						data: data,
						error: function(msg) {
							// alert("The Web server is not reachable for AJAX calls. Please check your configuration.");
						},
						success: function(msg) {
							var splittedResult = msg.split("##");
							if(splittedResult.length == 3 && splittedResult[0] == "OK" && splittedResult[1] == "geocoder") {
								addToLogQueue("INFO", splittedResult[2] + " was successfully stored in GeoCoder DB");
							}
						}
					});
					somethingToSend = 0;
					logType = "";
				}
			}
		}

	   /**
	    * Reads the PHP ERROR log queue.
		* Parameters:
		*	type - can be either INFO or ERROR
		*/			
		function readPHPLogQueue(type) {
			var logString;
			
			if(type == "INFO") {
				logString = "<?php echo $infoLogQueue->queue; ?>";
			} else if(type == "ERROR") {
				logString = "<?php echo $errorLogQueue->queue; ?>";
			} else {
				alert("Type error in readPHPLogQueue()");
			}
			
			if(logString.length > 0) {
				var logArray = logString.split("_");
				for(var i = 0; i < logArray.length; i++) {
					addToLogQueue(type, logArray[i]);
				}
			}
		}
			
	   /**
		* This function puts the network traffic and gegraphical information in a Javascript
		* associative array.
		*/			
		function importData() {
			// NetFlow data
			var IPs = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, $sessionData->flowRecordCount, 'IP'); ?>", "IP", flowRecordCount);
			var ports = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, $sessionData->flowRecordCount, 'PORT'); ?>", "PORT", flowRecordCount);
			var protocols = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, $sessionData->flowRecordCount, 'PROTOCOL'); ?>", "PROTOCOL", flowRecordCount);
			var packets = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, $sessionData->flowRecordCount, 'PACKETS'); ?>", "PACKETS", flowRecordCount);
			var octets = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, $sessionData->flowRecordCount, 'OCTETS'); ?>", "OCTETS", flowRecordCount);
			var durations = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, $sessionData->flowRecordCount, 'DURATION'); ?>", "DURATION", flowRecordCount);
			var flows = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, $sessionData->flowRecordCount, 'FLOWS'); ?>", "FLOWS", flowRecordCount);

			// GeoLocation data
			var countries = stringToArray("<?php echo stringifyGeoData($geoData, $sessionData->flowRecordCount, 'COUNTRY'); ?>", "COUNTRY", flowRecordCount);
			var regions = stringToArray("<?php echo stringifyGeoData($geoData, $sessionData->flowRecordCount, 'REGION'); ?>", "REGION", flowRecordCount);
			var cities = stringToArray("<?php echo stringifyGeoData($geoData, $sessionData->flowRecordCount, 'CITY'); ?>", "CITY", flowRecordCount);
			
			// GeoCoder data
			var countryLatLngs = stringToArray("<?php echo stringifyGeoCoderData('COUNTRY'); ?>", "GeoCoder_COUNTRY", flowRecordCount);
			var regionLatLngs = stringToArray("<?php echo stringifyGeoCoderData('REGION'); ?>", "GeoCoder_REGION", flowRecordCount);
			var cityLatLngs = stringToArray("<?php echo stringifyGeoCoderData('CITY'); ?>", "GeoCoder_CITY", flowRecordCount);

			for(var i = 0; i < flowRecordCount; i++) {
				flowRecords[i] = new FlowRecord(IPs[i][0], ports[i][0], IPs[i][1], ports[i][1], protocols[i]);
				flowRecords[i].packets = packets[i];
				flowRecords[i].octets = octets[i];
				flowRecords[i].duration = durations[i];
				if(nfsenOption == 1) flowRecords[i].flows = flows[i];
				flowRecords[i].srcCountry = countries[i][0];
				flowRecords[i].srcCountryLat = countryLatLngs[i][0][0];
				flowRecords[i].srcCountryLng = countryLatLngs[i][0][1];
				flowRecords[i].dstCountry = countries[i][1];
				flowRecords[i].dstCountryLat = countryLatLngs[i][1][0];
				flowRecords[i].dstCountryLng = countryLatLngs[i][1][1];
				flowRecords[i].srcRegion = regions[i][0];
				flowRecords[i].srcRegionLat = regionLatLngs[i][0][0];
				flowRecords[i].srcRegionLng = regionLatLngs[i][0][1];
				flowRecords[i].dstRegion = regions[i][1];
				flowRecords[i].dstRegionLat = regionLatLngs[i][1][0];
				flowRecords[i].dstRegionLng = regionLatLngs[i][1][1];
				flowRecords[i].srcCity = cities[i][0];
				flowRecords[i].srcCityLat = cityLatLngs[i][0][0];
				flowRecords[i].srcCityLng = cityLatLngs[i][0][1];
				flowRecords[i].dstCity = cities[i][1];
				flowRecords[i].dstCityLat = cityLatLngs[i][1][0];
				flowRecords[i].dstCityLng = cityLatLngs[i][1][1];
			}
		}
		
	   /**
		* Checks whether places need to be geocoded and whether geolocation information of places at a certain
		* zoom level X can be complemented by geolocation information for the same place at zoom level X+1.
		*/			
		function complementFlowRecords() {
			for(var i = 0; i < flowRecordCount; i++) {
				if(flowRecords[i].srcCountryLat == -1) geocodingQueue.push(flowRecords[i].srcCountry);
				if(flowRecords[i].dstCountryLat == -1) geocodingQueue.push(flowRecords[i].dstCountry);
				if(flowRecords[i].srcRegionLat == -1) geocodingQueue.push(flowRecords[i].srcCountry + ", " + flowRecords[i].srcRegion);
				if(flowRecords[i].dstRegionLat == -1) geocodingQueue.push(flowRecords[i].dstCountry + ", " + flowRecords[i].dstRegion);
				if(flowRecords[i].srcCityLat == -1) geocodingQueue.push(flowRecords[i].srcCountry + ", " + flowRecords[i].srcCity);
				if(flowRecords[i].dstCityLat == -1) geocodingQueue.push(flowRecords[i].dstCountry + ", " + flowRecords[i].dstCity);
			}				

			totalGeocodingRequests = geocodingQueue.length;
			
			// Start geocoding
			while(geocodingQueue.length > 0) {
				var currentPlace = geocodingQueue.pop();
				var duplicateIndex = arrayElementIndex(geocodingQueue, currentPlace);
				while(duplicateIndex >= 0) { // duplicate element was found in array
					geocodingQueue.splice(duplicateIndex, 1);
					totalGeocodingRequests--;
					duplicateIndex = arrayElementIndex(geocodingQueue, currentPlace);
				}
				geocode(currentPlace);
			}
			
			var intervalHandlerID = setInterval(function() {
				var completedGeocodingRequests = successfulGeocodingRequests + erroneousGeocodingRequests;
				
				/*
				 * Progress bar previous stage: 40%
				 * Progress bar next stage: 70%
				 * Percents to fill: 70% - 40% = 30%
				 */
				var progress = (completedGeocodingRequests / totalGeocodingRequests) * 30;
				setProgressBarValue("progressBar", 40 + progress, "Geocoding (" + completedGeocodingRequests + " of " + totalGeocodingRequests + ")...");
				if(geocodingQueue.length == 0 && totalGeocodingRequests == completedGeocodingRequests) {
					clearInterval(intervalHandlerID); 

					for(var i = 0; i < geocodedPlaces.length; i++) {
						for(var j = 0; j < flowRecordCount; j++) {
							if(flowRecords[j].srcCountry == geocodedPlaces[i].place && flowRecords[j].srcCountryLat == -1) {
								flowRecords[j].srcCountryLat = geocodedPlaces[i].lat;
								flowRecords[j].srcCountryLng = geocodedPlaces[i].lng;
							}
							if(flowRecords[j].dstCountry == geocodedPlaces[i].place && flowRecords[j].dstCountryLat == -1) {
								flowRecords[j].dstCountryLat = geocodedPlaces[i].lat;
								flowRecords[j].dstCountryLng = geocodedPlaces[i].lng;
							}
							if(flowRecords[j].srcCountry + ", " + flowRecords[j].srcRegion == geocodedPlaces[i].place && flowRecords[j].srcRegionLat == -1) {
								flowRecords[j].srcRegionLat = geocodedPlaces[i].lat;
								flowRecords[j].srcRegionLng = geocodedPlaces[i].lng;
							}
							if(flowRecords[j].dstCountry + ", " + flowRecords[j].dstRegion == geocodedPlaces[i].place && flowRecords[j].dstRegionLat == -1) {
								flowRecords[j].dstRegionLat = geocodedPlaces[i].lat;
								flowRecords[j].dstRegionLng = geocodedPlaces[i].lng;
							}
							if(flowRecords[j].srcCountry + ", " + flowRecords[j].srcCity == geocodedPlaces[i].place && flowRecords[j].srcCityLat == -1) {
								flowRecords[j].srcCityLat = geocodedPlaces[i].lat;
								flowRecords[j].srcCityLng = geocodedPlaces[i].lng;
							}
							if(flowRecords[j].dstCountry + ", " + flowRecords[j].dstCity == geocodedPlaces[i].place && flowRecords[j].dstCityLat == -1) {
								flowRecords[j].dstCityLat = geocodedPlaces[i].lat;
								flowRecords[j].dstCityLng = geocodedPlaces[i].lng;
							}
						}
					}

					for(var i = 0; i < flowRecordCount; i++) {
						// If no latitude/longitude coordinates are present on certain level, take the ones from the upper level
						if(flowRecords[i].srcRegionLat == 0 && flowRecords[i].srcRegionLng == 0) {
							flowRecords[i].srcRegionLat = flowRecords[i].srcCountryLat;
							flowRecords[i].srcRegionLng = flowRecords[i].srcCountryLng;
						}
						if(flowRecords[i].dstRegionLat == 0 && flowRecords[i].dstRegionLng == 0) {
							flowRecords[i].dstRegionLat = flowRecords[i].dstCountryLat;
							flowRecords[i].dstRegionLng = flowRecords[i].dstCountryLng;
						}
						if(flowRecords[i].srcCityLat == 0 && flowRecords[i].srcCityLng == 0) {
							flowRecords[i].srcCityLat = flowRecords[i].srcRegionLat;
							flowRecords[i].srcCityLng = flowRecords[i].srcRegionLng;
						}
						if(flowRecords[i].dstCityLat == 0 && flowRecords[i].dstCityLng == 0) {
							flowRecords[i].dstCityLat = flowRecords[i].dstRegionLat;
							flowRecords[i].dstCityLng = flowRecords[i].dstRegionLng;
						}
					}
					processing();
				}
			}, 100);	
		}		
		
	   /**
		* This function determines the corresponding protocol of the specified protocol number.
		* Parameters:
		*	number - the protocol number of which the corresponding name has to be resolved
		*/			
		function determineProtocolName(number) {
			var protocol;
			
			if(number == 1) {
				protocol = "ICMP";
			} else if(number == 6) {
				protocol = "TCP";
			} else if(number == 17) {
				protocol = "UDP";
			} else if(number == 47) {
				protocol = "GRE";
			} else if(number == 50) {
				protocol = "ESP";
			} else if(number == 51) {
				protocol = "AH";
			} else {
				protocol = protocols[number];
			}
			
			return protocol;
		}

	   /**
		* Removes the country names from geocoded places. This meta data has only been added to region and
		* city names.
		* Parameters:
		*	place - geocoder string from which the meta data needs to be stripped
		*/
		function stripGeocoderMetaData(place) {
			var strippedPlace;
			if(place.lastIndexOf(", ") != -1) {
				strippedPlace = place.substr(place.lastIndexOf(", ") + 2);
			} else {
				strippedPlace = place;
			}
			return strippedPlace;
		}
		
	   /**
		* This function starts calles the Google Maps API geocoder.
		* Parameters:
		*	place - name of the place that has to be geocoded
		*/			
		function geocode(place) {
			if(place == "INVALID IPV4 ADDRESS" && !outputGeocodingErrorMessage) {
				outputGeocodingErrorMessage = 1;
				alert("You are trying to visualize an invalid IP address (i.e., multicast addresses or IPv6 addresses). Please try to use another information source or subset.");
			}

			// Some geolocation databases return 'Unknown' or 'unknown' in case a location is not found or recognized.
			if(place.indexOf("nknown") == -1) {
				geocoder.geocode({'address': place}, function(results, status) {
					if(status == google.maps.GeocoderStatus.OK) {
						addToLogQueue("INFO", "Geocoded " + place + " successfully");
						
						// Store geocoded location in cache DB
						var geocodedPlace = new GeocodedPlace(place, results[0].geometry.location.lat(), results[0].geometry.location.lng());
						geocodedPlaces.push(geocodedPlace);
						
						if(WRITE_DATA_TO_GEOCODER_DB == 1) {
							GEOCODING_queue.push(geocodedPlace);
						}
						
						delay = 100;
						successfulGeocodingRequests++;
					} else if(status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT){
						delay += 100;
						setTimeout(function() {
							geocode(place);
						}, delay);
					} else {
						addToLogQueue("ERROR", "Could not find " + place + ". Reason: " + status);

						geocodedPlaces.push(new GeocodedPlace(place, 0, 0));
						erroneousGeocodingRequests++;
					}
				});
			} else {
				geocodedPlaces.push(new GeocodedPlace(place, 0, 0));
				erroneousGeocodingRequests++;
			}
		}
		
	   /**
		* Checks whether a particular marker record already exists for the marker with
		* the specified ID.
		* Parameters:
		*	level - a SURFmap zoom level
		*	markerID - ID of the marker that needs to be checked
		*	name - name to be present in the record
		*/		
		function markerRecordExists(level, markerID, name) {
			var markerRecordIndex = -1;
			for(var i = 0; i < markerProperties[level][markerID].markerRecords.length; i++) {
				if(markerProperties[level][markerID].markerRecords[i].name == name) {
					markerRecordIndex = i;
					break;
				}
			}
			return markerRecordIndex;
		}		
			
	   /**
		* Checks whether a particular line record already exists for the line with
		* the specified ID.
		* Parameters:
		*	level - a SURFmap zoom level
		*	lineID - ID of the line that needs to be checked
		*	srcName - name of the source to be present in the record
		*	dstName - name of the destination to be present in the record
		*/		
		function lineRecordExists(level, lineID, srcName, dstName) {
			var lineRecordIndex = -1;
			for(var i = 0; i < lineProperties[level][lineID].lineRecords.length; i++) {
				if(lineProperties[level][lineID].lineRecords[i].srcName == srcName && lineProperties[level][lineID].lineRecords[i].dstName == dstName) {
					lineRecordIndex = i;
					break;
				}
			}
			return lineRecordIndex;
		}
		
	   /**
		* This function initializes all markers for all zoom levels.
		*/			
		function initializeMarkers() {
			var MAX_INFO_WINDOW_LINES = 13, existValue;
			
			for(var i = 0; i < 4; i++) { // Zoom levels
				markerProperties[i] = []; // Initialize markerProperties storage

				for(var j = 0; j < flowRecordCount; j++) {
					if((flowRecords[j].srcCountryLat == 0 && flowRecords[j].srcCountryLng == 0) || (flowRecords[j].dstCountryLat == 0 && flowRecords[j].dstCountryLng == 0)) {
						continue;
					}
					
					for(var k = 0; k < 2; k++) { // Both ends of a line
						var currentLat = -1;
						var currentLng = -1;
						var currentName = "";
						
						if(i == COUNTRY && k == 0) {
							currentLat = flowRecords[j].srcCountryLat;
							currentLng = flowRecords[j].srcCountryLng;
							currentName = flowRecords[j].srcRegion;
						} else if(i == COUNTRY && k == 1) {
							currentLat = flowRecords[j].dstCountryLat;
							currentLng = flowRecords[j].dstCountryLng;
							currentName = flowRecords[j].dstRegion;
						} else if(i == REGION && k == 0) {
							currentLat = flowRecords[j].srcRegionLat;
							currentLng = flowRecords[j].srcRegionLng;
							currentName = flowRecords[j].srcCity;
						} else if(i == REGION && k == 1) {
							currentLat = flowRecords[j].dstRegionLat;
							currentLng = flowRecords[j].dstRegionLng;
							currentName = flowRecords[j].dstCity;
						} else if(i == CITY && k == 0) {
							currentLat = flowRecords[j].srcCityLat;
							currentLng = flowRecords[j].srcCityLng;
							currentName = flowRecords[j].srcCity;
						} else if(i == CITY && k == 1) {
							currentLat = flowRecords[j].dstCityLat;
							currentLng = flowRecords[j].dstCityLng;
							currentName = flowRecords[j].dstCity;
						} else if(i == HOST && k == 0) {
							currentLat = flowRecords[j].srcCityLat;
							currentLng = flowRecords[j].srcCityLng;
							currentName = flowRecords[j].srcIP;
						} else if(i == HOST && k == 1) {
							currentLat = flowRecords[j].dstCityLat;
							currentLng = flowRecords[j].dstCityLng;
							currentName = flowRecords[j].dstIP;
						} else {
						}

						existValue = markerExists(i, currentLat, currentLng);
						if(existValue == -1) { // Marker does not exist
							var properties = new MarkerProperties(currentLat, currentLng);
							var record = new MarkerRecord(currentName);

							if(i == HOST) {
								record.protocol = flowRecords[j].protocol;
								record.flows = flowRecords[j].flows;
								
								if(k == 0) {
									record.IP = flowRecords[j].srcIP;	
									record.port = flowRecords[j].srcPort;
									record.countryName = flowRecords[j].srcCountry;
									record.regionName = flowRecords[j].srcRegion;
									record.cityName = flowRecords[j].srcCity;
								} else { // k == 1
									record.IP = flowRecords[j].dstIP;
									record.port = flowRecords[j].dstPort;
									record.countryName = flowRecords[j].dstCountry;
									record.regionName = flowRecords[j].dstRegion;
									record.cityName = flowRecords[j].dstCity;
								}
							}
							
							record.flowRecordIDs.push(j);
							
							properties.markerRecords.push(record);
							markerProperties[i].push(properties);
						} else { // Marker exists
							var existValue2 = markerRecordExists(i, existValue, currentName);
							if(existValue2 == -1) { // Name is not present in a record
								var record = new MarkerRecord(currentName);
								
								if(i == HOST) {
									record.protocol = flowRecords[j].protocol;
									record.flows = flowRecords[j].flows;
									if(k == 0) {
										record.IP = flowRecords[j].srcIP;	
										record.port = flowRecords[j].srcPort;
										record.countryName = flowRecords[j].srcCountry;
										record.regionName = flowRecords[j].srcRegion;
										record.cityName = flowRecords[j].srcCity;
									} else { // k == 1
										record.IP = flowRecords[j].dstIP;
										record.port = flowRecords[j].dstPort;
										record.countryName = flowRecords[j].dstCountry;
										record.regionName = flowRecords[j].dstRegion;
										record.cityName = flowRecords[j].dstCity;
									}
								}
								
								record.flowRecordIDs.push(j);

								markerProperties[i][existValue].markerRecords.push(record);
							} else { // Name is present in a record
								// Check whether host is a new host or not (by using IP addresses)
								if(i == HOST) {
									markerProperties[i][existValue].markerRecords[existValue2].flows = parseInt(markerProperties[i][existValue].markerRecords[existValue2].flows) 
											+ parseInt(flowRecords[j].flows);
								} else {
									var newHost = 1;
									for(var x = 0; x < j; x++) {
										// Check whether the current IP address was present in an earlier (processed) flow record already
										if((k == 0 && (flowRecords[j].srcIP == flowRecords[x].srcIP || flowRecords[j].srcIP == flowRecords[x].dstIP)) 
												|| (k == 1 && (flowRecords[j].dstIP == flowRecords[x].srcIP || flowRecords[j].dstIP == flowRecords[x].dstIP))) {
											// Check whether the earlier found record was indeed processed (i.e. it doesn't contain 'unknown' locations)
											if(flowRecords[x].srcCountryLat != 0 && flowRecords[x].srcCountryLng != 0 
													&& flowRecords[x].dstCountryLat != 0 && flowRecords[x].dstCountryLng != 0) {
												newHost = 0;
												break;
											}
										}
									}
									
									markerProperties[i][existValue].markerRecords[existValue2].flowRecordIDs.push(j);

									if(newHost == 1) {
										markerProperties[i][existValue].markerRecords[existValue2].hosts++;
									}
								}
							}
						}
					}
				}

				markers[i] = []; // Initialize marker storage
				
				for(var j = 0; j < markerProperties[i].length; j++) {
					var tableHeader;
					if(i == COUNTRY) {
						tableHeader = "<table style='width: 200px;'><thead class='informationWindowHeader'><tr><th>Region</th><th>Hosts</th></tr></thead>";
					} else if(i == REGION || i == CITY) {
						tableHeader = "<table style='width: 200px;'><thead class='informationWindowHeader'><tr><th>City</th><th>Hosts</th></tr></thead>";
					} else { // i == HOST
						tableHeader = "<table style='width: 400px;'><thead class='informationWindowHeader'><tr><th>IP</th><th>Flows</th><th>Protocol</th><th>Port</th><th>Location</th></tr></thead>";
					}
					
					var orderArray = new Array(); // Contains an ordered list of markerRecord IDs (array indices)
					orderArray.push(0); // The first element to be considered is always the biggest/smallest
					if(i == COUNTRY || i == REGION || i == CITY) { // Sorted by hosts
						for(var k = 1; k < markerProperties[i][j].markerRecords.length; k++) {
							for(var l = 0; l < orderArray.length; l++) {
								if(markerProperties[i][j].markerRecords[k].hosts >= markerProperties[i][j].markerRecords[orderArray[l]].hosts) {
									orderArray.splice(l, 0, k);
									break;
								} else if(l == orderArray.length - 1) {
									orderArray.splice(orderArray.length, 0, k);
									break;
								}
							}
						}
					} else { // i == HOST, sorted by flows
						for(var k = 1; k < markerProperties[i][j].markerRecords.length; k++) {
							for(var l = 0; l < orderArray.length; l++) {
								if(markerProperties[i][j].markerRecords[k].flows >= markerProperties[i][j].markerRecords[orderArray[l]].flows) {
									orderArray.splice(l, 0, k);
									break;
								} else if(l == orderArray.length - 1) {
									orderArray.splice(orderArray.length, 0, k);
									break;
								}
							}
						}
					}
					
					var flowIDsString = ""; // Contains IDs of the flows that are aggregated in the current marker
					var tableBody = "<tbody class='informationWindowBody'>";
					for(var k = 0; k < orderArray.length; k++) {
						var orderArrayIndex = orderArray[k];
						if(i == COUNTRY) {
							tableBody += "<tr><td>" + formatName(markerProperties[i][j].markerRecords[orderArrayIndex].name) + "</td>";
							tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].hosts + "</td></tr>";
						} else if(i == REGION || i == CITY) {
							tableBody += "<tr><td>" + formatName(markerProperties[i][j].markerRecords[orderArrayIndex].name) + "</td>";
							tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].hosts + "</td></tr>";
						} else { // i == HOST
							var recordCount = markerProperties[i][j].markerRecords.length;
							
							// TODO Handle case where more than MAX_INFO_WINDOW_LINES lines are present in information window
							
							if(k == 0) {
								tableBody += "<tr><td>" + markerProperties[i][j].markerRecords[orderArrayIndex].name + "</td>";
								tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].flows + "</td>";
								tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].protocol + "</td>";
								tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].port + "</td>";
								tableBody += "<td rowspan='" + recordCount + "'>" + formatName(markerProperties[i][j].markerRecords[orderArrayIndex].countryName) + "<br />" + formatName(markerProperties[i][j].markerRecords[orderArrayIndex].regionName) + "<br />" + formatName(markerProperties[i][j].markerRecords[orderArrayIndex].cityName) + "</td></tr>";
							} else {
								tableBody += "<tr><td>" + markerProperties[i][j].markerRecords[orderArrayIndex].name + "</td>";
								tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].flows + "</td>";
								tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].protocol + "</td>";
								tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].port + "</td></tr>";
							}
						}
						for(var l = 0; l < markerProperties[i][j].markerRecords[orderArrayIndex].flowRecordIDs.length; l++) {
							if(flowIDsString != "") flowIDsString += "_";
							flowIDsString += markerProperties[i][j].markerRecords[orderArrayIndex].flowRecordIDs[l];
						}
					}
					
					tableBody += "</tbody>";
					
					var tableFooter = "</table><br /><div class='informationWindowFooter'>"
					 		+ "<a href = 'Javascript:zoom(1, 0, null)'>Zoom In</a><b> - </b><a href = 'Javascript:zoom(1, 1, null)'>Zoom Out</a><br />"
					 		+ "<a href = 'Javascript:zoom(0, 0, null)'>Quick Zoom In</a><b> - </b><a href = 'Javascript:zoom(0, 1, null)'>Quick Zoom Out</a><br />"
							+ "<a href='Javascript:showNetFlowDetails(\"" + flowIDsString + "\");'>Flow details</a></div>";
					
					markers[i].push(createMarker(i, new google.maps.LatLng(markerProperties[i][j].lat, markerProperties[i][j].lng), "<div id=\"content\">" + tableHeader + tableBody + tableFooter + "</div>"));
				}
			}

			initializeMarkerManager();
		}
		
	   /**
		* This function initializes all lines for all zoom levels.
		*/			
		function initializeLines() {
			for(var i = 0; i < 4; i++) { // Zoom levels
				lineProperties[i] = []; // Initialize lineProperties storage
				
				for(var j = 0; j < flowRecordCount; j++) {
					var existValue;
					switch(i) {
						case COUNTRY: 	if((flowRecords[j].srcCountryLat == 0 && flowRecords[j].srcCountryLng == 0) || (flowRecords[j].dstCountryLat == 0 && flowRecords[j].dstCountryLng == 0)) {
											continue;
										}
										existValue = lineExists(i, flowRecords[j].srcCountryLat, flowRecords[j].srcCountryLng, flowRecords[j].dstCountryLat, flowRecords[j].dstCountryLng);
										break;
										
						case REGION: 	if((flowRecords[j].srcRegionLat == 0 && flowRecords[j].srcRegionLng == 0) || (flowRecords[j].dstRegionLat == 0 && flowRecords[j].dstRegionLng == 0)) {
											continue;
										}
										existValue = lineExists(i, flowRecords[j].srcRegionLat, flowRecords[j].srcRegionLng, flowRecords[j].dstRegionLat, flowRecords[j].dstRegionLng);
										break;
										
						default: 		if((flowRecords[j].srcCityLat == 0 && flowRecords[j].srcCityLng == 0) || (flowRecords[j].dstCityLat == 0 && flowRecords[j].dstCityLng == 0)) {
											continue;
										}
										existValue = lineExists(i, flowRecords[j].srcCityLat, flowRecords[j].srcCityLng, flowRecords[j].dstCityLat, flowRecords[j].dstCityLng);
					}
					
					if(existValue == -1) { // Line does not exist
						var properties;
						var record;
						
						switch(i) {
							case COUNTRY: 	properties = new LineProperties(flowRecords[j].srcCountryLat, flowRecords[j].srcCountryLng, flowRecords[j].dstCountryLat, flowRecords[j].dstCountryLng);
											record = new LineRecord(flowRecords[j].srcCountry, flowRecords[j].dstCountry);
											break;
							
							case REGION: 	properties = new LineProperties(flowRecords[j].srcRegionLat, flowRecords[j].srcRegionLng, flowRecords[j].dstRegionLat, flowRecords[j].dstRegionLng);
											record = new LineRecord(flowRecords[j].srcRegion, flowRecords[j].dstRegion);
											record.srcParentCountryName = flowRecords[j].srcCountry;
											record.dstParentCountryName = flowRecords[j].dstCountry;
											break;
															
							case CITY: 		properties = new LineProperties(flowRecords[j].srcCityLat, flowRecords[j].srcCityLng, flowRecords[j].dstCityLat, flowRecords[j].dstCityLng);
											record = new LineRecord(flowRecords[j].srcCity, flowRecords[j].dstCity);
											record.srcParentCountryName = flowRecords[j].srcCountry;
											record.dstParentCountryName = flowRecords[j].dstCountry;
											record.srcParentRegionName = flowRecords[j].srcRegion;
											record.dstParentRegionName = flowRecords[j].dstRegion;
											break;
											
							case HOST: 		properties = new LineProperties(flowRecords[j].srcCityLat, flowRecords[j].srcCityLng, flowRecords[j].dstCityLat, flowRecords[j].dstCityLng);
											record = new LineRecord(flowRecords[j].srcIP, flowRecords[j].dstIP);
											record.srcParentCountryName = flowRecords[j].srcCountry;
											record.dstParentCountryName = flowRecords[j].dstCountry;
											record.srcParentRegionName = flowRecords[j].srcRegion;
											record.dstParentRegionName = flowRecords[j].dstRegion;
											record.srcParentCityName = flowRecords[j].srcCity;
											record.dstParentCityName = flowRecords[j].dstCity;
											break;
						}
						
						record.packets = flowRecords[j].packets;
						record.octets = flowRecords[j].octets;
						record.duration = flowRecords[j].duration;

						record.throughput = record.octets / record.duration;
						if(record.throughput == "NaN" || record.throughput == "Infinity") {
							record.throughput = 0;
						} else {
							record.throughput = record.throughput.toFixed(2);
						}

						record.flowRecordIDs.push(j);

						if(nfsenOption == 1) {
							record.flows = flowRecords[j].flows;
						}

						properties.lineRecords.push(record);
						lineProperties[i].push(properties);
					} else { // Line exists
						var existValue2;
						switch(i) {
							case COUNTRY: 	existValue2 = lineRecordExists(i, existValue, flowRecords[j].srcCountry, flowRecords[j].dstCountry);
											break;
											
							case REGION: 	existValue2 = lineRecordExists(i, existValue, flowRecords[j].srcRegion, flowRecords[j].dstRegion);
											break;
											
							case CITY: 		existValue2 = lineRecordExists(i, existValue, flowRecords[j].srcCity, flowRecords[j].dstCity);
											break;

							case HOST: 		existValue2 = lineRecordExists(i, existValue, flowRecords[j].srcIP, flowRecords[j].dstIP);
											break;
						}

						if(existValue2 == -1) { // Source and destination are not present in one record
							switch(i) {
								case COUNTRY: 	record = new LineRecord(flowRecords[j].srcCountry, flowRecords[j].dstCountry);
												break;

								case REGION: 	record = new LineRecord(flowRecords[j].srcRegion, flowRecords[j].dstRegion);
												record.srcParentCountryName = flowRecords[j].srcCountry;
												record.dstParentCountryName = flowRecords[j].dstCountry;
												break;

								case CITY: 		record = new LineRecord(flowRecords[j].srcCity, flowRecords[j].dstCity);
												record.srcParentCountryName = flowRecords[j].srcCountry;
												record.dstParentCountryName = flowRecords[j].dstCountry;
												record.srcParentRegionName = flowRecords[j].srcRegion;
												record.dstParentRegionName = flowRecords[j].dstRegion;
												break;
												
								case HOST: 		record = new LineRecord(flowRecords[j].srcIP, flowRecords[j].dstIP);
												record.srcParentCountryName = flowRecords[j].srcCountry;
												record.dstParentCountryName = flowRecords[j].dstCountry;
												record.srcParentRegionName = flowRecords[j].srcRegion;
												record.dstParentRegionName = flowRecords[j].dstRegion;
												record.srcParentCityName = flowRecords[j].srcCity;
												record.dstParentCityName = flowRecords[j].dstCity;
												break;
							}

							record.packets = flowRecords[j].packets;
							record.octets = flowRecords[j].octets;
							record.duration = flowRecords[j].duration;

							record.throughput = record.octets / record.duration;
							if(record.throughput == "NaN" || record.throughput == "Infinity") {
								record.throughput = 0;
							} else {
								record.throughput = record.throughput.toFixed(2);
							}

							record.flowRecordIDs.push(j);

							if(nfsenOption == 1) {
								record.flows = flowRecords[j].flows;
							}
							lineProperties[i][existValue].lineRecords.push(record);
						} else { // Source and destination are present in one record
							lineProperties[i][existValue].lineRecords[existValue2].packets = parseFloat(lineProperties[i][existValue].lineRecords[existValue2].packets) + parseFloat(flowRecords[j].packets);
							lineProperties[i][existValue].lineRecords[existValue2].octets = parseFloat(lineProperties[i][existValue].lineRecords[existValue2].octets) + parseFloat(flowRecords[j].octets);
							lineProperties[i][existValue].lineRecords[existValue2].duration = parseFloat(lineProperties[i][existValue].lineRecords[existValue2].duration) + parseFloat(flowRecords[j].duration);

							lineProperties[i][existValue].lineRecords[existValue2].throughput = lineProperties[i][existValue].lineRecords[existValue2].octets / lineProperties[i][existValue].lineRecords[existValue2].duration;
							if(lineProperties[i][existValue].lineRecords[existValue2].throughput == "NaN" || lineProperties[i][existValue].lineRecords[existValue2].throughput == "Infinity") {
								lineProperties[i][existValue].lineRecords[existValue2].throughput = 0;
							} else {
								lineProperties[i][existValue].lineRecords[existValue2].throughput = lineProperties[i][existValue].lineRecords[existValue2].throughput.toFixed(2);
							}

							lineProperties[i][existValue].lineRecords[existValue2].flowRecordIDs.push(j);

							if(nfsenOption == 1) {
								lineProperties[i][existValue].lineRecords[existValue2].flows = parseInt(lineProperties[i][existValue].lineRecords[existValue2].flows) + parseInt(flowRecords[j].flows);
							} else {
								lineProperties[i][existValue].lineRecords[existValue2].flows++;
							}
						}
					}
				}

				lines[i] = []; // Initialize lines storage
				determineLineColorRanges(i, nfsenStatOrder);
				
				for(var j = 0; j < lineProperties[i].length; j++) {
					var tableHeader = "<table style='width: 500px;'><thead class='informationWindowHeader'><tr>";
					tableHeader += "<th>Source</th>";
					tableHeader += "<th>Destination</th>";
					tableHeader += "<th>Flows</th>";
					tableHeader += "<th>Packets</th>";
					tableHeader += "<th>Octets</th>";
					tableHeader += "<th>Throughput</th></tr></thead>";

					var orderArray = new Array(); // Contains an ordered list of lineRecord IDs (array indices)
					orderArray.push(0); // The first element to be considered is always the biggest/smallest
					for(var k = 1; k < lineProperties[i][j].lineRecords.length; k++) {
						for(var l = 0; l < orderArray.length; l++) {
							// Ordering in information window is done based on flows
							if(lineProperties[i][j].lineRecords[k].flows >= lineProperties[i][j].lineRecords[orderArray[l]].flows) {
								orderArray.splice(l, 0, k);
								break;
							} else if(l == orderArray.length - 1) {
								orderArray.splice(orderArray.length, 0, k);
								break;
							}
						}
					}
					
					var flowIDsString = ""; // Contains IDs of the flows that are aggregated in the current line
					var tableBody = "<tbody class='informationWindowBody' style='vertical-align:text-top;'>";
					for(var k = 0; k < orderArray.length; k++) {
						var orderArrayIndex = orderArray[k];
						if(i == COUNTRY) {
							tableBody += "<tr><td><b>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcName) + "</b></td>";
							tableBody += "<td><b>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstName) + "</b></td>";
							tableBody += "<td>" + lineProperties[i][j].lineRecords[orderArrayIndex].flows + "</td>";
							tableBody += "<td>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].packets) + "</td>";
							tableBody += "<td>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].octets) + "</td>";
							tableBody += "<td>" + formatThroughput(lineProperties[i][j].lineRecords[orderArrayIndex].throughput) + "</td></tr>";
						} else if(i == REGION) {
							tableBody += "<tr><td><b>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentCountryName) + "</b></td>";
							tableBody += "<td><b>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentCountryName) + "</b></td>";
							tableBody += "<td rowspan='2'>" + lineProperties[i][j].lineRecords[orderArrayIndex].flows + "</td>";
							tableBody += "<td rowspan='2'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].packets) + "</td>";
							tableBody += "<td rowspan='2'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].octets) + "</td>";
							tableBody += "<td rowspan='2'>" + formatThroughput(lineProperties[i][j].lineRecords[orderArrayIndex].throughput) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstName) + "</td></tr>";
						} else if(i == CITY) {
							tableBody += "<tr><td><b>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentCountryName) + "</b></td>";
							tableBody += "<td><b>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentCountryName) + "</b></td>";
							tableBody += "<td rowspan='3'>" + lineProperties[i][j].lineRecords[orderArrayIndex].flows + "</td>";
							tableBody += "<td rowspan='3'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].packets) + "</td>";
							tableBody += "<td rowspan='3'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].octets) + "</td>";
							tableBody += "<td rowspan='3'>" + formatThroughput(lineProperties[i][j].lineRecords[orderArrayIndex].throughput) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentRegionName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentRegionName) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstName) + "</td></tr>";
						} else { // i == HOST
							tableBody += "<tr><td><b>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentCountryName) + "</b></td>";
							tableBody += "<td><b>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentCountryName) + "</b></td>";
							tableBody += "<td rowspan='4'>" + lineProperties[i][j].lineRecords[orderArrayIndex].flows + "</td>";
							tableBody += "<td rowspan='4'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].packets) + "</td>";
							tableBody += "<td rowspan='4'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].octets) + "</td>";
							tableBody += "<td rowspan='4'>" + formatThroughput(lineProperties[i][j].lineRecords[orderArrayIndex].throughput) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentRegionName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentRegionName) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentCityName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentCityName) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstName) + "</td></tr>";
						}

						for(var l = 0; l < lineProperties[i][j].lineRecords[orderArrayIndex].flowRecordIDs.length; l++) {
							if(flowIDsString != "") flowIDsString += "_";
							flowIDsString += lineProperties[i][j].lineRecords[orderArrayIndex].flowRecordIDs[l];
						}
					}
					
					tableBody += "</tbody>";

					var tableFooter = "</table><br /><div class='informationWindowFooter'>"
							+ "<a href = 'Javascript:zoom(1, 0, null)'>Zoom In</a><b> - </b><a href = 'Javascript:zoom(1, 1, null)'>Zoom Out</a><b>  |  </b>"
							+ "<a href = 'Javascript:zoom(0, 0, null)'>Quick Zoom In</a><b> - </b><a href = 'Javascript:zoom(0, 1, null)'>Quick Zoom Out</a><b>  |  </b>"
					 		+ "<a href='Javascript:showNetFlowDetails(\"" + flowIDsString + "\");'>Flow details</a><br />"
					 		+ "<a href='Javascript:goToLineEndPoint(" + i + ", " + j + ", \"source\");' title='" + formatName(lineProperties[i][j].lineRecords[0].srcName) + "'>Go to source</a><b> - </b><a href='Javascript:goToLineEndPoint(" + i + ", " + j + ", \"destination\");' title='" + formatName(lineProperties[i][j].lineRecords[0].dstName) + "'>Go to destination</a></div>";
					
					var lineTotal = 0;
					for(var k = 0; k < lineProperties[i][j].lineRecords.length; k++) {
						if(nfsenStatOrder == "flows") {
							lineTotal += parseInt(lineProperties[i][j].lineRecords[k].flows);
						} else if(nfsenStatOrder == "packets") {
							lineTotal += parseInt(lineProperties[i][j].lineRecords[k].packets);
						} else if(nfsenStatOrder == "bytes") {
							lineTotal += parseInt(lineProperties[i][j].lineRecords[k].octets);
						} else {
							alert("Type error in initializeLines()!")
						}
					}
					lines[i].push(createLine(new google.maps.LatLng(lineProperties[i][j].lat1, lineProperties[i][j].lng1), 
							new google.maps.LatLng(lineProperties[i][j].lat2, lineProperties[i][j].lng2), 
							"<div id=\"content\">" + tableHeader + tableBody + tableFooter + "</div>", 
							determineLineColor(lineTotal)));
				}
			}
			
			refreshLineOverlays(currentSURFmapZoomLevel);
		}
		
	   /**
		* Determines the line color ranges based on the current flow property (either flows, packets or bytes).
		* Parameters:
		*	level - a SURFmap zoom level
		*	property - either flows, packets or bytes
		*/
		function determineLineColorRanges(level, property) {
			var min = 1;
			var max = 1;
			
			for(var i = 0; i < lineProperties[level].length; i++) {
				var lineTotal = 0;
				
				if(property == "flows") {
					for(var j = 0; j < lineProperties[level][i].lineRecords.length; j++) {
						if(!(IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION == 1 && lineProperties[level][i].lineRecords[j].srcName == lineProperties[level][i].lineRecords[j].dstName)) {
							oldLineTotal = lineTotal;
							lineTotal += parseInt(lineProperties[level][i].lineRecords[j].flows);
						}
					}
				} else if(property == "packets") {
					for(var j = 0; j < lineProperties[level][i].lineRecords.length; j++) {
						if(!(IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION == 1 && lineProperties[level][i].lineRecords[j].srcName == lineProperties[level][i].lineRecords[j].dstName)) {
							lineTotal += parseInt(lineProperties[level][i].lineRecords[j].packets);
						}
					}
				} else if(property == "bytes") {
					for(var j = 0; j < lineProperties[level][i].lineRecords.length; j++) {
						if(!(IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION == 1 && lineProperties[level][i].lineRecords[j].srcName == lineProperties[level][i].lineRecords[j].dstName)) {
							lineTotal += parseInt(lineProperties[level][i].lineRecords[j].octets);
						}
					}
				}

				if(lineTotal < min) min = lineTotal;
				else if(lineTotal > max) max = lineTotal;
			}

			var delta = max - min;
			var categoryDelta = delta / lineColors;
			
			for(var i = 0; i < lineColors + 1; i++) {
				lineColorClassification[i] = min + (i * categoryDelta);
			}
		}
		
	   /**
		* Returns the actual line color of a line, based on the classification made in determineLineColorRanges().
		* Parameters:
		*	lineTotal - sum of either flows, packets or bytes of the specific line
		*/
		function determineLineColor(lineTotal) {
			if(lineColors == 2) {
				if(lineTotal >= lineColorClassification[0] && lineTotal < lineColorClassification[1]) return orange;
				else if(lineTotal >= lineColorClassification[1] && lineTotal <= lineColorClassification[2]) return red;
				else return red;
			} else if(lineColors == 3) {
				if(lineTotal >= lineColorClassification[0] && lineTotal < lineColorClassification[1]) return yellow;
				else if(lineTotal >= lineColorClassification[1] && lineTotal < lineColorClassification[2]) return orange;
				else if(lineTotal >= lineColorClassification[2] && lineTotal <= lineColorClassification[3]) return red;
				else return red;
			} else if(lineColors == 4) {				
				if(lineTotal >= lineColorClassification[0] && lineTotal < lineColorClassification[1]) return green;
				else if(lineTotal >= lineColorClassification[1] && lineTotal < lineColorClassification[2]) return yellow;
				else if(lineTotal >= lineColorClassification[2] && lineTotal < lineColorClassification[3]) return orange;
				else if(lineTotal >= lineColorClassification[3] && lineTotal <= lineColorClassification[4]) return red;
				else return red;
			} else {
				return red;
			}
		}
		
	   /**
		* This function creates GMarkers, according to the specified coordinates
		* and puts the specified text into the marker's information window.
		* Parameters:
		*	level - the SURFmap zoom level for which the marker is created
		*	coordinates - the coordinates on which the marker should be created
		*	text - the text that has to be put into the marker's information window
		*/			
		function createMarker(level, coordinates, text) {
			var internalTrafficMarker = 0;
			
			for(var i = 0; i < lines[level].length; i++) {
				var minSizeLat = Math.min(
						lineProperties[level][i].lat1.toString().length, 
						lineProperties[level][i].lat2.toString().length, 
						coordinates.lat().toString().length);
	
				var minSizeLng = Math.min( 
						lineProperties[level][i].lng1.toString().length,
						lineProperties[level][i].lng2.toString().length,
						coordinates.lng().toString().length);	

				if(lineProperties[level][i].lat1.toString().substr(0, minSizeLat) == coordinates.lat().toString().substr(0, minSizeLat) 
						&& lineProperties[level][i].lat1.toString().substr(0, minSizeLat) == lineProperties[level][i].lat2.toString().substr(0, minSizeLat)
						&& lineProperties[level][i].lng1.toString().substr(0, minSizeLng) == coordinates.lng().toString().substr(0, minSizeLng)
						&& lineProperties[level][i].lng1.toString().substr(0, minSizeLng) == lineProperties[level][i].lng2.toString().substr(0, minSizeLng)) {
					internalTrafficMarker = 1;
					break;
				}
			}
			
			var markerOptions;
			if(internalTrafficMarker == 1) {
				markerOptions = {
					position: coordinates,
					icon: greenIcon
				}
			} else {
				markerOptions = {
					position: coordinates
				}
			}
			var marker = new google.maps.Marker(markerOptions);

			google.maps.event.addListener(marker, "click", function(event) {
				document.getElementById("netflowDataDetails").innerHTML = "";
				map.setCenter(event.latLng);
				infoWindow.close();
				infoWindow.setContent(text);
				infoWindow.open(map, marker);
			});

			return marker;
		}
		
	   /**
		* This function creates GPolylines, according to the specified coordinates
		* and puts the specified text into the line's information window.
		* Parameters:
		*	coordinate1 - one end point of the line
		*	coordinate2 - one end point of the line
		*	text - the text that has to be put into the line's information window
		*	color - color of the line (used for line color classification)
		*/
		function createLine(coordinate1, coordinate2, text, color) {
			var lineOptions = {
				geodesic: true,
				path: [coordinate1, coordinate2],
				strokeColor: color,
				strokeOpacity: 1.0,
				strokeWeight: 2
			}
			var line = new google.maps.Polyline(lineOptions);
			
			google.maps.event.addListener(line, "click", function(event) {
				document.getElementById("netflowDataDetails").innerHTML = "";
				map.setCenter(event.latLng);
				infoWindow.close();
				
				if(event.latLng == undefined) {
					// When clickRandomLine(level) is used, a google.maps.LatLng object is passed as the 'event' parameter
					infoWindow.setPosition(event);
				} else {
					infoWindow.setPosition(event.latLng);
				}
				
				infoWindow.setContent(text);
				infoWindow.open(map);
			});
			
			return line;
		}
		
		/*
		 * Adds debugging information to the INFO log queue.
		 */
		function printDebugLogging() {
			addToLogQueue("DEBUG", "Application version: " + applicationVersion);
			addToLogQueue("DEBUG", "DemoMode: " + demoMode);
			addToLogQueue("DEBUG", "EntryCount: " + entryCount);
			addToLogQueue("DEBUG", "FlowRecordCount: " + flowRecordCount);
			addToLogQueue("DEBUG", "NfSenQuery: " + nfsenQuery);
			addToLogQueue("DEBUG", "NfSenFilter: " + nfsenFilter);
			addToLogQueue("DEBUG", "NfSenAllSources: " + nfsenAllSources);
			addToLogQueue("DEBUG", "NfSenSelectedSources: " + nfsenSelectedSources);

			addToLogQueue("DEBUG", "Date1: " + date1);
			addToLogQueue("DEBUG", "Date2: " + date2);
			addToLogQueue("DEBUG", "Hours1: " + hours1);
			addToLogQueue("DEBUG", "Hours2: " + hours2);
			addToLogQueue("DEBUG", "Minutes1: " + minutes1);
			addToLogQueue("DEBUG", "Minutes2: " + minutes2);
			addToLogQueue("DEBUG", "LatestDate: " + latestDate);
			addToLogQueue("DEBUG", "LatestHour: " + latestHour);
			addToLogQueue("DEBUG", "LatestMinute: " + latestMinute);
			
			addToLogQueue("DEBUG", "AutoRefresh: " + autoRefresh);
			addToLogQueue("DEBUG", "ErrorCode: " + getErrorCode());
			
			if(getErrorMessage() == "") {
				addToLogQueue("DEBUG", "ErrorMessage: (empty)");
			} else {
				addToLogQueue("DEBUG", "ErrorMessage: " + getErrorMessage());
			}
			
			addToLogQueue("DEBUG", "PHP version: <?php echo phpversion(); ?>");
			addToLogQueue("DEBUG", "Client Web browser: " + navigator.appName + "(" + navigator.appVersion + ")");
		}
		
		/*
		 * Parses and returns the error code of the current session.
		 */		
		function getErrorCode() {
			if(errorCode != "") return parseInt(errorCode);
			else return 0;
		}
		
		/*
		 * Parses and returns the error code of the current session, if available.
		 */		
		function getErrorMessage() {
			return errorMessage;
		}
		
	   /**
		* This function is called when automatically when loading the SURFmap Web page.
		* It contains the first stage of processing.
		*/
		function initialize() {
			if(debugLogging == 1) printDebugLogging();
			setProgressBarValue("progressBar", 10);
			readPHPLogQueue("INFO");
			readPHPLogQueue("ERROR");
			
			if(demoMode == 1) {
				currentSURFmapZoomLevel = demoModeInitialSURFmapZoomLevel;
				currentZoomLevel = getGoogleMapsZoomLevel(demoModeInitialSURFmapZoomLevel) + 1;
			} else {
				currentZoomLevel = initialZoomLevel;
				currentSURFmapZoomLevel = getSurfmapZoomLevel(initialZoomLevel);
			}
			map = initializeMap(mapCenter, currentZoomLevel, minZoomLevel, maxZoomLevel);
			
			google.maps.event.addListener(map, "click", function() {
				infoWindow.close();
			});
			google.maps.event.addListener(map, "dragend", function() {
				SESSION_queue.push(new SessionData("mapCenter", map.getCenter().lat() + "," + map.getCenter().lng()));
			});
			google.maps.event.addListener(map, "zoom_changed", function() {
				var newZoomLevel = map.getZoom();
				var newSurfmapZoomLevel = getSurfmapZoomLevel(newZoomLevel);
				SESSION_queue.push(new SessionData("zoomLevel", newZoomLevel));
				
				if(currentSURFmapZoomLevel != newSurfmapZoomLevel) {
					infoWindow.close();
					document.getElementById("netflowDataDetails").innerHTML = "";
					
					refreshLineOverlays(newSurfmapZoomLevel);
					changeZoomLevelPanel(currentSURFmapZoomLevel, newSurfmapZoomLevel);
					initializeLegend(newSurfmapZoomLevel);
					
					currentSURFmapZoomLevel = newSurfmapZoomLevel;
				}
				
				google.maps.event.addListenerOnce(map, "idle", function() {
					if(map.getCenter() != undefined && map.getCenter().equals(mapCenterWithoutGray) && !map.getCenter().equals(mapCenter)) {
						/*
						 * If the map center was adjusted due to a gray area at the top or bottom of the map, 
						 * change its center again.
						 * In demo mode, when a random line is clicked by SURFmap, map.getCenter() can be undefined.
						 */					
						map.setCenter(mapCenter);
					}
					mapCenterWithoutGray = hideGrayMapAreas();
				});
			});
			
			markerManager = new MarkerManager(map);
			geocoder = new google.maps.Geocoder();
			infoWindow = new google.maps.InfoWindow({maxWidth: 1000});
			
			setProgressBarValue("progressBar", 20);
			changeZoomLevelPanel(0, currentSURFmapZoomLevel);
			if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 1. Basic initialization completed");

			if(getErrorCode() == 1) {
				generateAlert("filterError");
				addToLogQueue("INFO", "Stopped initialization due to filter error");
				serverTransactions();
				return;
			} else if(getErrorCode() == 4) {
				if(showWarningOnFileError == 1) {
					generateAlert("fileError");
				}
				addToLogQueue("INFO", "Stopped initialization due to file error");
				serverTransactions();
				return;
			} else if(getErrorCode() == 6) {
				generateAlert("profileError");
				addToLogQueue("INFO", "Stopped initialization due to profile error");
				serverTransactions();
				return;
			}

			setProgressBarValue("progressBar", 30, "Importing NetFlow data...");
			if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 2. Importing NetFlow data...");
			importData();
			if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 2. Importing NetFlow data... Done");
			
			setProgressBarValue("progressBar", 40, "Complementing flow records");
			if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 3. Complementing flow records...");
			complementFlowRecords();
			if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 3. Complementing flow records... Done");
			
			setInterval("serverTransactions()", 2000);
		}
		
	   /**
		* This function contains the second stage of processing.
		*/		
		function processing() {
			setProgressBarValue("progressBar", 70, "Initializing lines...");
			if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 4. Initializing lines...");
			initializeLines();
			if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 4. Initializing lines... Done");

			setProgressBarValue("progressBar", 80, "Initializing markers...");
			if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 5. Initializing markers...");
			initializeMarkers();
			if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 5. Initializing markers... Done");

			if(demoMode == 0) {
				setProgressBarValue("progressBar", 90, "Initializing legend...");
				if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 6. Initializing legend...");
				initializeLegend(currentSURFmapZoomLevel);
				if(debugLogging == 1) addToLogQueue("DEBUG", "Progress: 6. Initializing legend... Done");
			}

			// If a gray area is present at the top or bottom of the map, change its center
			mapCenterWithoutGray = hideGrayMapAreas();

			setProgressBarValue("progressBar", 100, "Finished loading...");
			addToLogQueue("INFO", "Initialized");
			
			$("#dialog").dialog("destroy"); // Hide progress bar
			
			if(getErrorCode() >= 2 && getErrorCode() <= 4) {
				generateAlert("invalidWindow");
			}
		}

	</script>
</head>
	
<body onload="initialize()">
	<div id="header" style="width:<?php if(strpos($RELATIVE_MAP_WIDTH, "%") === false) echo "$RELATIVE_MAP_WIDTH%"; else echo $RELATIVE_MAP_WIDTH; ?>;clear:both;">
		<a href="http://www.utwente.nl/en" target="_blank"><img src="images/UT_Logo.png" style="height:76px;width:280px;float:right;" alt="University of Twente"/></a>
	
	<script type="text/javascript">
		if(demoMode == 1) {
			document.write("<div id=\"header-text-demo\" style=\"height:76px;font-size:200%;text-align:center;\" display=\"inline-block\" >" + demoModePageTitle + " (" + hours1 + ":" + minutes1 + ")</div>");
		} else {
			document.write("<div id=\"header-text\" style=\"height:76px;font-size:85%;float:right;\" display=\"inline-block\" ><br><b>SURFmap</b><br><i>A network monitoring tool based on the Google Maps API</i></div>");
		}
	</script>
	
	</div>
	
	<div id="map_canvas" style="width:<?php if(strpos($RELATIVE_MAP_WIDTH, "%") === false) echo "$RELATIVE_MAP_WIDTH%"; else echo $RELATIVE_MAP_WIDTH; ?>; height:<?php if(strpos($RELATIVE_MAP_HEIGHT, "%") === false) echo "$RELATIVE_MAP_HEIGHT%"; else echo $RELATIVE_MAP_HEIGHT; ?>;"></div>
	<div id="footer" style="width:<?php if(strpos($RELATIVE_MAP_WIDTH, "%") === false) echo "$RELATIVE_MAP_WIDTH%"; else echo $RELATIVE_MAP_WIDTH; ?>;">
		<div class="footer" id="legend">
			<div class="legendTextCell" id="legend_based_on" style="width: 175px; padding-left:0px;"></div>
			<div class="legendImageCell" style="padding-left:10px;"><img src="images/legend/legend_green.png" alt="Legend_green"/></div><div class="legendTextCell" id="legend_green"></div>
			<div class="legendImageCell"><img src="images/legend/legend_yellow.png" alt="legend_yellow"/></div><div class="legendTextCell" id="legend_yellow"></div>
			<div class="legendImageCell"><img src="images/legend/legend_orange.png" alt="legend_orange"/></div><div class="legendTextCell" id="legend_orange"></div>
			<div class="legendImageCell"><img src="images/legend/legend_red.png" alt="legend_red"/></div><div class="legendTextCell" id="legend_red"></div>
		</div>
		<div class="footer" id="footerfunctions" style='float:right;'><a href="Javascript:showNetFlowDetails('');" title="Show flow details">Flow details</a> | <a href="Javascript:generateDialog('help', '');" title="Show help information">Help</a> | <a href="Javascript:generateDialog('about','');" title="Show about information">About</a></div>
	</div>
	
	<div class="panel">
		<div class="panelSectionTitle"><p>Zoom levels</p></div>
		<div class="panelSectionContent" id="zoomLevelPanel"></div>
		<hr />
		<div class="panelSectionTitle"><p>Options</p></div>
		<div class="panelSectionContent" id="optionPanel"></div>
	</div>
	<a class="trigger" href="#">Menu</a>
	
	<div id='netflowDataDetails' style='margin-top: 10px;'></div>
	<div id="dialog"></div>
	
	<script type="text/javascript">
		// Panel: Zoom levels
		document.getElementById("zoomLevelPanel").innerHTML = "<table id=\"zoomLevels\"><tr><td style=\"width:85px;\">"
			+ "<form><input type=\"radio\" id=\"countryZoomRadio\" name=\"zoomLevel\" value=\"country\" onclick=\"zoom(1, 0, 2);\" />Country<br />"
			+ "<input type=\"radio\" id=\"regionZoomRadio\" name=\"zoomLevel\" value=\"region\" onclick=\"zoom(1, 0, 5);\" />Region<br />"
			+ "<input type=\"radio\" id=\"cityZoomRadio\" name=\"zoomLevel\" value=\"city\" onclick=\"zoom(1, 0, 8);\" />City<br />"
			+ "<input type=\"radio\" id=\"hostZoomRadio\" name=\"zoomLevel\" value=\"host\" onclick=\"zoom(1, 0, 11);\" />Host<br />"
			+ "</form></td><td style=\"vertical-align:bottom;\">"
			+ "<input type=\"checkbox\" id=\"auto-refresh\" onclick=\"manageAutoRefresh()\" />Auto-refresh</td></tr></table>";
		
		// Panel: Options
		var truncatedNfSenProfile = (nfsenProfile.length > 22) ? nfsenProfile.substr(0, 22) + "..." : nfsenProfile;
		var nfsenSourceOptions = "<optgroup label=\"Profile '" + truncatedNfSenProfile + "'\">";
		for(var i = 0; i < nfsenAllSources.length; i++) {
			var sourceSelected = false;
			for(var j = 0; j < nfsenSelectedSources.length; j++) {
				if(nfsenSelectedSources[j] == nfsenAllSources[i]) {
					sourceSelected = true;
				}
			}

			if(sourceSelected) {
				nfsenSourceOptions += "<option selected>" + nfsenAllSources[i] + "</option>";
			} else {
				nfsenSourceOptions += "<option>" + nfsenAllSources[i] + "</option>";
			}
		}
		nfsenSourceOptions += "</optgroup>";
		
		document.getElementById("optionPanel").innerHTML = "<form id=\"options\" method=\"GET\" action=\"index.php\">"
			+ "<table>"
				+ "<tr>"
					+ "<td style=\"width:90px; \">"
						+ "Sources"
					+ "</td>"
					+ "<td>"
						+ "<select id=\"nfsensources\" name=\"nfsensources[]\" multiple=\"multiple\" style=\"height:20px;\" >" + nfsenSourceOptions + "</select>"
					+ "</td>"
				+ "</tr>"
			+ "</table><br /><table>"	
				+ "<tr>"
					+ "<td style=\"width:90px; vertical-align:center;\">"
						+ "<input type=\"radio\" id=\"nfsenoptionStatTopN\" name=\"nfsenoption\" value=\"1\" onclick=\"document.getElementById('nfsenstatorder').disabled = false;\" />Stat TopN"
					+ "</td>"
					+ "<td>"
						+ "<select id=\"nfsenstatorder\" name=\"nfsenstatorder\"><option>flows</option><option>packets</option><option>bytes</option></select>"
					+ "</td>"
				+ "</tr>"
				+ "<tr>"
					+ "<td>"
						+ "<input type=\"radio\" id=\"nfsenoptionListFlows\" name=\"nfsenoption\" value=\"0\" onclick=\"document.getElementById('nfsenstatorder').disabled = true;\" />List Flows"
					+ "</td>"
					+ "<td>"
					+ "</td>"					
				+ "</tr>"
			+ "</table><br /><table>"
				+ "<tr>"
					+ "<td style=\"width:60px;\">"
						+ "Begin"
					+ "</td>"
					+ "<td>"
						+ "<input type=\"text\" id=\"datetime1\" name=\"datetime1\" style=\"width:122px; padding:2px 0px 2px 5px;\" />"
					+ "</td>"					
				+ "</tr>"
				+ "<tr>"
					+ "<td>"
						+ "End"
					+ "</td>"
					+ "<td>"
						+ "<input type=\"text\" id=\"datetime2\" name=\"datetime2\" style=\"width:122px; padding:2px 0px 2px 5px;\" />"
					+ "</td>"
				+ "</tr>"
				+ "<tr>"
					+ "<td>"
						+ "Limit to"
					+ "</td>"
					+ "<td>"
						+ "<input type=\"text\" name=\"amount\" style=\"width:35px; padding:2px 0px 2px 0px; text-align:center;\" maxlength=\"4\" value=\"" + entryCount + "\" /> flows"
					+ "</td>"					
				+ "</tr>"
				+ "<tr>"
					+ "<td>"
						+ "Filter"
					+ "</td>"
					+ "<td>"
					+ "</td>"					
				+ "</tr>"
				+ "<tr>"
					+ "<td colspan=\"2\">"
						+ "<textarea name=\"filter\" rows=\"2\" cols=\"26\" style=\"font-size:11px;\">" + nfsenDisplayFilter + "</textarea>"
					+ "</td>"					
				+ "</tr>"
				+ "<tr>"
					+ "<td colspan=\"2\" style=\"text-align:center; padding-top:5px;\">"
						+ "<input type=\"submit\" name=\"submit\" value=\"Submit\" />"
					+ "</td>"					
				+ "</tr>"					
			+ "</table></form>";

		// Select the current option in the 'nfsenstatorder' selector
		var options = document.getElementById("nfsenstatorder").options;
		for(var i = 0; i < options.length; i++) {
			if(options[i].text == nfsenStatOrder) {
				options[i].selected = true;
				break;
			}
		}

		if(nfsenOption == 1) { // Stat TopN
			document.getElementById("nfsenoptionStatTopN").checked = true;
		} else {
			document.getElementById("nfsenoptionListFlows").checked = true;
			document.getElementById('nfsenstatorder').disabled = true;
		}
		
		if(autoRefresh > 0) {
			document.getElementById("auto-refresh").checked = true;
			manageAutoRefresh();
		} else {
			document.getElementById("auto-refresh").checked = false;
		}
		
		// Initialize panel
		if(demoMode == 0) {
			$(".trigger").click(function(){
				$(".panel").toggle("fast");
				$(this).toggleClass("active");
				return false;
			});
		} else {
			$(".trigger").hide();
		}
		
		if(autoOpenMenu == 1 || getErrorCode() == 1) {
			$(".trigger").trigger("click");
		}
		
		if(demoMode == 1) {
			document.getElementById("map_canvas").style.cssText = "width:100%; height:100%;";
			document.getElementById("legend").style.display = "none";
			document.getElementById("footerfunctions").style.display = "none";
		}
		
		// Initialize date/time pickers (http://trentrichardson.com/examples/timepicker/)
		$('#datetime1').datetimepicker({
			hour: hours1,
			minute: minutes1,
			maxDate: new Date(latestDate.substr(0, 4), latestDate.substr(4, 2) - 1, latestDate.substr(6, 2), latestHour, latestMinute),
			stepMinute: 5
		});
		$('#datetime1').datetimepicker('setDate', new Date(date1.substr(0, 4), parseInt(date1.substr(4, 2)) - 1, date1.substr(6, 2), hours1, minutes1));
		$('#datetime2').datetimepicker({
			hour: hours2,
			minute: minutes2,
			maxDate: new Date(latestDate.substr(0, 4), latestDate.substr(4, 2) - 1, latestDate.substr(6, 2), latestHour, latestMinute),
			stepMinute: 5
		});
		$('#datetime2').datetimepicker('setDate', new Date(date2.substr(0, 4), parseInt(date1.substr(4, 2)) - 1, date2.substr(6, 2), hours2, minutes2));
		
		// Initialize buttons (jQuery)
		$('#options').submit(function() {
		    $('input[type=submit]', this).attr('disabled', 'disabled');
		});
		
		// Initialize source selector (jQuery)
		$("#nfsensources").multiselect({
			minWidth:135,
			open: function(event, ui){
				$("div.ui-multiselect-menu").css("left", "");
				$("div.ui-multiselect-menu").css("right", "23px");
				$("div.ui-multiselect-menu").css("width", "175px");
			}
		});
		
		// Generate progress bar (jQuery)
		generateDialog("progressBar", "");
		
	   /**
		* Prepares a jQuery dialog of the specified type.
		*
		* Parameters:
		*		type - indicates which contents should be shown inside the dialog. The possible
		*				options are:
		*					1. 'about' - shows an about window
		*					2. 'help' - shows the SURFmap help
		*					3. 'invalidWindow' - shows an error message in case something is wrong with
		*					   			the window settings
		*		text - text which should be indicated inside the dialog
		*/			
		function generateDialog(type, text) {
			if($("#dialog").dialog("isOpen")) {
				$("#dialog").dialog("destroy");
			}
			
			if(type == "about") {
				document.getElementById("dialog").setAttribute("title", "About");
				document.getElementById("dialog").innerHTML = "SURFmap has been developed by:<br /><br />Rick Hofstede<br />Anna Sperotto, Tiago Fioreze<br /><br /><i>University of Twente, The Netherlands</i><br /><br />SURFmap is available on <a href=\"http://sourceforge.net/p/surfmap\" target=\"_blank\" style=\"text-decoration:underline;\">SourceForge</a> and is distributed under the <a href=\"javascript:generateDialog('license')\" style=\"text-decoration:underline;\">BSD license</a>.<br /><br />Special thanks to Pavel Celeda from INVEA-TECH, for his valuable contributions.<br /><br />";

				if(GEOLOCATION_DB == "IP2Location") document.getElementById("dialog").innerHTML += "<table style='width: 270px; font-size:80%;'><tr><td>You are using the following geolocation service:</td><td><img src='images/ip2location.gif' alt='IP2Location' style='width: 130px;' /></td></tr></table><br />";
				else if(GEOLOCATION_DB == "MaxMind") document.getElementById("dialog").innerHTML += "<table style='width: 270px; font-size:80%;'><tr><td>You are using the following geolocation service:</td><td><img src='images/maxmind.png' alt='MaxMind' style='width: 130px;' /></td></tr></table><br />";
				else if(GEOLOCATION_DB == "geoPlugin") document.getElementById("dialog").innerHTML += "<table style='width: 270px; font-size:80%;'><tr><td>You are using the following geolocation service:</td><td><img src='images/geoplugin.jpg' alt='geoPlugin' style='width: 130px;' /></td></tr></table><br />";
				
				document.getElementById("dialog").innerHTML += "<div style='font-size:80%;'>Application version: " + applicationVersion + "</div>";	
				showDialog("dialog", "auto", 350, "center", false);
			} else if(type == "help") {
				document.getElementById("dialog").setAttribute("title", "Help");
				document.getElementById("dialog").innerHTML = "Welcome to the SURFmap help. Some main principles of SURFmap are explained here.<br /><br /><table border = '0'><tr><td width = '100'><b>Marker</b></td><td>Markers represent hosts and show information about them, like IPv4 addresses and the country, region and city they're in. The information shown here depends on the selected zoom level.<hr /></td></tr><tr><td><b>Line</b></td><td>Lines represent a flow between two hosts (so between markers) and show information about that flow, like the geographical information of the two end points, the exchanged amount of packets, octets and throughput per flow. The information shown here depends on the selected zoom level.<hr /></td></tr><tr><td><b>Zoom levels table</b></td><td>This tables shows the current zoom level. The four zoom levels are also clickable, so that you can zoom in or out to a particular zoom level directly.<hr /></td></tr><tr><td><b>NfSen options</b></td><td>The main NfSen options can be set here. First, either 'List flows' or 'Stat TopN' has to be chosen. The first option lists the first N flows of the selected time slot (N and the selected time slot will be discussed later). 'Stat TopN' shows top N statistics about the network data in the selected time slot. The value of N can be set in the 'Amount' field, while the time slot can be set in the 'Date' field.</td></tr></table>";
				showDialog("dialog", "auto", 500, "center", false);				
			} else if(type == "license") {
				document.getElementById("dialog").setAttribute("title", "SURFmap license");
				document.getElementById("dialog").innerHTML = "The SURFmap project is distributed under the BSD license:<br>"
					+ "<br>"
					+ "Copyright (c) 2011, Rick Hofstede (University of Twente, The Netherlands)<br>"
					+ "All rights reserved.<br>"
					+ "<br>"
					+ "Redistribution and use in source and binary forms, with or without <br>"
					+ "modification, are permitted provided that the following conditions are met:<br>"
					+ "<br>"
					+ "&nbsp; * Redistributions of source code must retain the above copyright notice, <br>"
					+ "&nbsp;&nbsp;&nbsp;&nbsp; this list of conditions and the following disclaimer.<br>"
					+ "&nbsp; * Redistributions in binary form must reproduce the above copyright notice, <br>"
					+ "&nbsp;&nbsp;&nbsp;&nbsp; this list of conditions and the following disclaimer in the documentation <br>"
					+ "&nbsp;&nbsp;&nbsp;&nbsp; and/or other materials provided with the distribution.<br>"
					+ "&nbsp; * Neither the name of Rick Hofstede, nor the name of the University of Twente, <br>"
					+ "&nbsp;&nbsp;&nbsp;&nbsp; nor the names of its contributors may be <br>"
					+ "&nbsp;&nbsp;&nbsp;&nbsp; used to endorse or promote products derived from this software without <br>"
					+ "&nbsp;&nbsp;&nbsp;&nbsp; specific prior written permission.<br>"
					+ "<br>"
					+ "THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS \"AS IS\" <br>"
					+ "AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE <br>"
					+ "IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE <br>"
					+ "ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE <br>"
					+ "LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR <br>"
					+ "CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF <br>"
					+ "SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS <br>"
					+ "INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN <br>"
					+ "CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) <br>"
					+ "ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE <br>"
					+ "POSSIBILITY OF SUCH DAMAGE.<br>";
				showDialog("dialog", "auto", "auto", "center", false);
			} else if(type == "netflowDetails") {
				document.getElementById("dialog").setAttribute("title", "Details");
				document.getElementById("dialog").innerHTML = text;
				
				var tableRows = 0;
				var pos = 0;
				while(pos < text.length && text.indexOf("<tr>", pos) != -1) {
					tableRows++;
					pos = text.indexOf("<tr>", pos) + 4; // "<tr>".length = 4
				}
				tableRows--; // Table header was also counted, and is not a body row
				
				// Using 'height: auto' together width 'maxHeight' does not work properly (jQuery UI bug #4820): http://bugs.jqueryui.com/ticket/4820)
				var headerHeight = 70;
				var rowHeight = 15;
				var dialogHeight = (headerHeight + (tableRows * rowHeight) > 450) ? 450 : headerHeight + (tableRows * rowHeight);

				if(nfsenOption == 1) {
					showDialog("dialog", dialogHeight, "auto", "center", false);
				} else {
					showDialog("dialog", dialogHeight, "auto", "center", false);
				}
			} else if(type == "progressBar") {
				document.getElementById("dialog").setAttribute("title", "Loading...");
				document.getElementById("dialog").innerHTML = "<div style='margin-top: 6px; width:400px;' id='progressBar'></div>";
				showDialog("dialog", 80, 450, "center", true);
				showProgressBar("progressBar", 0, "");
			}
		}
	
	   /**
		* Prepares a jQuery alert.
		*
		* Parameters:
		*		type - indicates which contents should be shown inside the dialog. The possible
		*				options are:
		*					1. 'filterError'
		*/	
		function generateAlert(type) {
			if($("#dialog").dialog("isOpen")) {
				$("#dialog").dialog("destroy");
			}
			
			if(type == "filterError") {
				jAlert("The filter you provided does not adhere to the expected syntax.<br /><br /><b>Filter</b>: " + nfsenFilter + "<br /><b>Error message</b>: " +  getErrorMessage() + "</br /><br />Please check <a href='http://nfdump.sourceforge.net/' style='text-decoration:underline;' target='_blank'>http://nfdump.sourceforge.net/</a> for the filter syntax.", "Filter error");
			} else if(type == "fileError") {
				jAlert("There is a problem with your profile data. Please check whether your profile is empty and/or change to another NetFlow source. This can be done in the 'Menu' panel by selecting other 'Sources'.", "File error");
			} else if(type == "profileError") {
				jAlert("You have an error in your configuration. <br /><br /><b>Error message</b>: " +  getErrorMessage(), "Error");
			} else if(type == "invalidWindow") {
				if(getErrorCode() == 2) {
					// The first (normal) selected date/time is invalid.
					jAlert("The selected date/time window (<?php echo $sessionData->originalDate1Window.' '.$sessionData->originalTime1Window; ?>) does not exist.<br /><br />The last available/valid time window will be selected.", "Error");
				} else if(getErrorCode() == 3) {
					// The second (time range) selected date/time is invalid.
					jAlert("The (second) selected date/time window (<?php echo $sessionData->originalDate2Window.' '.$sessionData->originalTime2Window; ?>) does not exist.<br /><br />The last available/valid time window will be selected.", "Error");
				} else {
					// The selected date/time range is invalid (i.e., the second selected date/time is earlier than the first selected date/time).
					jAlert("An unknown error occured.", "Error");
				}
			}
		}
	
	   /**
		* Shows the NfSen flow output / overview (depends on whether or not it is already present) in a dialog.
		* When the table has been generated, generateDialog() is called to put it into a jQuery dialog.
		* Parameters:
		*		flowIDs - IDs of the flow records of which the table should be composed. Provide an empty String
		*					to show all flow records
		*/
		function showNetFlowDetails(flowIDs) {
			if(flowIDs != "") { // if flowIDs == "", all flows should be presented
				var idArray = flowIDs.split("_");
				var idCount = idArray.length;
			} else {
				var idCount = flowRecordCount;
			}
			
			var netflowDataDetailsTable = "<table border='0' style='text-align: center;'><thead class='netflowDataDetailsTitle'><tr><th>Duration</th><th>Source IP</th><th>Src. Port</th><th>Destination IP</th><th>Dst. Port</th><th>Protocol</th><th>Packets</th><th>Octets</th>";
			
			// If NfSen is used as the information source and its 'Stat TopN' option is used
			if(nfsenOption == 1) {
				netflowDataDetailsTable += "<th>Flows</th>";
				var columns = 9;
			} else {
				var columns = 8;
			}
			
			netflowDataDetailsTable += "</tr></thead><tbody class='netflowDataDetails'>";

			if(idCount == 0) {
				netflowDataDetailsTable += "<tr><td colspan='" + columns + "' style='text-align: center;'>No flow records are available...</td></tr>";				
			} else {
				for(var i = 0; i < idCount; i++) {
					netflowDataDetailsTable += "<tr>";

					var currentID = -1;
					if(flowIDs != "") currentID = idArray[i];
					else currentID = i;

					netflowDataDetailsTable += "<td title='Duration'>" + flowRecords[currentID].duration + "</td><td title='Source IP address'>" + flowRecords[currentID].srcIP + "</td><td title='Source port'>" + flowRecords[currentID].srcPort + "</td><td title='Destination IP address'>" + flowRecords[currentID].dstIP + "</td><td title='Destination port'>" + flowRecords[currentID].dstPort + "</td><td title='Protocol'>" + flowRecords[currentID].protocol + "</td><td title='Packets'>" + applySIScale(flowRecords[currentID].packets) + "</td><td title='Octets'>" + applySIScale(flowRecords[currentID].octets) + "</td>";
					if(nfsenOption == 1) {
						netflowDataDetailsTable += "<td title='Flows'>" + flowRecords[currentID].flows + "</td>";
					}

					netflowDataDetailsTable += "</tr>";
				}
			}

			netflowDataDetailsTable += "</tbody></table>";
			generateDialog("netflowDetails", netflowDataDetailsTable);
		}

	   /**
		* Sets the center of the Google Maps map to the specified endpoint (either source's endpoint, or destination's 
		* endpoint, depending on parameter).
		* Parameters:
		*		zoomLevel - a SURFmap zoom level
		*		lineID - unique ID of the line to which's end points should be navigated
		* 		endPoint - either "source" or "destination"
		*/
		function goToLineEndPoint(zoomLevel, lineID, endPoint) {
			if(endPoint == "source") {
				var source = new google.maps.LatLng(lineProperties[zoomLevel][lineID].lat1, lineProperties[zoomLevel][lineID].lng1);
				map.setCenter(source);
				infoWindow.setPosition(source);
			} else {
				var destination = new google.maps.LatLng(lineProperties[zoomLevel][lineID].lat2, lineProperties[zoomLevel][lineID].lng2);
				map.setCenter(destination);
				infoWindow.setPosition(destination);
			}
		}
		
	   /**
		* Writes the legend beneath the Google Maps map, depending on the line color classification.
		* Parameters:
		*		zoom_level - a SURFmap zoom level
		*/
		function initializeLegend(zoomLevel) {
			determineLineColorRanges(zoomLevel, nfsenStatOrder);
			document.getElementById("legend_based_on").innerHTML = "Number of observed " + nfsenStatOrder;
			
			if(nfsenStatOrder == "bytes") {
				document.getElementById("legend_green").innerHTML = "[ " + applySIScale(lineColorClassification[0]) + ", " + applySIScale(lineColorClassification[1]) + " >";
				document.getElementById("legend_yellow").innerHTML = "[ " + applySIScale(lineColorClassification[1]) + ", " + applySIScale(lineColorClassification[2]) + " >";
				document.getElementById("legend_orange").innerHTML = "[ " + applySIScale(lineColorClassification[2]) + ", " + applySIScale(lineColorClassification[3]) + " >";
				document.getElementById("legend_red").innerHTML = "[ " + applySIScale(lineColorClassification[3]) + ", " + applySIScale(lineColorClassification[4]) + " ]";
			} else {
				document.getElementById("legend_green").innerHTML = "[ " + lineColorClassification[0] + ", " + lineColorClassification[1] + " >";
				document.getElementById("legend_yellow").innerHTML = "[ " + lineColorClassification[1] + ", " + lineColorClassification[2] + " >";
				document.getElementById("legend_orange").innerHTML = "[ " + lineColorClassification[2] + ", " + lineColorClassification[3] + " >";
				document.getElementById("legend_red").innerHTML = "[ " + lineColorClassification[3] + ", " + lineColorClassification[4] + " ]";
			}
		}
		
	   /*
		* This function changes the indicated current zoom level in the table to the
		* right of the SURFmap v3 map.
		* Parameters:
		*		old_zoom_level - the previous zoom level in terms of the four SURFmap zoom levels
		*		new_zoom_level - the next zoom level in terms of the four SURFmap zoom levels
		*/
		function changeZoomLevelPanel(old_zoom_level, new_zoom_level) {
			var zoomLevels = document.getElementById("zoomLevels");
			var rows = zoomLevels.getElementsByTagName("input");
			rows[old_zoom_level].checked = false;
			rows[new_zoom_level].checked = true;
		}
				
	   /**
		* Manages the visibility of the specified element.
		*
		* Parameters:
		*		id - ID of the corresponding accordion element
		*		option - text which should be indicated inside the dialog. The possible
		*				options are:
		*					1. 0 - hides the accordion element
		*					2. 1 - shows the accordion element
		*					3. 2 - shows the accordion element and opens it	
		*/			
		function manageAccordionElementVisibility(id, option) {
			if(option == 0) {
				hideAccordionElement(id);
			} else if(option == 2) {
				openAccordionElement(id);
			}
		}
		
	   /**
		* Manages the execution or stop of auto-refresh, based on the checkbox in the
		* user interface.
		*/		
		function manageAutoRefresh() {
			if(document.getElementById("auto-refresh").checked) {
				SESSION_queue.push(new SessionData('refresh', 300));
				autoRefreshID = setTimeout("window.location.replace(\"index.php?autorefresh=1\")", 300000);
			} else {
				SESSION_queue.push(new SessionData('refresh', 0));
				clearTimeout(autoRefreshID);
			}
		}
		
	</script>
</body>
</html>