<?php
	/*******************************
	 # config.php [SURFmap]
	 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
	 # University of Twente, The Netherlands
	 #
	 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
	 *******************************/
	require_once("objects.php");
	
	/* [Application parameters] */
	$MAP_CENTER="52.217,6.9"; // Center of the map, specified by latitude and longitude coordinates; coordinates should be separated by a comma (,) [default: '52.217,6.9']
	$RELATIVE_MAP_WIDTH="97%"; // Relative map size compared to SURFmap iFrame [default: '97%']
	$RELATIVE_MAP_HEIGHT="95%"; // Relative map size compared to SURFmap iFrame [default: '95%']
	$DEFAULT_FLOW_RECORD_COUNT=-1; // Default amount of flow records to be selected - '-1': let SURFmap decide depending on the selected geolocation service [default: -1]
	$DEFAULT_QUERY_TYPE=1; // Default NfSen option - 0: Flow listing, 1: Stat TopN
	$DEFAULT_QUERY_TYPE_STAT_ORDER="bytes"; // Indicates the field on which statistics should be based, 'flows', 'packets' or 'bytes' [default: 'bytes']
	$DEFAULT_ZOOM_LEVEL=0; // Default SURFmap zoom level (i.e. Country (0), Region (1), City (2), Host(3)) [default: 0]
	$LOG_DEBUG=0; // If enabled, debug logging is printed to the log file [default: 0]
	$LOG_ERRORS_ONLY=0; // If enabled, only log messages of type 'ERROR' are written to the log file [default: 0]
	$AUTO_OPEN_MENU=0; // If enabled, the settings menu will open automatically when SURFmap is loaded [default: 0]
	$FORCE_HTTPS=1; // If enabled, HTTPS will be used to contact Google for retrieving the maps [default: 1]
	$SHOW_WARNING_ON_NO_DATA=1; // If enabled, a warning message is shown in the user interface to inform about the issue [default: 1]
	$SHOW_WARNING_ON_HEAVY_QUERY=1; // If enabled, a warning message is shown in the user interface to inform about apotential heavy query [default: 1]
	$SORT_FLOWS_BY_START_TIME=0; // Sorts flows by start time - 0: no, 1: yes [default: 0]
	
	/* [NfSen] */
	$NFSEN_CONF="/data/nfsen/etc/nfsen.conf"; // Path to NfSen configuration file [example: '/data/nfsen/etc/nfsen.conf']
	$NFSEN_DEFAULT_SOURCES=""; // NfSen sources which should be selected by default, if available. Separate multiple sources by a semicolon [default: '', example: 'core-router;backup-router']
	
	/* [GeoLocation] */
	$GEOLOCATION_DB="MaxMind"; // "IP2Location" or "MaxMind" [default: 'MaxMind']
	$MAXMIND_PATH="MaxMind/GeoLiteCity.dat"; // Will be ignored when $GEOLOCATION_DB is not set to "MaxMind" [default: 'MaxMind/GeoLiteCity.dat']
	$IP2LOCATION_PATH="IP2Location/IP-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE.BIN"; // Will be ignored when $GEOLOCATION_DB is not set to "IP2Location" [default: 'IP2Location/IP-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE.BIN']
	
	/* [GeoCoding] */
	$USE_GEOCODER_DB=1; // Indicates whether the geocoder cache database should be used - 0: no, 1: yes [default: 1]
	$WRITE_DATA_TO_GEOCODER_DB=1; // Indicates whether geocoded locations should be written to geocoder cache database - 0: no, 1: yes [default: 1]
	$GEOCODER_DB_SQLITE3="geocoder/geocoder_cache.sqlite3"; // Path to the SQLite3 database file [default: 'geocoder/geocoder_cache.sqlite3']
	
	/* [Internal traffic]
	 * You can specify 'internal domains' here which force SURFmap to apply static geocoding. This is especially useful in NATed setups, where private IP addresses are used.
	 * The default configuration can be extended by adding a new line to the $INTERNAL_DOMAINS array (Don't forget to add a comma after each line in the array, except for the last line!):
	 *
	 * "<domains>" => array("country" => "<country>", "region" => "<region>", "city" => "<city>")
	 * 
	 * Explanation of the fields:
	 *		1: domains - NfSen filter subnet notation to indicate your internal domain (e.g. NATed) traffic. Multiple domains must be separated by a semicolon [example: '192.168/16;172.16/12;10.0/8']
	 *		2: country - Indicates the country in which a NATed network relies. If left empty, matching flow records will be ignored.
 	 *		3: region - Indicates the region in which a NATed network relies. Leave this setting empty, if unknown..
 	 *		4: city - Indicates the city in which a NATed network relies. Leave this setting empty, if unknown..
	 */
	$INTERNAL_DOMAINS = array(
			"192.168/16;172.16/12;10.0/8" => array("country" => "THE NETHERLANDS", "region" => "OVERIJSSEL", "city" => "ENSCHEDE")
	);
	
	$HIDE_INTERNAL_DOMAIN_TRAFFIC=1; // Indicates whether your internal domain traffic should be visualized in SURFmap - 0: no, 1: yes [default: 1]
	$IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION=1; // Indicates whether traffic 'inside' a marker (e.g., inside a country, region or city) should be ignored in the line color classification process - 0: no, 1: yes [default: 1]

	/* [Demo Mode] */
	$DEMO_MODE=0; // Enables or disables SURFmap's demo mode - 0: no, 1: yes [default: 0]
	$DEMO_MODE_PAGE_TITLE="Current network traffic across the world"; // Title to be displayed in demo mode
	
	/* [Proxy] */
	$USE_PROXY=0; // Only enable this setting if your Web server is behind a proxy [default: 0]
	$PROXY_IP="127.0.0.1"; // IP address of the proxy
	$PROXY_PORT=8080; // Port to connect to the proxy [default: 8080]
	$PROXY_USER_AUTHENTICATION=0; // Enable this setting if your proxy requires authentication (username and password) [default: 0]
	$PROXY_USERNAME_PASSWORD="username:password"; // Login credentials for the proxy, in format 'username:password'
?>