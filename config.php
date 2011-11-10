<?php
	/*******************************
	 * config.php [SURFmap]
	 * Author: Rick Hofstede
	 * University of Twente, The Netherlands
	 *******************************/
	
	// [Application parameters]
	$MAP_CENTER="52.217,6.9"; // Center of the map, specified by latitude and longitude coordinates; coordinates should be separated by a comma (,) [default: '52.217,6.9']
	$RELATIVE_MAP_WIDTH="97%"; // Relative map size compared to SURFmap iFrame [default: '97%']
	$RELATIVE_MAP_HEIGHT="95%"; // Relative map size compared to SURFmap iFrame [default: '95%']
	$DEFAULT_FLOW_RECORD_COUNT=-1; // Default amount of flow records to be selected - '-1': let SURFmap decide depending on the selected geolocation service [default: -1]
	$DEFAULT_QUERY_TYPE=1; // Default NfSen option - 0: Flow listing, 1: Stat TopN
	$DEFAULT_QUERY_TYPE_STAT_ORDER="bytes"; // Indicates the field on which statistics should be based, 'flows', 'packets' or 'bytes' [default: 'bytes']
	$LOG_DEBUG=0; // If enabled, debug logging is printed to the log file [default: 0]
	$LOG_ERRORS_ONLY=0; // If enabled, only log messages of type 'ERROR' are written to the log file [default: 0]
	$AUTO_OPEN_MENU=0; // If enabled, the settings menu will open automatically when SURFmap is loaded [default: 0]
	$FORCE_HTTPS=1; // If enabled, HTTPS will be used to contact Google for retrieving the maps [default: 1]
	$SHOW_WARNING_ON_FILE_ERROR=1; // If enabled, a warning message is shown in the user interface to inform about the issue [default: 1]
	$SORT_FLOWS_BY_START_TIME=0; // Sorts flows by start time - 0: no, 1: yes [default: 0]
	
	// [NfSen]
	$COMMSOCKET="/[path]/nfsen.comm"; // Path to NfSen communication socket [example: '/opt/var/run/nfsen.comm']
	$NFSEN_PROFILE="live"; // NfSen's default profile [default: 'live']
	$NFSEN_PRIMARY_SRC_SELECTOR="router_name"; // Primary/default NfSen source. Make sure to add only one source here! Additional sources can be added below [example: 'institution_name' or 'router_name']
	$NFSEN_ADDITIONAL_SRC_SELECTORS=""; // Additional NfSen sources [example: 'institution_name' or ''].  Multiple sources must be separated by a semicolon [example: 'institution_1;institution2']
	$NFSEN_SOURCE_DIR="/data/nfsen/profiles-data/$NFSEN_PROFILE/$NFSEN_PRIMARY_SRC_SELECTOR/"; // Path to NetFlow data files [example: '/data/nfsen/profiles-data/$NFSEN_PROFILE/$NFSEN_PRIMARY_SRC_SELECTOR/']
	$NFSEN_SOURCE_FILE_NAMING = "[yyyy]/[MM]/[dd]/nfcapd.[yyyy][MM][dd][hh][mm]"; // Source file naming. Examples: 'nfcapd.[yyyy][MM][dd][hh][mm]' (to represent 'nfcapd.201010111540' for instance), or '[yyyy]/[MM]/[dd]/nfcapd.[yyyy][MM][dd][hh][mm]' (to represent 2010/10/23/10/nfcapd.201010231005 for instance)
	$NFSEN_OUTPUT=0; // Prints the raw result of the query for debugging - 0: no, 1: yes [default: 1]
	
	// [GeoLocation]
	$GEOLOCATION_DB="IP2Location"; // "IP2Location", "MaxMind" or "geoPlugin" [default: 'MaxMind']
	$IP2LOCATION_PATH="/[path]/SURFmap/IP2Location/IP-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE.BIN"; // Will be ignored when $GEOLOCATION_DB is not set to "IP2Location"
	$MAXMIND_PATH="/[path]/SURFmap/MaxMind/GeoLiteCity.dat"; // Will be ignored when $GEOLOCATION_DB is not set to "MaxMind"
	
	// [GeoCoding]
	$USE_GEOCODER_DB=1; // Indicates whether the geocoder cache database should be used - 0: no, 1: yes [default: 1]
	$GEOCODER_DB_SQLITE2="geocoder/geocoder_cache.sqlite"; // Path to the SQLite2 database file [default: 'geocoder/geocoder_cache.sqlite']
	$GEOCODER_DB_SQLITE3="geocoder/geocoder_cache.sqlite3"; // Path to the SQLite3 database file [default: 'geocoder/geocoder_cache.sqlite3']
	$WRITE_DATA_TO_GEOCODER_DB=1; // Indicates whether geocoded locations should be written to geocoder cache database - 0: no, 1: yes [default: 1]
	
	// [Internal traffic]
	$INTERNAL_DOMAINS="192.168/16;172.16/12;10.0/8"; // Use the NfSen filter subnet notation to indicate your internal domain (e.g. NATed) traffic. Multiple domains must be separated by a semicolon [example: '192.168/16;172.16/12;10.0/8']
	$INTERNAL_DOMAINS_COUNTRY="NETHERLANDS"; // Indicates the country in which a NATed network relies. If left empty, the corresponding flow record will be ignored.
	$INTERNAL_DOMAINS_REGION="OVERIJSSEL"; // Indicates the region in which a NATed network relies. Leave this setting empty, if unknown.
	$INTERNAL_DOMAINS_CITY="ENSCHEDE"; // Indicates the city in which a NATed network relies. Leave this setting empty, if unknown.
	$HIDE_INTERNAL_DOMAIN_TRAFFIC=1; // Indicates whether your internal domain traffic should be visualized in SURFmap - 0: no, 1: yes [default: 1]
	$IGNORE_MARKER_INTERNAL_TRAFFIC_IN_LINE_COLOR_CLASSIFICATION=1; // Indicates whether traffic 'inside' a marker (e.g., inside a country, region or city) should be ignored in the line color classification process - 0: no, 1: yes [default: 1]

	// [Demo Mode]
	$DEMO_MODE=0; // Enables or disables SURFmap's demo mode - 0: no, 1: yes [default: 0]
	$DEMO_MODE_DEFAULT_ZOOM_LEVEL=0; // Default SURFmap zoom level in demo mode (i.e. Country (0), Region (1), City (2), Host(3)) [default: 0]
	$DEMO_MODE_QUERY_TYPE_LIST_ENTRY_COUNT=200; // Amount of NetFlow records to be used in demo mode with NfSen's 'Flow listing' option [default: 200]
	$DEMO_MODE_QUERY_TYPE_STAT_ENTRY_COUNT=100; // Amount of NetFlow records to be used in demo mode with NfSen's 'Stat TopN' option [default: 100]
	$DEMO_MODE_PAGE_TITLE="Current network traffic across the world"; // Title to be displayed in demo mode

?>