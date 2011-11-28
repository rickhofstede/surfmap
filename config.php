<?php
/*******************************
 * config.php [SURFmap]
 * Author: Rick Hofstede
 * University of Twente, The Netherlands
 *
 * LICENSE TERMS: outlined in BSD-license.html
 *******************************/
	
	// [Application parameters]
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
	
	// [NfSen]
	$NFSEN_CONF="/etc/nfsen.conf"; // Path to NfSen configuration file [example: '/etc/nfsen.conf']
	$NFSEN_DEFAULT_SOURCES=""; // NfSen sources which should be selected by default, if available. Separate multiple sources by a semicolon [default: '', example: 'core-router;backup-router']
	
	// [GeoLocation]
	$GEOLOCATION_DB="MaxMind"; // "IP2Location", "MaxMind" or "geoPlugin" [default: 'MaxMind']
	$MAXMIND_PATH="MaxMind/GeoLiteCity.dat"; // Will be ignored when $GEOLOCATION_DB is not set to "MaxMind" [default: 'MaxMind/GeoLiteCity.dat']
	$IP2LOCATION_PATH="IP2Location/IP-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE.BIN"; // Will be ignored when $GEOLOCATION_DB is not set to "IP2Location" [default: 'IP2Location/IP-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE.BIN']
	
	// [GeoCoding]
	$USE_GEOCODER_DB=1; // Indicates whether the geocoder cache database should be used - 0: no, 1: yes [default: 1]
	$GEOCODER_DB_SQLITE2="geocoder/geocoder_cache.sqlite"; // Path to the SQLite2 database file [default: 'geocoder/geocoder_cache.sqlite']
	$GEOCODER_DB_SQLITE3="geocoder/geocoder_cache.sqlite3"; // Path to the SQLite3 database file [default: 'geocoder/geocoder_cache.sqlite3']
	$WRITE_DATA_TO_GEOCODER_DB=1; // Indicates whether geocoded locations should be written to geocoder cache database - 0: no, 1: yes [default: 1]
	
	// [Internal traffic]
	$INTERNAL_DOMAINS="192.168/16;172.16/12;10.0/8"; // Use the NfSen filter subnet notation to indicate your internal domain (e.g. NATed) traffic. Multiple domains must be separated by a semicolon [example: '192.168/16;172.16/12;10.0/8']
	$INTERNAL_DOMAINS_COUNTRY="NETHERLANDS"; // Indicates the country in which a NATed network relies. If left empty, matching flow records will be ignored. Consult the ConfigurationChecker for more information.
	$INTERNAL_DOMAINS_REGION="OVERIJSSEL"; // Indicates the region in which a NATed network relies. Leave this setting empty, if unknown. Consult the ConfigurationChecker for more information.
	$INTERNAL_DOMAINS_CITY="ENSCHEDE"; // Indicates the city in which a NATed network relies. Leave this setting empty, if unknown. Consult the ConfigurationChecker for more information.
	$HIDE_INTERNAL_DOMAIN_TRAFFIC=1; // Indicates whether your internal domain traffic should be visualized in SURFmap - 0: no, 1: yes [default: 1]
	$IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION=1; // Indicates whether traffic 'inside' a marker (e.g., inside a country, region or city) should be ignored in the line color classification process - 0: no, 1: yes [default: 1]

	// [Demo Mode]
	$DEMO_MODE=0; // Enables or disables SURFmap's demo mode - 0: no, 1: yes [default: 0]
	$DEMO_MODE_PAGE_TITLE="Current network traffic across the world"; // Title to be displayed in demo mode

?>