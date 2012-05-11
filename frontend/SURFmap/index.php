<?php
	/******************************
	 # index.php [SURFmap]
	 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
	 # University of Twente, The Netherlands
	 #
	 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
	 *******************************/

	require_once("config.php");
	require_once("objects.php");
	require_once("connectionhandler.php");
	require_once("loghandler.php");
	require_once("sessionhandler.php");
	require_once("geofilter.php");
	require_once("surfmaputil.php");
	
	$nfsenConfig = readNfSenConfig();
	
	require_once($nfsenConfig['HTMLDIR']."/conf.php");
	require_once($nfsenConfig['HTMLDIR']."/nfsenutil.php");

	$version = "v2.3 dev (20120511)";

	// Initialize session
	if (!isset($_SESSION['SURFmap'])) $_SESSION['SURFmap'] = array();
	
	$logHandler = new LogHandler();
	$sessionData = new SessionData();
	$sessionHandler = new SessionHandler($logHandler);
	$connectionHandler = new ConnectionHandler($logHandler, $sessionHandler);
	
	$sessionData->NetFlowData = $connectionHandler->retrieveDataNfSen();
	$sessionData->geoLocationData = $connectionHandler->retrieveDataGeolocation($sessionData->NetFlowData);
	$sessionData->geoCoderData = $connectionHandler->retrieveDataGeocoderDB($sessionData->geoLocationData);
	
	// Apply geo filter
	for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
		try {
			if (!evaluateGeoFilter($sessionData->geoLocationData[$i], $_SESSION['SURFmap']['geoFilter'])) {
				$sessionData->flowRecordCount--;

				array_splice($sessionData->NetFlowData, $i, 1);
				array_splice($sessionData->geoLocationData, $i, 1);
				array_splice($sessionData->geoCoderData, $i, 1);

				// Compensate for array element removal; result will be a repetition of current index.
				$i--;
			}
		} catch (GeoFilterException $ex) {
			$sessionData->errorCode = 7;
			$sessionData->errorMessage = $ex->errorMessage();
		}
	}

?>

<!DOCTYPE html>
<html>
<head>
   	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>SURFmap -- A Network Monitoring Tool Based on the Google Maps API</title>
	<link type="text/css" rel="stylesheet" href="jquery/css/start/jquery-ui-1.8.18.custom.css" />
	<link type="text/css" rel="stylesheet" href="css/jquery.alerts.css" /> <!-- http://abeautifulsite.net/blog/2008/12/jquery-alert-dialogs/ -->
	<link type="text/css" rel="stylesheet" href="css/surfmap.css" />
	<script type="text/javascript" src="<?php if ($FORCE_HTTPS) {echo 'https';} else {echo 'http';} ?>://maps.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript" src="jquery/js/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="jquery/js/jquery-ui-1.8.18.custom.min.js"></script>
	<script type="text/javascript" src="js/jquery.alerts.js"></script>
	<script type="text/javascript" src="js/jquery.multiselect.js"></script> <!-- http://www.erichynds.com/examples/jquery-ui-multiselect-widget/demos/ -->
	<script type="text/javascript" src="js/jqueryutil.js"></script>
	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script> <!-- http://trentrichardson.com/examples/timepicker/ -->
	<script type="text/javascript" src="js/maputil.js"></script>
	<script type="text/javascript" src="js/markermanager.js"></script>
	<script type="text/javascript" src="js/objects.js"></script>
	<script type="text/javascript" src="js/queuemanager.js"></script>	
	<script type="text/javascript" src="js/util.js"></script>
	<script type="text/javascript">
		var COUNTRY = 0; var REGION = 1; var CITY = 2; var HOST = 3;
		var map, markerManager, geocoder, infoWindow, currentZoomLevel, currentSURFmapZoomLevel, initialZoomLevel, queueManager;
		var initialZoomLevel = <?php echo $_SESSION['SURFmap']['zoomLevel']; ?>;
		var initialSURFmapZoomLevel = <?php echo $DEFAULT_ZOOM_LEVEL; ?>;
		var mapCenter = "<?php if ($_SESSION['SURFmap']['mapCenter'] != "-1") {echo $_SESSION['SURFmap']['mapCenter'];} else {echo $MAP_CENTER;} ?>";
			mapCenter = new google.maps.LatLng(parseFloat(mapCenter.substring(0, mapCenter.indexOf(","))), parseFloat(mapCenter.substring(mapCenter.indexOf(",") + 1)));
		var mapCenterWithoutGray; // Map center, for which the map doesn't show gray areas

		var flowRecords = [];
		var lines = new Array(4); // 4 zoom levels
		var lineProperties = new Array(4); // 4 zoom levels
		var lineOverlays = new Array(); // Contains the actual map overlays (lines, not markers)
		var markers = new Array(4); // 4 zoom levels
		var markerProperties = new Array(4); // 4 zoom levels	

		var green = "#00cc00"; var yellow = "#ffff00"; var orange = "#ff6600"; var red = "#ff0000"; var black = "#000000";
		var lineColors = 4;
		var lineColorClassification = [];
		
		/* NfSen settings */
		var nfsenQuery = "<?php echo $sessionData->query; ?>";
		var nfsenProfile = "<?php echo $_SESSION['SURFmap']['nfsenProfile'] ?>"
		var nfsenAllSources = "<?php echo $_SESSION['SURFmap']['nfsenAllSources']; ?>".split(":");
		var nfsenSelectedSources = "<?php echo $_SESSION['SURFmap']['nfsenSelectedSources']; ?>".split(":");
		var flowFilter = "<?php echo $_SESSION['SURFmap']['flowFilter']; ?>";
		var flowDisplayFilter = "<?php echo $sessionData->flowDisplayFilter; ?>";
		var geoFilter = "<?php echo $_SESSION['SURFmap']['geoFilter']; ?>";
		
		var date1 = "<?php echo $_SESSION['SURFmap']['date1']; ?>";
		var date2 = "<?php echo $_SESSION['SURFmap']['date2']; ?>";
		var hours1 = "<?php echo $_SESSION['SURFmap']['hours1']; ?>";
		var hours2 = "<?php echo $_SESSION['SURFmap']['hours2']; ?>";
		var minutes1 = "<?php echo $_SESSION['SURFmap']['minutes1']; ?>";
		var minutes2 = "<?php echo $_SESSION['SURFmap']['minutes2']; ?>";
		var latestDate = "<?php echo $sessionData->latestDate; ?>";
		var latestHour = "<?php echo $sessionData->latestHour; ?>";
		var latestMinute = "<?php echo $sessionData->latestMinute; ?>";
		var errorCode = <?php echo intval($sessionData->errorCode); ?>;
		var errorMessage = "<?php echo $sessionData->errorMessage; ?>";
		var originalDate1Window = "<?php echo $sessionData->originalDate1Window; ?>";
		var originalTime1Window = "<?php echo $sessionData->originalTime1Window; ?>";
		var originalDate2Window = "<?php echo $sessionData->originalDate2Window; ?>";
		var originalTime2Window = "<?php echo $sessionData->originalTime2Window; ?>";
		
		var markerManagerInitialized = false;
		var markersProcessed = false;

		var entryCount = <?php echo $_SESSION['SURFmap']['entryCount']; ?>;
		var flowRecordCount = <?php echo $sessionData->flowRecordCount; ?>;
		var applicationVersion = "<?php echo $version; ?>"; // SURFmap version number
		var demoMode = <?php echo $DEMO_MODE; ?>; // 0: Disabled; 1: Enabled
		var demoModePageTitle = "<?php echo $DEMO_MODE_PAGE_TITLE; ?>";
		var autoOpenMenu = <?php echo $AUTO_OPEN_MENU; ?>; // 0: Disabled; 1: Enabled
		var debugLogging = <?php echo $LOG_DEBUG; ?>;
		var showWarningOnNoData = <?php echo $SHOW_WARNING_ON_NO_DATA; ?>;
		var showWarningOnHeavyQuery = <?php echo $SHOW_WARNING_ON_HEAVY_QUERY; ?>;
		var geocoderRequestsSuccess = <?php echo $sessionData->geocoderRequestsSuccess; ?>; // Geocoder request history for current day
		var geocoderRequestsError = <?php echo $sessionData->geocoderRequestsError; ?>; // Geocoder request history for current day
		var geocoderRequestsSkip = <?php echo $sessionData->geocoderRequestsSkip; ?>; // Geocoder request history for current day
		var geocoderRequestsBlock = <?php echo $sessionData->geocoderRequestsBlock; ?>; // Geocoder request history for current day
		
		var autoRefresh = <?php echo $_SESSION['SURFmap']['refresh']; ?>;
		var autoRefreshID = -1;
	
		var greenIcon = new google.maps.MarkerImage("images/green_marker.png", new google.maps.Size(20, 34));
		var protocols = ["Reserved", "ICMP", "IGMP", "GGP", "Encapsulated IP", "ST", "TCP", "UCL", "EGP", "IGP", "BBN-RCC-MON", "NVP-II", "PUP", "ARGUS", "EMCON", "XNET", "CHAOS", "UDP", "MUX", "DCN-MEAS", "HMP", "PRM", "XNS-IDP", "trUNK-1", "trUNK-2", "LEAF-1", "LEAF-2", "RDP", "IRTP", "ISO-TP4", "NETBLT", "MFE-NSP", "MERIT-INP", "SEP", "3PC", "IDPR", "XTP", "DDP", "IDPR-CMTP", "TP++", "IL", "SIP", "SDRP", "SIP-SR", "SIP-FRAG", "IDRP", "RSVP", "GRE", "MHRP", "BNA", "SIPP-ESP", "SIPP-AH", "I-NLSP", "SWIPE", "NHRP", "Unassigned", "Unassigned", "Unassigned", "Unassigned", "Unassigned", "Unassigned", "Any host internal protocol", "CFTP", "Any local network", "SAT-EXPAK", "KRYPTOLAN", "RVD", "IPPC", "Any distributed file system", "SAT-MON", "VISA", "IPCV", "CPNX", "CPHB", "WSN", "PVP", "BR-SAT-MON", "SUN-ND", "WB-MON", "WB-EXPAK", "ISO-IP", "VMTP", "SECURE-VMTP", "VIVES", "TTP", "NSFNET-IGP", "DGP", "TCF", "IGRP", "OSPFIGP", "Sprite-RPC", "LARP", "MTP", "AX.25", "IFIP", "MICP", "SCC-SP", "ETHERIP", "ENCAP", "Any private encryption scheme", "GMTP"];

		var resolvedDNSNames = [];
		var DNSNameResolveQueue = [];
		
		// NfSen parameters
		var nfsenOption = <?php echo $_SESSION['SURFmap']['nfsenOption']; ?>; // 0: List flows; 1: Top StatN
		var nfsenStatOrder = "<?php echo $_SESSION['SURFmap']['nfsenStatOrder']; ?>"; // flows, packets or octets
		
		// --- Geocoding parameters
		var geocodingDelay = 100;
		var geocodingQueue = [];
		var geocodedPlaces = [];
		var successfulGeocodingRequests = 0; // successful geocoding requests
		var erroneousGeocodingRequests = 0; // erroneous geocoding requests
		var skippedGeocodingRequests = 0; // skipped geocoding requests
		var blockedGeocodingRequests = 0; // blocked geocoding requests
		var outputGeocodingErrorMessage = 0; // indicates if an geocoding error message has been shown to the user (this should happen only once)
		
		var GEOLOCATION_DB = "<?php echo $GEOLOCATION_DB; ?>";
		var IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION = "<?php echo $IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION; ?>";
		var INTERNAL_DOMAIN_COUNTRY = "<?php echo $INTERNAL_DOMAINS_COUNTRY ?>";
		var USE_GEOCODER_DB = <?php if (is_numeric($USE_GEOCODER_DB)) { echo $USE_GEOCODER_DB; } else {echo "0";} ?>;
		var WRITE_DATA_TO_GEOCODER_DB = <?php if (is_numeric($WRITE_DATA_TO_GEOCODER_DB)) { echo $WRITE_DATA_TO_GEOCODER_DB; } else {echo "0";} ?>;
		// --- End of Geocoding parameters
		
	    /*
		 * Processes all server transactions. These transactions are using 'servertransaction.php'.
		 */
		function serverTransactions () {
			while (queueManager.getTotalQueueSize() > 0) {				
				// Gets queue item with highest priority
				var queueItem = queueManager.getElementPrio();
								
				if (queueItem.type == queueManager.queueTypes.INFO
						|| queueItem.type == queueManager.queueTypes.ERROR
						|| queueItem.type == queueManager.queueTypes.DEBUG) {
					data = "transactionType=LOG"
							+ "&logType=" + queueItem.type 
							+ "&message=" + escape(queueItem.element);
				} else if (queueItem.type == queueManager.queueTypes.GEOCODING) {
					data = "transactionType=GEOCODING"
							+ "&location=" + escape(queueItem.element.place)
							+ "&lat=" + queueItem.element.lat
							+ "&lng=" + queueItem.element.lng;
				} else if (queueItem.type == queueManager.queueTypes.DNS) {
					data = "transactionType=" + queueItem.type
							+ "&value=" + queueItem.element;				
				} else {
					data = "transactionType=" + queueItem.type
							+ "&type=" + queueItem.element.type 
							+ "&value=" + queueItem.element.value;
				}
				
				data += "&token=" + Math.random();
				
				$.ajax({
					type: "GET",
					url: "servertransaction.php",
					data: data,
					error: function(msg) {
						// alert("The Web server is not reachable for AJAX calls. Please check your configuration.");
					},
					success: function(msg) {
						var splittedResult = msg.split("##");
						if (splittedResult[0] == queueManager.queueTypes.GEOCODING && splittedResult[1] == "OK") {
							queueManager.addElement(queueManager.queueTypes.INFO, splittedResult[2] + " was stored in GeoCoder DB");
						} else if (splittedResult[0] == queueManager.queueTypes.DNS && splittedResult[1] == "OK") {
							resolvedDNSNames[splittedResult[2]] = splittedResult[3];
						}
					}
				});
			}
		}

	    /*
	     * Reads the PHP ERROR log queue.
		 * Parameters:
		 *	type - can be either INFO or ERROR
		 */			
		function importPHPLogQueue (type) {
			var logString;
			
			if (type == "INFO") {
				logString = "<?php echo $logHandler->getInfo(); ?>";
			} else if (type == "ERROR") {
				logString = "<?php echo $logHandler->getError(); ?>";
			}
			
			if (logString.length > 0) {
				var logArray = logString.split("##");
				for (var i = 0; i < logArray.length; i++) {
					if (type == "INFO") {
						queueManager.addElement(queueManager.queueTypes.INFO, logArray[i]);
					} else if (type == "ERROR") {
						queueManager.addElement(queueManager.queueTypes.ERROR, logArray[i]);
					}
				}
			}
		}
			
	    /*
		 * This function puts the network traffic and gegraphical information in a Javascript
		 * associative array.
		 */			
		function importData () {
			// NetFlow data
			var IPs = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, 'IP'); ?>", "IP", flowRecordCount);
			var ports = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, 'PORT'); ?>", "PORT", flowRecordCount);
			var protocols = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, 'PROTOCOL'); ?>", "PROTOCOL", flowRecordCount);
			var packets = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, 'PACKETS'); ?>", "PACKETS", flowRecordCount);
			var octets = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, 'OCTETS'); ?>", "OCTETS", flowRecordCount);
			var durations = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, 'DURATION'); ?>", "DURATION", flowRecordCount);
			var flows = stringToArray("<?php echo stringifyNetFlowData($sessionData->NetFlowData, 'FLOWS'); ?>", "FLOWS", flowRecordCount);

			// GeoLocation data
			var countries = stringToArray("<?php echo stringifyGeoData($sessionData->geoLocationData, 'COUNTRY'); ?>", "COUNTRY", flowRecordCount);
			var regions = stringToArray("<?php echo stringifyGeoData($sessionData->geoLocationData, 'REGION'); ?>", "REGION", flowRecordCount);
			var cities = stringToArray("<?php echo stringifyGeoData($sessionData->geoLocationData, 'CITY'); ?>", "CITY", flowRecordCount);
			
			// GeoCoder data
			var countryLatLngs = stringToArray("<?php echo stringifyGeoCoderData('COUNTRY'); ?>", "GeoCoder_COUNTRY", flowRecordCount);
			var regionLatLngs = stringToArray("<?php echo stringifyGeoCoderData('REGION'); ?>", "GeoCoder_REGION", flowRecordCount);
			var cityLatLngs = stringToArray("<?php echo stringifyGeoCoderData('CITY'); ?>", "GeoCoder_CITY", flowRecordCount);

			for (var i = 0; i < flowRecordCount; i++) {
				flowRecords[i] = new FlowRecord(IPs[i][0], ports[i][0], IPs[i][1], ports[i][1], protocols[i]);
				flowRecords[i].packets = packets[i];
				flowRecords[i].octets = octets[i];
				flowRecords[i].duration = durations[i];
				if (nfsenOption == 1) flowRecords[i].flows = flows[i];
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
		
	    /*
		 * Checks whether places need to be geocoded and whether geolocation information of places at a certain
		 * zoom level X can be complemented by geolocation information for the same place at zoom level X+1.
		 */			
		function complementFlowRecords () {
			for (var i = 0; i < flowRecordCount; i++) {
				var entry = flowRecords[i].srcCountry;
				if (flowRecords[i].srcCountryLat == -1 && entry.indexOf("NKNOWN") == -1	&& jQuery.inArray(entry, geocodingQueue) == -1) {
					geocodingQueue.push(entry);
				}
				
				entry = flowRecords[i].dstCountry;
				if (flowRecords[i].dstCountryLat == -1 && entry.indexOf("NKNOWN") == -1 && jQuery.inArray(entry, geocodingQueue) == -1) {
					geocodingQueue.push(entry);
				}
				
				entry = flowRecords[i].srcCountry + ", " + flowRecords[i].srcRegion;
				if (flowRecords[i].srcRegionLat == -1 && entry.indexOf("NKNOWN") == -1 && jQuery.inArray(entry, geocodingQueue) == -1) {
					geocodingQueue.push(entry);
				}
				
				entry = flowRecords[i].dstCountry + ", " + flowRecords[i].dstRegion;
				if (flowRecords[i].dstRegionLat == -1 && entry.indexOf("NKNOWN") == -1 && jQuery.inArray(entry, geocodingQueue) == -1) {
					geocodingQueue.push(entry);
				}
				
				if (flowRecords[i].srcRegion.indexOf("NKNOWN") == -1) {
					entry = flowRecords[i].srcCountry + ", " + flowRecords[i].srcRegion + ", " + flowRecords[i].srcCity;
				} else {
					entry = flowRecords[i].srcCountry + ", " + flowRecords[i].srcCity;
				}
				if (flowRecords[i].srcCityLat == -1 && entry.indexOf("NKNOWN") == -1 && jQuery.inArray(entry, geocodingQueue) == -1) {
					geocodingQueue.push(entry);
				}
				
				if (flowRecords[i].dstRegion.indexOf("NKNOWN") == -1) {
					entry = flowRecords[i].dstCountry + ", " + flowRecords[i].dstRegion + ", " + flowRecords[i].dstCity;
				} else {
					entry = flowRecords[i].dstCountry + ", " + flowRecords[i].dstCity;
				}				
				entry = flowRecords[i].dstCountry + ", " + flowRecords[i].dstRegion + ", " + flowRecords[i].dstCity;
				if (flowRecords[i].dstCityLat == -1 && entry.indexOf("NKNOWN") == -1 && jQuery.inArray(entry, geocodingQueue) == -1) {
					geocodingQueue.push(entry);
				}
			}

			var totalGeocodingRequests = geocodingQueue.length;
			
			// Start geocoding
			while (geocodingQueue.length > 0) {
				geocode(geocodingQueue.pop());
			}

			var intervalHandlerID = setInterval(function() {
				var completedGeocodingRequests = successfulGeocodingRequests + erroneousGeocodingRequests + skippedGeocodingRequests;
				setProcessingText("Geocoding (" + completedGeocodingRequests + " of " + totalGeocodingRequests + ")...");
				
				if (geocodingQueue.length == 0 && totalGeocodingRequests == completedGeocodingRequests) {
					clearInterval(intervalHandlerID); 
					
					for (var i = 0; i < flowRecordCount; i++) {
						if (flowRecords[i].srcCountry.indexOf("NKNOWN") > -1) {
							flowRecords[i].srcCountryLat = 0;
							flowRecords[i].srcCountryLng = 0;
						}
						if (flowRecords[i].dstCountry.indexOf("NKNOWN") > -1) {
							flowRecords[i].dstCountryLat = 0;
							flowRecords[i].dstCountryLng = 0;
						}
					}

					// Apply geocoded places to flow records
					for (var i = 0; i < geocodedPlaces.length; i++) {
						for (var j = 0; j < flowRecordCount; j++) {
							if (flowRecords[j].srcCountry == geocodedPlaces[i].place && flowRecords[j].srcCountryLat == -1) {
								flowRecords[j].srcCountryLat = geocodedPlaces[i].lat;
								flowRecords[j].srcCountryLng = geocodedPlaces[i].lng;
							}
							if (flowRecords[j].dstCountry == geocodedPlaces[i].place && flowRecords[j].dstCountryLat == -1) {
								flowRecords[j].dstCountryLat = geocodedPlaces[i].lat;
								flowRecords[j].dstCountryLng = geocodedPlaces[i].lng;
							}
							if (flowRecords[j].srcCountry + ", " + flowRecords[j].srcRegion == geocodedPlaces[i].place && flowRecords[j].srcRegionLat == -1) {
								flowRecords[j].srcRegionLat = geocodedPlaces[i].lat;
								flowRecords[j].srcRegionLng = geocodedPlaces[i].lng;
							}
							if (flowRecords[j].dstCountry + ", " + flowRecords[j].dstRegion == geocodedPlaces[i].place && flowRecords[j].dstRegionLat == -1) {
								flowRecords[j].dstRegionLat = geocodedPlaces[i].lat;
								flowRecords[j].dstRegionLng = geocodedPlaces[i].lng;
							}
							if (flowRecords[j].srcCountry + ", " + flowRecords[j].srcRegion + ", " + flowRecords[j].srcCity == geocodedPlaces[i].place && flowRecords[j].srcCityLat == -1) {
								flowRecords[j].srcCityLat = geocodedPlaces[i].lat;
								flowRecords[j].srcCityLng = geocodedPlaces[i].lng;
							}
							if (flowRecords[j].dstCountry + ", " + flowRecords[j].dstRegion + ", " + flowRecords[j].dstCity == geocodedPlaces[i].place && flowRecords[j].dstCityLat == -1) {
								flowRecords[j].dstCityLat = geocodedPlaces[i].lat;
								flowRecords[j].dstCityLng = geocodedPlaces[i].lng;
							}
						}
					}

					for (var i = 0; i < flowRecordCount; i++) {
						/*
						 * If no latitude/longitude coordinates are present at certain level, take 
						 * the ones from the upper level. If the coordinates for the country level
						 * are (0,0), the whole flow record will be skipped in further processing.
						 */
						if (flowRecords[i].srcRegionLat == 0 && flowRecords[i].srcRegionLng == 0) {
							flowRecords[i].srcRegionLat = flowRecords[i].srcCountryLat;
							flowRecords[i].srcRegionLng = flowRecords[i].srcCountryLng;
						}
						if (flowRecords[i].dstRegionLat == 0 && flowRecords[i].dstRegionLng == 0) {
							flowRecords[i].dstRegionLat = flowRecords[i].dstCountryLat;
							flowRecords[i].dstRegionLng = flowRecords[i].dstCountryLng;
						}
						if (flowRecords[i].srcCityLat == 0 && flowRecords[i].srcCityLng == 0) {
							flowRecords[i].srcCityLat = flowRecords[i].srcRegionLat;
							flowRecords[i].srcCityLng = flowRecords[i].srcRegionLng;
						}
						if (flowRecords[i].dstCityLat == 0 && flowRecords[i].dstCityLng == 0) {
							flowRecords[i].dstCityLat = flowRecords[i].dstRegionLat;
							flowRecords[i].dstCityLng = flowRecords[i].dstRegionLng;
						}
					}
					
					processing();
				}
			}, 100);	
		}		
		
	    /*
		 * This function determines the corresponding protocol of the specified protocol number.
		 * Parameters:
		 *	number - the protocol number of which the corresponding name has to be resolved
		 */			
		function determineProtocolName (number) {
			var protocol;
			
			if (number == 1) {
				protocol = "ICMP";
			} else if (number == 6) {
				protocol = "TCP";
			} else if (number == 17) {
				protocol = "UDP";
			} else if (number == 47) {
				protocol = "GRE";
			} else if (number == 50) {
				protocol = "ESP";
			} else if (number == 51) {
				protocol = "AH";
			} else {
				protocol = protocols[number];
			}
			
			return protocol;
		}

	    /*
		 * Removes the country names from geocoded places. This meta data has only been added to region and
		 * city names.
		 * Parameters:
		 *	place - geocoder string from which the meta data needs to be stripped
		 */
		function stripGeocoderMetaData (place) {
			var strippedPlace;
			if (place.lastIndexOf(", ") != -1) {
				strippedPlace = place.substr(place.lastIndexOf(", ") + 2);
			} else {
				strippedPlace = place;
			}
			return strippedPlace;
		}
		
	    /*
		 * This function starts calls to the Google Maps API GeoCoder.
		 * Parameters:
		 *	place - name of the place that has to be geocoded
		 */
		function geocode (place) {
			if (place == "INVALID IPV4 ADDRESS" && !outputGeocodingErrorMessage) {
				outputGeocodingErrorMessage = 1;
				alert("You are trying to visualize an invalid IP address (i.e., multicast addresses or IPv6 addresses). Please try to use another information source or subset.");
			}

			// Some geolocation databases return 'Unknown' or 'unknown' in case a location is not found or recognized.
			if (geocoderRequestsSuccess + geocoderRequestsError + geocoderRequestsSkip + successfulGeocodingRequests + erroneousGeocodingRequests + skippedGeocodingRequests <= 2250) {
				geocoder.geocode({'address': place}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						queueManager.addElement(queueManager.queueTypes.INFO, place + " was geocoded successfully");
						
						// Store geocoded location in cache DB
						var geocodedPlace = new GeocodedPlace(place, results[0].geometry.location.lat(), results[0].geometry.location.lng());
						geocodedPlaces.push(geocodedPlace);
						
						if (USE_GEOCODER_DB == 1 && WRITE_DATA_TO_GEOCODER_DB == 1) {
							queueManager.addElement(queueManager.queueTypes.GEOCODING, geocodedPlace);
						}
						
						geocodingDelay = 500;
						successfulGeocodingRequests++;
					} else if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
						blockedGeocodingRequests++;
						geocodingDelay += 500;
						setTimeout(function() {
							geocode(place);
						}, geocodingDelay);
					} else {
						queueManager.addElement(queueManager.queueTypes.ERROR, "Geocoder could not find " + place + ". Reason: " + status);
						geocodedPlaces.push(new GeocodedPlace(place, 0, 0));
						erroneousGeocodingRequests++;
					}
				});
			} else {
				geocodedPlaces.push(new GeocodedPlace(place, 0, 0));
				skippedGeocodingRequests++;
			}
		}
		
	    /*
		 * Checks whether a particular marker record already exists for the marker with
		 * the specified ID.
		 * Parameters:
		 *	level - a SURFmap zoom level
		 *	markerID - ID of the marker that needs to be checked
		 *	name - name to be present in the record
		 */		
		function markerRecordExists (level, markerID, name) {
			var markerRecordIndex = -1;
			for (var i = 0; i < markerProperties[level][markerID].markerRecords.length; i++) {
				if (markerProperties[level][markerID].markerRecords[i].name == name) {
					markerRecordIndex = i;
					break;
				}
			}
			return markerRecordIndex;
		}		
			
	    /*
		 * Checks whether a particular line record already exists for the line with
		 * the specified ID.
		 * Parameters:
		 *	level - a SURFmap zoom level
		 *	lineID - ID of the line that needs to be checked
		 *	srcName - name of the source to be present in the record
		 *	dstName - name of the destination to be present in the record
		 */		
		function lineRecordExists (level, lineID, srcName, dstName) {
			var lineRecordIndex = -1;
			for (var i = 0; i < lineProperties[level][lineID].lineRecords.length; i++) {
				if (lineProperties[level][lineID].lineRecords[i].srcName == srcName && lineProperties[level][lineID].lineRecords[i].dstName == dstName) {
					lineRecordIndex = i;
					break;
				}
			}
			return lineRecordIndex;
		}
		
	    /*
		 * This function initializes all markers for all zoom levels.
		 */			
		function initMarkers () {
			var MAX_INFO_WINDOW_LINES = 13, existValue;
			
			for (var i = 0; i < 4; i++) { // Zoom levels
				markerProperties[i] = []; // Initialize markerProperties storage

				for (var j = 0; j < flowRecordCount; j++) {
					// No geolocation data available
					if ((flowRecords[j].srcCountryLat == 0 && flowRecords[j].srcCountryLng == 0) 
							|| (flowRecords[j].dstCountryLat == 0 && flowRecords[j].dstCountryLng == 0)) {
						continue;
					}
					
					for (var k = 0; k < 2; k++) { // Both ends of a line
						var currentLat = -1;
						var currentLng = -1;
						var currentName = "";
						var locationString = "";
						
						if (i == COUNTRY && k == 0) {
							currentLat = flowRecords[j].srcCountryLat;
							currentLng = flowRecords[j].srcCountryLng;
							currentName = flowRecords[j].srcRegion;
							locationString = flowRecords[j].srcCountry;
						} else if (i == COUNTRY && k == 1) {
							currentLat = flowRecords[j].dstCountryLat;
							currentLng = flowRecords[j].dstCountryLng;
							currentName = flowRecords[j].dstRegion;
							locationString = flowRecords[j].dstCountry;
						} else if (i == REGION && k == 0) {
							currentLat = flowRecords[j].srcRegionLat;
							currentLng = flowRecords[j].srcRegionLng;
							currentName = flowRecords[j].srcCity;
							locationString = flowRecords[j].srcCountry + ", " + flowRecords[j].srcRegion;
						} else if (i == REGION && k == 1) {
							currentLat = flowRecords[j].dstRegionLat;
							currentLng = flowRecords[j].dstRegionLng;
							currentName = flowRecords[j].dstCity;
							locationString = flowRecords[j].dstCountry + ", " + flowRecords[j].dstRegion;
						} else if (i == CITY && k == 0) {
							currentLat = flowRecords[j].srcCityLat;
							currentLng = flowRecords[j].srcCityLng;
							currentName = flowRecords[j].srcCity;
							locationString = flowRecords[j].srcCountry + ", " + flowRecords[j].srcRegion + ", " + flowRecords[j].srcCity;
						} else if (i == CITY && k == 1) {
							currentLat = flowRecords[j].dstCityLat;
							currentLng = flowRecords[j].dstCityLng;
							currentName = flowRecords[j].dstCity;
							locationString = flowRecords[j].dstCountry + ", " + flowRecords[j].dstRegion + ", " + flowRecords[j].dstCity;
						} else if (i == HOST && k == 0) {
							currentLat = flowRecords[j].srcCityLat;
							currentLng = flowRecords[j].srcCityLng;
							currentName = flowRecords[j].srcIP;
							locationString = flowRecords[j].srcCountry + ", " + flowRecords[j].srcRegion + ", " + flowRecords[j].srcCity;
						} else if (i == HOST && k == 1) {
							currentLat = flowRecords[j].dstCityLat;
							currentLng = flowRecords[j].dstCityLng;
							currentName = flowRecords[j].dstIP;
							locationString = flowRecords[j].dstCountry + ", " + flowRecords[j].dstRegion + ", " + flowRecords[j].dstCity;
						} else {
						}
						
						existValue = markerExists(i, currentLat, currentLng);
						if (existValue == -1) { // Marker does not exist
							var properties = new MarkerProperties(currentLat, currentLng, locationString);
							var record = new MarkerRecord(currentName);

							if (i == HOST) {
								record.protocol = flowRecords[j].protocol;
								record.flows = flowRecords[j].flows;
								
								if (k == 0) {
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
							if (existValue2 == -1) { // Name is not present in a record
								var record = new MarkerRecord(currentName);
								
								if (i == HOST) {
									record.protocol = flowRecords[j].protocol;
									record.flows = flowRecords[j].flows;
									if (k == 0) {
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
								if (i == HOST) {
									markerProperties[i][existValue].markerRecords[existValue2].flows = parseInt(markerProperties[i][existValue].markerRecords[existValue2].flows) 
											+ parseInt(flowRecords[j].flows);
								} else {
									var newHost = 1;
									for (var x = 0; x < j; x++) {
										// Check whether the current IP address was present in an earlier (processed) flow record already
										if ((k == 0 && (flowRecords[j].srcIP == flowRecords[x].srcIP || flowRecords[j].srcIP == flowRecords[x].dstIP)) 
												|| (k == 1 && (flowRecords[j].dstIP == flowRecords[x].srcIP || flowRecords[j].dstIP == flowRecords[x].dstIP))) {
											// Check whether the earlier found record was indeed processed (i.e. it doesn't contain 'unknown' locations)
											if (flowRecords[x].srcCountryLat != 0 && flowRecords[x].srcCountryLng != 0 
													&& flowRecords[x].dstCountryLat != 0 && flowRecords[x].dstCountryLng != 0) {
												newHost = 0;
												break;
											}
										}
									}

									if (newHost == 1) {
										markerProperties[i][existValue].markerRecords[existValue2].hosts++;
									}
								}
								markerProperties[i][existValue].markerRecords[existValue2].flowRecordIDs.push(j);
							}
						}
					}
				}

				markers[i] = []; // Initialize marker storage
				
				for (var j = 0; j < markerProperties[i].length; j++) {
					var tableHeader;
					if (i == COUNTRY) {
						tableHeader = "<table style='width: 200px;'><thead class='informationWindowHeader'><tr><th>Region</th><th>Hosts</th></tr></thead>";
					} else if (i == REGION || i == CITY) {
						tableHeader = "<table style='width: 200px;'><thead class='informationWindowHeader'><tr><th>City</th><th>Hosts</th></tr></thead>";
					} else { // i == HOST
						tableHeader = "<table style='width: 400px;'><thead class='informationWindowHeader'><tr><th>IP</th><th>Flows</th><th>Protocol</th><th>Port</th><th>Location</th></tr></thead>";
					}
					
					var orderArray = new Array(); // Contains an ordered list of markerRecord IDs (array indices)
					orderArray.push(0); // The first element to be considered is always the biggest/smallest
					if (i == HOST) { // Sorted by flows
						for (var k = 1; k < markerProperties[i][j].markerRecords.length; k++) {
							for (var l = 0; l < orderArray.length; l++) {
								if (markerProperties[i][j].markerRecords[k].flows >= markerProperties[i][j].markerRecords[orderArray[l]].flows) {
									orderArray.splice(l, 0, k);
									break;
								} else if (l == orderArray.length - 1) {
									orderArray.splice(orderArray.length, 0, k);
									break;
								}
							}
						}
					} else { // Sorted by hosts
						for (var k = 1; k < markerProperties[i][j].markerRecords.length; k++) {
							for (var l = 0; l < orderArray.length; l++) {
								if (markerProperties[i][j].markerRecords[k].hosts >= markerProperties[i][j].markerRecords[orderArray[l]].hosts) {
									orderArray.splice(l, 0, k);
									break;
								} else if (l == orderArray.length - 1) {
									orderArray.splice(orderArray.length, 0, k);
									break;
								}
							}
						}
					}
					
					var flowIDsString = ""; // Contains IDs of the flows that are aggregated in the current marker
					var tableBody = "<tbody class='informationWindowBody'>";
					for (var k = 0; k < orderArray.length; k++) {
						var orderArrayIndex = orderArray[k];
						if (i == HOST) {
							var recordCount = markerProperties[i][j].markerRecords.length;
							
							// TODO Handle case where more than MAX_INFO_WINDOW_LINES lines are present in information window
							
							tableBody += "<tr><td class=\"ipAddress\">" + markerProperties[i][j].markerRecords[orderArrayIndex].name + "</td>";
							tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].flows + "</td>";
							tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].protocol + "</td>";
							
							if (k == 0) {
								tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].port + "</td>";
								tableBody += "<td rowspan='" + recordCount + "'>" + formatName(markerProperties[i][j].markerRecords[orderArrayIndex].countryName) + "<br />" + formatName(markerProperties[i][j].markerRecords[orderArrayIndex].regionName) + "<br />" + formatName(markerProperties[i][j].markerRecords[orderArrayIndex].cityName) + "</td></tr>";
							} else {
								tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].port + "</td></tr>";
							}
						} else {
							tableBody += "<tr><td>" + formatName(markerProperties[i][j].markerRecords[orderArrayIndex].name) + "</td>";
							tableBody += "<td>" + markerProperties[i][j].markerRecords[orderArrayIndex].hosts + "</td></tr>";
						}
						
						for (var l = 0; l < markerProperties[i][j].markerRecords[orderArrayIndex].flowRecordIDs.length; l++) {
							if (flowIDsString != "") flowIDsString += "_";
							flowIDsString += markerProperties[i][j].markerRecords[orderArrayIndex].flowRecordIDs[l];
						}
					}
					
					tableBody += "</tbody>";
					
					var tableFooter = "</table><br /><div class='informationWindowFooter'>"
					 		+ "<a href = 'Javascript:zoom(1, 0, null)'>Zoom In</a><b> - </b><a href = 'Javascript:zoom(1, 1, null)'>Zoom Out</a><br />"
					 		+ "<a href = 'Javascript:zoom(0, 0, null)'>Quick Zoom In</a><b> - </b><a href = 'Javascript:zoom(0, 1, null)'>Quick Zoom Out</a><br />"
							+ "<a href='Javascript:showNetFlowDetails(\"" + flowIDsString + "\");'>Flow details</a></div>";
					
					var markerLocation = new google.maps.LatLng(markerProperties[i][j].lat, markerProperties[i][j].lng);
					var markerTitle = formatName(markerProperties[i][j].locationString);
					var markerContent = "<div id=\"content\">" + tableHeader + tableBody + tableFooter + "</div>";
					markers[i].push(createMarker(markerLocation, i, markerTitle, markerContent));
				}
			}
			
			if (markerManagerInitialized) {
				addMarkersToMarkerManager();
			}
			markersProcessed = true;
		}
		
	    /*
		 * This function initializes all lines for all zoom levels.
		 */			
		function initLines () {
			for (var i = 0; i < 4; i++) { // Zoom levels
				lineProperties[i] = []; // Initialize lineProperties storage
				
				for (var j = 0; j < flowRecordCount; j++) {
					var existValue;
					switch(i) {
						case COUNTRY: 	if ((flowRecords[j].srcCountryLat == 0 && flowRecords[j].srcCountryLng == 0) || (flowRecords[j].dstCountryLat == 0 && flowRecords[j].dstCountryLng == 0)) {
											continue;
										}
										existValue = lineExists(i, flowRecords[j].srcCountryLat, flowRecords[j].srcCountryLng, flowRecords[j].dstCountryLat, flowRecords[j].dstCountryLng);
										break;
										
						case REGION: 	if ((flowRecords[j].srcRegionLat == 0 && flowRecords[j].srcRegionLng == 0) || (flowRecords[j].dstRegionLat == 0 && flowRecords[j].dstRegionLng == 0)) {
											continue;
										}
										existValue = lineExists(i, flowRecords[j].srcRegionLat, flowRecords[j].srcRegionLng, flowRecords[j].dstRegionLat, flowRecords[j].dstRegionLng);
										break;
										
						default: 		if ((flowRecords[j].srcCityLat == 0 && flowRecords[j].srcCityLng == 0) || (flowRecords[j].dstCityLat == 0 && flowRecords[j].dstCityLng == 0)) {
											continue;
										}
										existValue = lineExists(i, flowRecords[j].srcCityLat, flowRecords[j].srcCityLng, flowRecords[j].dstCityLat, flowRecords[j].dstCityLng);
					}
					
					if (existValue == -1) { // Line does not exist
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
						if (record.throughput == "NaN" || record.throughput == "Infinity") {
							record.throughput = 0;
						} else {
							record.throughput = record.throughput.toFixed(2);
						}

						record.flowRecordIDs.push(j);

						if (nfsenOption == 1) {
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

						if (existValue2 == -1) { // Source and destination are not present in one record
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
							if (record.throughput == "NaN" || record.throughput == "Infinity") {
								record.throughput = 0;
							} else {
								record.throughput = record.throughput.toFixed(2);
							}

							record.flowRecordIDs.push(j);

							if (nfsenOption == 1) {
								record.flows = flowRecords[j].flows;
							}
							lineProperties[i][existValue].lineRecords.push(record);
						} else { // Source and destination are present in one record
							lineProperties[i][existValue].lineRecords[existValue2].packets = parseFloat(lineProperties[i][existValue].lineRecords[existValue2].packets) + parseFloat(flowRecords[j].packets);
							lineProperties[i][existValue].lineRecords[existValue2].octets = parseFloat(lineProperties[i][existValue].lineRecords[existValue2].octets) + parseFloat(flowRecords[j].octets);
							lineProperties[i][existValue].lineRecords[existValue2].duration = parseFloat(lineProperties[i][existValue].lineRecords[existValue2].duration) + parseFloat(flowRecords[j].duration);

							lineProperties[i][existValue].lineRecords[existValue2].throughput = lineProperties[i][existValue].lineRecords[existValue2].octets / lineProperties[i][existValue].lineRecords[existValue2].duration;
							if (lineProperties[i][existValue].lineRecords[existValue2].throughput == "NaN" || lineProperties[i][existValue].lineRecords[existValue2].throughput == "Infinity") {
								lineProperties[i][existValue].lineRecords[existValue2].throughput = 0;
							} else {
								lineProperties[i][existValue].lineRecords[existValue2].throughput = lineProperties[i][existValue].lineRecords[existValue2].throughput.toFixed(2);
							}

							lineProperties[i][existValue].lineRecords[existValue2].flowRecordIDs.push(j);

							if (nfsenOption == 1) {
								lineProperties[i][existValue].lineRecords[existValue2].flows = parseInt(lineProperties[i][existValue].lineRecords[existValue2].flows) + parseInt(flowRecords[j].flows);
							} else {
								lineProperties[i][existValue].lineRecords[existValue2].flows++;
							}
						}
					}
				}

				lines[i] = []; // Initialize lines storage
				if (nfsenOption == 0) { // List flows
					determineLineColorRanges(i, "flows");
				} else { // Stat TopN
					determineLineColorRanges(i, nfsenStatOrder);
				}
				
				for (var j = 0; j < lineProperties[i].length; j++) {
					var tableHeader = "<table style='width: 500px;'><thead class='informationWindowHeader'><tr>";
					tableHeader += "<th>Source</th>";
					tableHeader += "<th>Destination</th>";
					tableHeader += "<th>Flows</th>";
					tableHeader += "<th>Packets</th>";
					tableHeader += "<th>Octets</th>";
					tableHeader += "<th>Throughput</th></tr></thead>";

					var orderArray = new Array(); // Contains an ordered list of lineRecord IDs (array indices)
					orderArray.push(0); // The first element to be considered is always the biggest/smallest
					for (var k = 1; k < lineProperties[i][j].lineRecords.length; k++) {
						for (var l = 0; l < orderArray.length; l++) {
							// Ordering in information window is done based on flows
							if (lineProperties[i][j].lineRecords[k].flows >= lineProperties[i][j].lineRecords[orderArray[l]].flows) {
								orderArray.splice(l, 0, k);
								break;
							} else if (l == orderArray.length - 1) {
								orderArray.splice(orderArray.length, 0, k);
								break;
							}
						}
					}
					
					var flowIDsString = ""; // Contains IDs of the flows that are aggregated in the current line
					var tableBody = "<tbody class='informationWindowBody' style='vertical-align:text-top;'>";
					for (var k = 0; k < orderArray.length; k++) {
						var orderArrayIndex = orderArray[k];
						if (i == COUNTRY) {
							tableBody += "<tr><td style=\"font-weight:bold;\">" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcName) + "</td>";
							tableBody += "<td style=\"font-weight:bold;\">" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstName) + "</td>";
							tableBody += "<td>" + lineProperties[i][j].lineRecords[orderArrayIndex].flows + "</td>";
							tableBody += "<td>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].packets) + "</td>";
							tableBody += "<td>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].octets) + "</td>";
							tableBody += "<td>" + formatThroughput(lineProperties[i][j].lineRecords[orderArrayIndex].throughput) + "</td></tr>";
						} else if (i == REGION) {
							tableBody += "<tr><td style=\"font-weight:bold;\">" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentCountryName) + "</td>";
							tableBody += "<td style=\"font-weight:bold;\">" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentCountryName) + "</td>";
							tableBody += "<td rowspan='2'>" + lineProperties[i][j].lineRecords[orderArrayIndex].flows + "</td>";
							tableBody += "<td rowspan='2'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].packets) + "</td>";
							tableBody += "<td rowspan='2'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].octets) + "</td>";
							tableBody += "<td rowspan='2'>" + formatThroughput(lineProperties[i][j].lineRecords[orderArrayIndex].throughput) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstName) + "</td></tr>";
						} else if (i == CITY) {
							tableBody += "<tr><td style=\"font-weight:bold;\">" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentCountryName) + "</td>";
							tableBody += "<td style=\"font-weight:bold;\">" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentCountryName) + "</td>";
							tableBody += "<td rowspan='3'>" + lineProperties[i][j].lineRecords[orderArrayIndex].flows + "</td>";
							tableBody += "<td rowspan='3'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].packets) + "</td>";
							tableBody += "<td rowspan='3'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].octets) + "</td>";
							tableBody += "<td rowspan='3'>" + formatThroughput(lineProperties[i][j].lineRecords[orderArrayIndex].throughput) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentRegionName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentRegionName) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstName) + "</td></tr>";
						} else { // i == HOST
							tableBody += "<tr><td style=\"font-weight:bold;\">" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentCountryName) + "</td>";
							tableBody += "<td style=\"font-weight:bold;\">" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentCountryName) + "</td>";
							tableBody += "<td rowspan='4'>" + lineProperties[i][j].lineRecords[orderArrayIndex].flows + "</td>";
							tableBody += "<td rowspan='4'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].packets) + "</td>";
							tableBody += "<td rowspan='4'>" + applySIScale(lineProperties[i][j].lineRecords[orderArrayIndex].octets) + "</td>";
							tableBody += "<td rowspan='4'>" + formatThroughput(lineProperties[i][j].lineRecords[orderArrayIndex].throughput) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentRegionName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentRegionName) + "</td></tr>";
							tableBody += "<tr><td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcParentCityName) + "</td>";
							tableBody += "<td>" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstParentCityName) + "</td></tr>";
							tableBody += "<tr><td class=\"ipAddress\">" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].srcName) + "</td>";
							tableBody += "<td class=\"ipAddress\">" + formatName(lineProperties[i][j].lineRecords[orderArrayIndex].dstName) + "</td></tr>";
						}

						for (var l = 0; l < lineProperties[i][j].lineRecords[orderArrayIndex].flowRecordIDs.length; l++) {
							if (flowIDsString != "") flowIDsString += "_";
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
					for (var k = 0; k < lineProperties[i][j].lineRecords.length; k++) {
						if (nfsenOption == 0 || nfsenStatOrder == "flows") {
							lineTotal += parseInt(lineProperties[i][j].lineRecords[k].flows);
						} else if (nfsenStatOrder == "packets") {
							lineTotal += parseInt(lineProperties[i][j].lineRecords[k].packets);
						} else if (nfsenStatOrder == "bytes") {
							lineTotal += parseInt(lineProperties[i][j].lineRecords[k].octets);
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
		
	    /*
		 * Determines the line color ranges based on the current flow property
		 * (either flows, packets or bytes).
		 * Parameters:
		 *	level - a SURFmap zoom level: [0..3]
		 *	property - either 'flows', 'packets' or 'bytes'
		 */
		function determineLineColorRanges (level, property) {
			var min = -1;
			var max = -1;

			for (var i = 0; i < lineProperties[level].length; i++) {
				var lineTotal = 0;
				
				for (var j = 0; j < lineProperties[level][i].lineRecords.length; j++) {
					if ((level == COUNTRY 
								&& !(IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION == 1 
								&& lineProperties[level][i].lineRecords[j].srcName == lineProperties[level][i].lineRecords[j].dstName))
							|| (level == REGION
								&& !(IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION == 1
								&& lineProperties[level][i].lineRecords[j].srcParentCountryName == lineProperties[level][i].lineRecords[j].dstParentCountryName
								&& lineProperties[level][i].lineRecords[j].srcName == lineProperties[level][i].lineRecords[j].dstName))
							|| (level == CITY
								&& !(IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION == 1
								&& lineProperties[level][i].lineRecords[j].srcParentCountryName == lineProperties[level][i].lineRecords[j].dstParentCountryName
								&& lineProperties[level][i].lineRecords[j].srcParentRegionName == lineProperties[level][i].lineRecords[j].dstParentRegionName
								&& lineProperties[level][i].lineRecords[j].srcName == lineProperties[level][i].lineRecords[j].dstName))
							|| (level == HOST
								&& !(IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION == 1
								&& lineProperties[level][i].lineRecords[j].srcParentCountryName == lineProperties[level][i].lineRecords[j].dstParentCountryName
								&& lineProperties[level][i].lineRecords[j].srcParentRegionName == lineProperties[level][i].lineRecords[j].dstParentRegionName
								&& lineProperties[level][i].lineRecords[j].srcParentCityName == lineProperties[level][i].lineRecords[j].dstParentCityName))) {
						if (property == "flows") {
							lineTotal += parseInt(lineProperties[level][i].lineRecords[j].flows);
						} else if (property == "packets") {
							lineTotal += parseInt(lineProperties[level][i].lineRecords[j].packets);
						} else if (property == "bytes") {
							lineTotal += parseInt(lineProperties[level][i].lineRecords[j].octets);
						}
					}
				}

				if (lineTotal > 0) {
					if (min == -1 && max == -1) { // initialization values
						min = lineTotal;
						max = lineTotal;
					} else if (lineTotal < min) {
						min = lineTotal;
					} else if (lineTotal > max) {
						max = lineTotal;
					}
				}
			}

			if (min == -1 && max == -1) { // initialization values
				min = 1;
				max = 1;	
			}

			var delta = max - min;
			var categoryDelta = delta / lineColors;
			
			for (var i = 0; i < lineColors + 1; i++) {
				lineColorClassification[i] = min + (i * categoryDelta);
			}
		}
		
	    /*
		 * Returns the actual line color of a line, based on the classification made 
		 * in 'determineLineColorRanges'.
		 * Parameters:
		 *	lineTotal - sum of either flows, packets or bytes of the specific line
		 */
		function determineLineColor (lineTotal) {
			var lineColor;
			
			if (lineColors == 2) {
				if (lineTotal >= lineColorClassification[0] && lineTotal < lineColorClassification[1]) lineColor = green;
				else if (lineTotal >= lineColorClassification[1] && lineTotal <= lineColorClassification[2]) lineColor = orange;
			} else if (lineColors == 3) {
				if (lineTotal >= lineColorClassification[0] && lineTotal < lineColorClassification[1]) lineColor = green;
				else if (lineTotal >= lineColorClassification[1] && lineTotal < lineColorClassification[2]) lineColor = orange;
				else if (lineTotal >= lineColorClassification[2] && lineTotal <= lineColorClassification[3]) lineColor = red;
			} else if (lineColors == 4) {
				if (lineTotal >= lineColorClassification[0] && lineTotal < lineColorClassification[1]) lineColor = green;
				else if (lineTotal >= lineColorClassification[1] && lineTotal < lineColorClassification[2]) lineColor = yellow;
				else if (lineTotal >= lineColorClassification[2] && lineTotal < lineColorClassification[3]) lineColor = orange;
				else if (lineTotal >= lineColorClassification[3] && lineTotal <= lineColorClassification[4]) lineColor = red;
			}
			
			return (lineColor == undefined) ? black : lineColor;
		}
				
	    /*
	     * Checks whether all the DNS names of the IP address in the currently
		 * opened information window are resolved. The resolved names are added
		 * to the information window. As soon as all names are resolved, the
		 * periodic execution of this method is stopped.
		 */		
		function processResolvedDNSNames () {
			// Infowindow or NetFlow data details dialog is visible
			if ($(".informationWindowHeader").is(':visible') || $('span#ui-dialog-title-dialog').is(':visible')) {
				var totalIPCount = $("td.ipAddress:visible").length;
				var resolvedCount = 0;
				
				$("td.ipAddress:visible").each(function(index) {
					if (resolvedDNSNames.hasOwnProperty($(this).text())) {
						resolvedCount++;
						$(this).attr("title", resolvedDNSNames[$(this).text()]);
					}
				});
				
				if (resolvedCount == totalIPCount) {
					for (var i = 0; i < DNSNameResolveQueue.length; i++) {
						clearInterval(DNSNameResolveQueue.splice(0, 1));
					}
				}
			}
		}
		
		/*
		 * Adds debugging information to the DEBUG log queue.
		 */
		function printDebugLogging () {
			queueManager.addElement(queueManager.queueTypes.DEBUG, "Application version: " + applicationVersion);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "DemoMode: " + demoMode);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "EntryCount: " + entryCount);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "FlowRecordCount: " + flowRecordCount);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "NfSenQuery: " + nfsenQuery);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "NfSenAllSources: " + nfsenAllSources);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "NfSenSelectedSources: " + nfsenSelectedSources);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "FlowFilter: " + flowFilter);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "FlowDisplayFilter: " + flowDisplayFilter);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "GeoFilter: " + geoFilter);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "geocoderRequestsSuccess: " + geocoderRequestsSuccess);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "geocoderRequestsError: " + geocoderRequestsError);
			
			queueManager.addElement(queueManager.queueTypes.DEBUG, "Date1: " + date1);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "Date2: " + date2);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "Hours1: " + hours1);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "Hours2: " + hours2);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "Minutes1: " + minutes1);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "Minutes2: " + minutes2);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "LatestDate: " + latestDate);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "LatestHour: " + latestHour);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "LatestMinute: " + latestMinute);
			
			queueManager.addElement(queueManager.queueTypes.DEBUG, "AutoRefresh: " + autoRefresh);
			queueManager.addElement(queueManager.queueTypes.DEBUG, "ErrorCode: " + errorCode);
			
			if (errorMessage == "") {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "ErrorMessage: (empty)");
			} else {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "ErrorMessage: " + errorMessage);
			}
			
			queueManager.addElement(queueManager.queueTypes.DEBUG, "PHP version: <?php echo phpversion(); ?>");
			queueManager.addElement(queueManager.queueTypes.DEBUG, "Client Web browser: " + navigator.appName + "(" + navigator.appVersion + ")");
		}
		
	    /*
		 * This function is called when automatically when loading the SURFmap Web page.
		 * It contains the first stage of processing.
		 */
		function init () {
			queueManager = new QueueManager();
			
			importPHPLogQueue("INFO");
			importPHPLogQueue("ERROR");
			
			if (debugLogging == 1) printDebugLogging();
			
			// Generate processing message (jQuery)
			showDialog("processing", "");

			if (initialZoomLevel == -1) {
				currentSURFmapZoomLevel = initialSURFmapZoomLevel;
				currentZoomLevel = getGoogleMapsZoomLevel(currentSURFmapZoomLevel);
			} else {
				currentZoomLevel = initialZoomLevel;
				currentSURFmapZoomLevel = getSurfmapZoomLevel(initialZoomLevel);
			}

			map = initMap(mapCenter, currentZoomLevel, 2, 13);
			google.maps.event.addListener(map, "click", function() {
				infoWindow.close();
			});
			google.maps.event.addListenerOnce(map, "bounds_changed", function() {
				/*
				 * To make sure that bounds are set after the map has been loaded.
				 * If a gray area is present at the top or bottom of the map, change its center.
				 * Note that this command is called only once (because of addListenerOnce)
				 */
				 mapCenterWithoutGray = hideGrayMapArea();
			});
			google.maps.event.addListener(map, "dragend", function() {
				queueManager.addElement(queueManager.queueTypes.SESSION, new SessionData("mapCenter", map.getCenter().lat() + "," + map.getCenter().lng()));
			});
			google.maps.event.addListener(map, "zoom_changed", function() {
				var newZoomLevel = map.getZoom();
				var newSurfmapZoomLevel = getSurfmapZoomLevel(newZoomLevel);
				queueManager.addElement(queueManager.queueTypes.SESSION, new SessionData("zoomLevel", newZoomLevel));
				
				if (currentSURFmapZoomLevel != newSurfmapZoomLevel) {
					infoWindow.close();
					document.getElementById("netflowDataDetails").innerHTML = "";
					
					refreshLineOverlays(newSurfmapZoomLevel);
					changeZoomLevelPanel(currentSURFmapZoomLevel, newSurfmapZoomLevel);
					initLegend(newSurfmapZoomLevel);
					currentSURFmapZoomLevel = newSurfmapZoomLevel;
				}

				google.maps.event.addListenerOnce(map, "idle", function() {
					if (grayMapAreaPresent()) {
						mapCenterWithoutGray = hideGrayMapArea();
					} else if (map.getCenter() != undefined && map.getCenter().equals(mapCenterWithoutGray)) {
						/*
						 * If the map center was adjusted due to a gray area at the top or bottom of the map, 
						 * change its center again to the actual configured map center.
						 * When called in demo mode, when a random line is clicked by SURFmap, map.getCenter() can be undefined.
						 */
						if (!map.getCenter().equals(mapCenter)) map.setCenter(mapCenter);
					}
				});
			});
			
			markerManager = new MarkerManager(map);
			google.maps.event.addListener(markerManager, "loaded", function() { 
				if (markersProcessed) {
					addMarkersToMarkerManager();
				}
				markerManagerInitialized = true;
			});
			
			geocoder = new google.maps.Geocoder();
			infoWindow = new google.maps.InfoWindow({maxWidth: 1000});
			
			changeZoomLevelPanel(0, currentSURFmapZoomLevel);
			if (debugLogging == 1) {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 1. Basic initialization completed");
			}

			if (errorCode == 1 || errorCode >= 5) { // an error has occurred
				switch (errorCode) {
					case 1:		generateAlert(1);
								queueManager.addElement(queueManager.queueTypes.DEBUG, "Stopped initialization due to flow filter error");
								break;
							
					case 5:		if (showWarningOnNoData == 1) {
									generateAlert(5);
								} else {
									$("#dialog").dialog("destroy"); // Hide processing message
								}
								queueManager.addElement(queueManager.queueTypes.DEBUG, "Stopped initialization due to no data error");
								break;

					case 6:		generateAlert(6);
								queueManager.addElement(queueManager.queueTypes.DEBUG, "Stopped initialization due to profile error");
								break;

					case 7:		generateAlert(7);
								queueManager.addElement(queueManager.queueTypes.DEBUG, "Stopped initialization due to GeoFilter error");
								break;

					case 8:		generateAlert(8);
								queueManager.addElement(queueManager.queueTypes.DEBUG, "Stopped initialization due to flow query kill");
								$("#dialog").dialog("destroy"); // Hide processing message
								break;
		
					default:	generateAlert(errorCode);
								break;
				}
				
				$('#legend').hide();
				serverTransactions();
				return;	
			}

			setProcessingText("Importing NetFlow data...");
			if (debugLogging == 1) {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 2. Importing NetFlow data...");
			}
			importData();
			if (debugLogging == 1) {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 2. Importing NetFlow data... Done");
			}
			
			setProcessingText("Complementing flow records...");
			if (debugLogging == 1) {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 3. Complementing flow records...");
			}
			complementFlowRecords();
			if (debugLogging == 1) {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 3. Complementing flow records... Done");
			}
			
			setInterval("serverTransactions()", 500);
		}
		
	   /*
		 * This function contains the second stage of processing.
		 */		
		function processing() {
			setProcessingText("Initializing lines...");
			if (debugLogging == 1) {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 4. Initializing lines...");
			}
			initLines();
			if (debugLogging == 1) {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 4. Initializing lines... Done");
			}

			setProcessingText("Initializing markers...");
			if (debugLogging == 1) {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 5. Initializing markers...");
			}
			initMarkers();
			if (debugLogging == 1) {
				queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 5. Initializing markers... Done");
			}
			
			if (demoMode == 0) {
				setProcessingText("Initializing legend...");
				if (debugLogging == 1) {
					queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 6. Initializing legend...");
				}
				initLegend(currentSURFmapZoomLevel);
				if (debugLogging == 1) {
					queueManager.addElement(queueManager.queueTypes.DEBUG, "Progress: 6. Initializing legend... Done");
				}
			}
			
			checkForHeavyQuery();
			if (USE_GEOCODER_DB == 1 && WRITE_DATA_TO_GEOCODER_DB == 1 
					&& successfulGeocodingRequests + erroneousGeocodingRequests + skippedGeocodingRequests > 0) {
				queueManager.addElement(queueManager.queueTypes.STAT, new StatData("geocoderRequestsSuccess", successfulGeocodingRequests));
				queueManager.addElement(queueManager.queueTypes.STAT, new StatData("geocoderRequestsError", erroneousGeocodingRequests));
				queueManager.addElement(queueManager.queueTypes.STAT, new StatData("geocoderRequestsSkip", skippedGeocodingRequests));
				queueManager.addElement(queueManager.queueTypes.STAT, new StatData("geocoderRequestsBlock", blockedGeocodingRequests));
			}

			setProcessingText("Finished loading...");
			queueManager.addElement(queueManager.queueTypes.INFO, "Initialized");
			
			$("#dialog").dialog("destroy"); // Hide processing message

			if (errorCode >= 2 && errorCode <= 4) {
				generateAlert(errorCode);
			}
		}

	</script>
</head>
<body>
	<div id="header" style="width:<?php if (strpos($RELATIVE_MAP_WIDTH, "%") === false) echo "$RELATIVE_MAP_WIDTH%"; else echo $RELATIVE_MAP_WIDTH; ?>; clear:both;">
		<span id="logo" style="float:right;"><a href="http://www.utwente.nl/en" target="_blank"><img src="images/UT_Logo.png" alt="University of Twente"/></a></span>
		<div id="headerText"><p /></div>
	
	<script type="text/javascript">
		var clientHeight = parent.document.documentElement.clientHeight;
		
		/*
		 * IE8/IE9 does not properly support an iFrame width/height of 100% 
		 * when "<meta http-equiv="X-UA-Compatible" content="IE=edge" />" is used.
		 * http://brondsema.net/blog/index.php/2007/06/06/100_height_iframe
		 */
		if ($("meta[http-equiv='X-UA-Compatible'][content='IE=edge']").length > 0 // Check whether the problematic meta-tag has been set
				&& $.browser.msie && parseInt($.browser.version) >= 8) {
			parent.document.getElementById("surfmapParentIFrame").style.height = clientHeight +"px";
		}

		if (clientHeight < 850) {
			$('#logo').hide();
			
			if (demoMode == 1) {
				$('#headerText').css('font-size', '20pt');
				$('#headerText').css('height', '40px');
				$('#headerText').css('text-align', 'center');
				$('#headerText p').text(demoModePageTitle + ' (' + hours1 + ':' + minutes1 + ')');
			} else {
				$('#headerText').css('font-size', '10pt');
				$('#headerText').css('height', '30px');
				$('#headerText').css('text-align', 'right');
				$('#headerText p').html('<b>SURFmap</b> - <i>A network monitoring tool based on the Google Maps API</i>');
			}
		} else {
			$('#headerText p').css('margin-top', '18px');
			
			if (demoMode == 1) {
				$('#headerText').css('font-size', '30pt');
				$('#headerText').css('height', '60px');
				$('#headerText').css('text-align', 'center');
				$('#headerText p').text(demoModePageTitle + ' (' + hours1 + ':' + minutes1 + ')');
				$('#logo').hide();
			} else {
				$('#headerText').css('float', 'right');
				$('#headerText').css('font-size', '10pt');
				$('#headerText').css('height', '76px');
				$('#headerText p').html('<b>SURFmap</b><br /><i>A network monitoring tool based on the Google Maps API</i>');
			}
		}
	</script>
	
	</div> <!-- Close header -->
	
	<div id="map_canvas" style="width:<?php if (strpos($RELATIVE_MAP_WIDTH, "%") === false) echo "$RELATIVE_MAP_WIDTH%"; else echo $RELATIVE_MAP_WIDTH; ?>; height:<?php if (strpos($RELATIVE_MAP_HEIGHT, "%") === false) echo "$RELATIVE_MAP_HEIGHT%"; else echo $RELATIVE_MAP_HEIGHT; ?>;"></div>
	<div id="footer" style="width:<?php if (strpos($RELATIVE_MAP_WIDTH, "%") === false) echo "$RELATIVE_MAP_WIDTH%"; else echo $RELATIVE_MAP_WIDTH; ?>;">
		<div class="footer" id="legend">
			<div class="legendTextCell" id="legend_based_on" style="width: 175px; padding-left:0px;"></div>
			<div class="legendImageCell" style="padding-left:10px;"><img src="images/legend/legend_green.png" alt="Legend_green"/></div><div class="legendTextCell" id="legend_green"></div>
			<div class="legendImageCell"><img src="images/legend/legend_yellow.png" alt="legend_yellow"/></div><div class="legendTextCell" id="legend_yellow"></div>
			<div class="legendImageCell"><img src="images/legend/legend_orange.png" alt="legend_orange"/></div><div class="legendTextCell" id="legend_orange"></div>
			<div class="legendImageCell"><img src="images/legend/legend_red.png" alt="legend_red"/></div><div class="legendTextCell" id="legend_red"></div>
		</div>
		<div class="footer" id="footerfunctions" style='float:right;'><a href="Javascript:showNetFlowDetails('');" title="Show flow details">Flow details</a> | <a href="Javascript:showDialog('help', '');" title="Show help information">Help</a> | <a href="Javascript:showDialog('about','');" title="Show about information">About</a></div>
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
			+ "<form><input type=\"radio\" id=\"countryZoomRadio\" name=\"zoomLevel\" value=\"country\" onclick=\"zoom(1, 0, 2);\" /><label for=\"countryZoomRadio\">Country</label><br />"
			+ "<input type=\"radio\" id=\"regionZoomRadio\" name=\"zoomLevel\" value=\"region\" onclick=\"zoom(1, 0, 5);\" /><label for=\"regionZoomRadio\">Region</label><br />"
			+ "<input type=\"radio\" id=\"cityZoomRadio\" name=\"zoomLevel\" value=\"city\" onclick=\"zoom(1, 0, 8);\" /><label for=\"cityZoomRadio\">City</label><br />"
			+ "<input type=\"radio\" id=\"hostZoomRadio\" name=\"zoomLevel\" value=\"host\" onclick=\"zoom(1, 0, 11);\" /><label for=\"hostZoomRadio\">Host</label><br />"
			+ "</form></td><td style=\"vertical-align:bottom;\">"
			+ "<input type=\"checkbox\" id=\"auto-refresh\" onclick=\"manageAutoRefresh(this.id);\" /><label for=\"auto-refresh\">Auto-refresh</label></td></tr></table>";
		
		// Panel: Options
		var truncatedNfSenProfile = (nfsenProfile.length > 22) ? nfsenProfile.substr(0, 22) + "..." : nfsenProfile;
		var nfsenSourceOptions = "<optgroup label=\"Profile '" + truncatedNfSenProfile + "'\">";
		for (var i = 0; i < nfsenAllSources.length; i++) {
			var sourceSelected = false;
			for (var j = 0; j < nfsenSelectedSources.length; j++) {
				if (nfsenSelectedSources[j] == nfsenAllSources[i]) {
					sourceSelected = true;
				}
			}

			if (sourceSelected) {
				nfsenSourceOptions += "<option selected>" + nfsenAllSources[i] + "</option>";
			} else {
				nfsenSourceOptions += "<option>" + nfsenAllSources[i] + "</option>";
			}
		}
		nfsenSourceOptions += "</optgroup>";
		
		$("#optionPanel").html(" \
			<form id=\"options\" method=\"GET\" action=\"index.php\"> \
				<table> \
					<tr> \
						<td style=\"width:90px;\">Sources</td> \
						<td> \
							<select id=\"nfsensources\" name=\"nfsensources[]\" multiple=\"multiple\">" + nfsenSourceOptions + "</select> \
						</td> \
					</tr> \
				</table><br /> \
				<input type=\"radio\" id=\"nfsenoptionStatTopN\" name=\"nfsenoption\" value=\"1\" onclick=\"if (!$('#nfsenstatorder').is(':visible')) $('#nfsenstatorder').toggle('fast');checkForHeavyQuery();\" /><label for=\"nfsenoptionStatTopN\">Stat TopN</label><br /> \
				<div id=\"nfsenstatorder\" style=\"margin-top:10px; margin-bottom:10px; text-align:right;\">"
				+ "<input type=\"radio\" id=\"nfsenstatorderflows\" name=\"nfsenstatorder\" value=\"flows\" /><label for=\"nfsenstatorderflows\">flows</label>"
				+ "<input type=\"radio\" id=\"nfsenstatorderpackets\" name=\"nfsenstatorder\" value=\"packets\" /><label for=\"nfsenstatorderpackets\">packets</label>"
				+ "<input type=\"radio\" id=\"nfsenstatorderbytes\" name=\"nfsenstatorder\" value=\"bytes\" /><label for=\"nfsenstatorderbytes\">bytes</label>"
				+ "</div> \
				<input type=\"radio\" id=\"nfsenoptionListFlows\" name=\"nfsenoption\" value=\"0\" onclick=\"if ($('#nfsenstatorder').is(':visible')) $('#nfsenstatorder').toggle('fast');checkForHeavyQuery();\" /><label for=\"nfsenoptionListFlows\">List Flows</label><br /> \
				<div style=\"margin-top:10px; width:195px;\"> \
					<span style=\"float:left;\">Begin</span> \
					<input type=\"text\" id=\"datetime1\" class=\"dateTimeInput\" name=\"datetime1\" /> \
					<div class=\"ui-state-default ui-corner-all noButtonBackground\" style=\"float:right;\"> \
						<span class=\"ui-icon ui-icon-arrowthick-1-e\" title=\"Copy 'end' time to here\" onclick=\"copyDateTime('datetime2', 'datetime1');\"></span> \
					</div> \
				</div><br /> \
				<div style=\"margin-top:10px; width:195px;\"> \
					<span style=\"float:left;\">End</span> \
					<input type=\"text\" id=\"datetime2\" class=\"dateTimeInput\" name=\"datetime2\" /> \
					<div class=\"ui-state-default ui-corner-all noButtonBackground\" style=\"float:right;\"> \
						<span class=\"ui-icon ui-icon-arrowthick-1-e\" title=\"Copy 'begin' time to here\" onclick=\"copyDateTime('datetime1', 'datetime2');\"></span> \
					</div> \
				</div><br /> \
				<div style=\"margin-top:10px; width:195px;\"> \
					<span style=\"float:left;\">Limit to</span> \
					<span style=\"width:127px; float:right;\"><input type=\"text\" id=\"flowsinput\" name=\"amount\" style=\"width:35px; padding:2px 0px 2px 0px; text-align:center;\" maxlength=\"4\" value=\"" + entryCount + "\" /><label for=\"flowsinput\"> flows</label><span> \
				</div><br /> \
				<div style=\"margin-top:15px; width:195px;\"> \
					<div class=\"ui-state-default ui-corner-all noButtonBackground\" style=\"float:left;\"> \
						<span id=\"flowFilterButton\" class=\"ui-icon filterButton\" title=\"Show flow filter\"></span> \
					</div> \
					<span id=\"flowFilterHeader\" class=\"filterHeader\" style=\"float:left; cursor:pointer;\">Flow filter</span><br /> \
					<textarea id=\"flowFilter\" class=\"filterinput\" name=\"flowFilter\" rows=\"2\" cols=\"26\" style=\"font-size:11px; margin-top:2px;\"></textarea> \
				</div><br /> \
				<div style=\"width:195px;\"> \
					<div class=\"ui-state-default ui-corner-all noButtonBackground\" style=\"float:left;\"> \
						<span id=\"geoFilterButton\" class=\"ui-icon filterButton\" title=\"Show geo filter\"></span> \
					</div> \
					<span id=\"geoFilterHeader\" class=\"filterHeader\" style=\"float:left; cursor:pointer;\">Geo filter</span><br /> \
					<textarea id=\"geoFilter\" class=\"filterinput\" name=\"geoFilter\" rows=\"2\" cols=\"26\" style=\"font-size:11px; margin-top:2px;\"></textarea> \
				</div><br /> \
				<div style=\"text-align:center; width:195px;\"> \
					<div id=\"heavyquerymessage\" style=\"color:#FF192A; display:none; margin-bottom:5px;\">Warning: you've selected a potentially heavy query!</div> \
					<input type=\"submit\" name=\"submit\" value=\"Submit\" /> \
				</div> \
			</form>");
			
		init();

		// Select the current option in the 'nfsenstatorder' selector
		var options = $("#nfsenstatorder").children("input[name='nfsenstatorder']");
		for (var i = 0; i < options.length; i++) {
			if (options[i].id.substring(14) == nfsenStatOrder) {
				options[i].checked = true;
				break;
			}
		}

		if (nfsenOption == 1) { // Stat TopN
			document.getElementById("nfsenoptionStatTopN").checked = true;
		} else {
			document.getElementById("nfsenoptionListFlows").checked = true;
			$('#nfsenstatorder').hide();
		}
		
		if (autoRefresh > 0) {
			document.getElementById("auto-refresh").checked = true;
			manageAutoRefresh();
		} else {
			document.getElementById("auto-refresh").checked = false;
		}
		
		// Initialize panel
		if (demoMode == 0) {
			$('a.trigger').click(function(){
				$(".panel").toggle("fast");
				$(this).toggleClass("active");
				return false;
			});
			
			if (clientHeight < 850) {
				$('a.trigger').css('top', '43px');
				$('.panel').css('top', '43px');
			} else {
				$('a.trigger').css('top', '89px');
				$('.panel').css('top', '89px');
			}
		} else {
			$('a.trigger').hide();
		}
		
		if (autoOpenMenu == 1 || errorCode == 1) {
			$('a.trigger').trigger('click');
		}
		
		if (demoMode == 1) {
			$('#map_canvas').css('width', '100%');
			$('#map_canvas').css('height', '100%');
			$('#legend').hide();
			$('#footerfunctions').hide();
		}
		
		// Initialize date/time pickers (http://trentrichardson.com/examples/timepicker/)
		$('.dateTimeInput').datetimepicker({
			maxDate: new Date(latestDate.substr(0, 4), latestDate.substr(4, 2) - 1, latestDate.substr(6, 2), latestHour, latestMinute),
			stepMinute: 5,
			onClose: function(dateText, inst) {
				checkForHeavyQuery();
			}
		});
		$('#datetime1').datetimepicker('setDate', new Date(date1.substr(0, 4), parseInt(date1.substr(4, 2)) - 1, date1.substr(6, 2), hours1, minutes1));
		$('#datetime2').datetimepicker('setDate', new Date(date2.substr(0, 4), parseInt(date2.substr(4, 2)) - 1, date2.substr(6, 2), hours2, minutes2));
		
		// Initialize buttons (jQuery)
		$('#options').submit(function() {
			if ($("#nfsensources").multiselect("widget").find("input:checked").length == 0) {
				generateAlert(999); // This error code is only client-side
				return false;
			} else {
		    	$('input[type=submit]', this).attr('disabled', 'disabled');
				$('a.trigger').trigger("click");
				setTimeout("showDialog('processing', '');setProcessingText('Querying NetFlow data...');", 100);
				return true;
			}
		});
		
		// Initialize button set (jQuery)
		$("#nfsenstatorder").buttonset();
		
		// Initialize source selector (jQuery)
		$("#nfsensources").multiselect({
			minWidth: 135,
			header: true,
			open: function() {
				$("div.ui-multiselect-menu").css("left", "");
				$("div.ui-multiselect-menu").css("right", "23px");
				$("div.ui-multiselect-menu").css("width", "175px");
			},
			close: function() {
				checkForHeavyQuery();
			}
		});
		
		// Initialize filter areas
		if (flowDisplayFilter == "") {
			$('#flowFilterButton').addClass('ui-icon-triangle-1-e');
			$('#flowFilter').hide();
		} else {
			$('#flowFilterButton').addClass('ui-icon-triangle-1-s');
			$('#flowFilter').text(flowDisplayFilter);
		}
		if (geoFilter == "") {
			$('#geoFilterButton').addClass('ui-icon-triangle-1-e');
			$('#geoFilter').hide();
		} else {
			$('#geoFilterButton').addClass('ui-icon-triangle-1-s');
			$('#geoFilter').text(geoFilter);
		}
		$('.filterHeader, .filterButton').click(function(event) {
			var target = $('#' + event.target.id);
			
			// Change to appropriate target if text has been clicked instead of button
			if (target.attr('class').indexOf('filterHeader') != -1) {
				target = target.prev().children("span:first");
			}
			
			target.toggleClass('ui-icon-triangle-1-e').toggleClass('ui-icon-triangle-1-s');

			var textArea;
			if (target.attr('id') == "flowFilterHeader" || target.attr('id') == "flowFilterButton") {
				textArea = $('#flowFilter');
			} else {
				textArea = $('#geoFilter');
			}
			
			if (target.hasClass('ui-icon-triangle-1-s')) {
				textArea.show();
			} else {
				textArea.hide();
			}
		});
		
		// Forbid entering 'new line' in filter input textarea
		$(".filterinput").keypress(function(event) {
		    if (event.keyCode == 13) return false;
		});

	   /*
		* Checks whether a (suspected) heavy query has been selected. This is done based on the amount
		* of selected sources and the filter length.
		*/		
		function checkForHeavyQuery () {
			var heavyQuery = false;
			var timePeriod = ($('#datetime2').datetimepicker('getDate') - $('#datetime1').datetimepicker('getDate')) / 1000;

			if ($("#nfsensources").multiselect("widget").find("input:checked").length > 4
					|| (timePeriod > 3600 && $('#nfsenoptionStatTopN').attr('checked') == 'checked')) { // 1800 seconds -> 60 minutes
				heavyQuery = true;
			}

			if (showWarningOnHeavyQuery && heavyQuery) {
				$("#heavyquerymessage").show();
			} else {
				$("#heavyquerymessage").hide();
			}
		}		
		
	   /*
		* Shows the NfSen flow output / overview (depends on whether or not it is already present) in a dialog.
		* When the table has been generated, showDialog() is called to put it into a jQuery dialog.
		* Parameters:
		*		flowIDs - IDs of the flow records of which the table should be composed. Provide an empty String
		*					to show all flow records
		*/
		function showNetFlowDetails (flowIDs) {
			if (flowIDs != "") { // if flowIDs == "", all flows should be presented
				var idArray = flowIDs.split("_");
				var idCount = idArray.length;
			} else {
				var idCount = flowRecordCount;
			}
			
			var netflowDataDetailsTable = " \
					<table style='text-align: center;'> \
						<thead class='netflowDataDetailsTitle'> \
							<tr> \
								<th>Duration</th> \
								<th>Source IP</th> \
								<th>Source Port</th> \
								<th>Destination IP</th> \
								<th>Destination Port</th> \
								<th>Protocol</th> \
								<th>Packets</th> \
								<th>Octets</th>";
			
			// If NfSen is used as the information source and its 'Stat TopN' option is used
			if (nfsenOption == 1) {
				netflowDataDetailsTable += "<th>Flows</th>";
				var columns = 9;
			} else {
				var columns = 8;
			}
			
			netflowDataDetailsTable += " \
							</tr> \
						</thead> \
						<tbody class='netflowDataDetails'>";

			if (idCount == 0) {
				netflowDataDetailsTable += "<tr><td colspan='" + columns + "' style='text-align: center;'>No flow records are available...</td></tr>";				
			} else {
				for (var i = 0; i < idCount; i++) {
					netflowDataDetailsTable += "<tr>";

					var currentID = -1;
					if (flowIDs != "") currentID = idArray[i];
					else currentID = i;

					netflowDataDetailsTable += " \
							<td>" + flowRecords[currentID].duration + "</td> \
							<td class=\"ipAddress\">" + flowRecords[currentID].srcIP + "</td> \
							<td>" + flowRecords[currentID].srcPort + "</td> \
							<td class=\"ipAddress\">" + flowRecords[currentID].dstIP + "</td> \
							<td>" + flowRecords[currentID].dstPort + "</td> \
							<td>" + flowRecords[currentID].protocol + "</td> \
							<td>" + applySIScale(flowRecords[currentID].packets) + "</td> \
							<td>" + applySIScale(flowRecords[currentID].octets) + "</td>";
					if (nfsenOption == 1) {
						netflowDataDetailsTable += " \
							<td title='Flows'>" + flowRecords[currentID].flows + "</td>";
					}

					netflowDataDetailsTable += "</tr>";
				}
			}

			netflowDataDetailsTable += " \
						</tbody> \
					</table>";
			showDialog("netflowDetails", netflowDataDetailsTable);
			
			$("td.ipAddress:visible").each(function(index) {
				if (!resolvedDNSNames.hasOwnProperty($(this).text())) {
					queueManager.addElement(queueManager.queueTypes.DNS, $(this).text());
				}					
			});
				
			// Store ID of setInterval in first array position
			DNSNameResolveQueue.push(setInterval("processResolvedDNSNames()", 250));
		}

	   /*
		* Sets the center of the Google Maps map to the specified endpoint (either source's endpoint, or destination's 
		* endpoint, depending on parameter).
		* Parameters:
		*		zoomLevel - a SURFmap zoom level
		*		lineID - unique ID of the line to which's end points should be navigated
		* 		endPoint - either "source" or "destination"
		*/
		function goToLineEndPoint (zoomLevel, lineID, endPoint) {
			if (endPoint == "source") {
				var source = new google.maps.LatLng(lineProperties[zoomLevel][lineID].lat1, lineProperties[zoomLevel][lineID].lng1);
				map.setCenter(source);
				infoWindow.setPosition(source);
			} else {
				var destination = new google.maps.LatLng(lineProperties[zoomLevel][lineID].lat2, lineProperties[zoomLevel][lineID].lng2);
				map.setCenter(destination);
				infoWindow.setPosition(destination);
			}
		}
		
	   /*
		* Writes the legend beneath the Google Maps map, depending on the line color classification.
		* Parameters:
		*		zoom_level - a SURFmap zoom level
		*/
		function initLegend (zoomLevel) {
			if (nfsenOption == 0) { // List flows
				document.getElementById("legend_based_on").innerHTML = "Number of observed flows:";
				determineLineColorRanges(zoomLevel, "flows");
			} else { // Stat TopN
				document.getElementById("legend_based_on").innerHTML = "Number of observed " + nfsenStatOrder + ":";
				determineLineColorRanges(zoomLevel, nfsenStatOrder);
			}
			
			document.getElementById("legend_green").innerHTML = "[ " + applySIScale(lineColorClassification[0]) + ", " + applySIScale(lineColorClassification[1]) + " >";
			document.getElementById("legend_yellow").innerHTML = "[ " + applySIScale(lineColorClassification[1]) + ", " + applySIScale(lineColorClassification[2]) + " >";
			document.getElementById("legend_orange").innerHTML = "[ " + applySIScale(lineColorClassification[2]) + ", " + applySIScale(lineColorClassification[3]) + " >";
			document.getElementById("legend_red").innerHTML = "[ " + applySIScale(lineColorClassification[3]) + ", " + applySIScale(lineColorClassification[4]) + " ]";
		}
		
	   /*
		* This function changes the indicated current zoom level in the table to the
		* right of the SURFmap v3 map.
		* Parameters:
		*		old_zoom_level - the previous zoom level in terms of the four SURFmap zoom levels
		*		new_zoom_level - the next zoom level in terms of the four SURFmap zoom levels
		*/
		function changeZoomLevelPanel (old_zoom_level, new_zoom_level) {
			var zoomLevels = document.getElementById("zoomLevels");
			var rows = zoomLevels.getElementsByTagName("input");
			rows[old_zoom_level].checked = false;
			rows[new_zoom_level].checked = true;
		}
				
	   /*
		* Copies date/time from one date/time selector to another.
		* Parameters:
		*		selector1 - ID of the source date/time selector
		*		selector2 - ID of the destination date/time selector
		*/			
		function copyDateTime (selector1, selector2) {
			$("#" + selector2).datetimepicker('setDate', new Date($("#" + selector1).datetimepicker('getDate')));
			
			// Workaround for date/time picker copying, as described here: https://github.com/trentrichardson/jQuery-Timepicker-Addon/issues/280
			var setDate = $("#" + selector2).datetimepicker('getDate');
			if (setDate.getHours() == 0 && setDate.getMinutes() == 0) {
				$("#" + selector2).datetimepicker('setDate', new Date($("#" + selector1).datetimepicker('getDate')));
			}
		}
		
	   /*
		* Manages the execution or stop of auto-refresh, based on the checkbox in the
		* user interface.
		* Parameters:
		*		sourceID - ID of the source that called this method (e.g. a button ID)
		*/		
		function manageAutoRefresh (sourceID) {
			if (document.getElementById("auto-refresh").checked) {
				queueManager.addElement(queueManager.queueTypes.SESSION, new SessionData("refresh", 300));
				autoRefreshID = setTimeout("window.location.replace(\"index.php?autorefresh=1\")", 300000);
				
				/*
				Perform immediate auto-refresh only when auto-refresh has been enabled
				during the current session.
				*/
				if (sourceID == "auto-refresh") {
					// Perform auto-refresh directly after queues are empty
					setInterval("if (queueManager.getTotalQueueSize() == 0) { window.location.replace(\"index.php?autorefresh=1\"); }", 500);
				}
			} else {
				queueManager.addElement(queueManager.queueTypes.SESSION, new SessionData("refresh", 0));
				clearTimeout(autoRefreshID);
			}
		}
		
	</script>
</body>
</html>
