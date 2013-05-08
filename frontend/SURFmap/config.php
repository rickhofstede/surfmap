<?php
    /*******************************
     # config.php [SURFmap]
     # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
     # University of Twente, The Netherlands
     #
     # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
     *******************************/
    
    /* [Application parameters] */
    $config['map_center'] = "52.217,6.9"; // Center of the map, specified by latitude and longitude coordinates; coordinates should be separated by a comma (,) [default: '52.217,6.9']
    $config['default_flow_record_count'] = 50; // Default number of flow records to be selected [default: 50]
    $config['default_query_type'] = 1; // Default NfSen option - 0: Flow listing, 1: Stat TopN [default: 1]
    $config['default_query_type_stat_order'] = 2; // Indicates the field on which statistics should be based - 0: flows, 1: packets, 2: bytes [default: 2]
    $config['default_zoom_level'] = 0; // Default SURFmap zoom level - 0: country, 1: region, 2: city, 3: host [default: 0]
    $config['log_debug'] = 0; // If enabled, debug logging is printed to the log file [default: 0]
    $config['auto_open_menu'] = 0; // If enabled, the settings menu will open automatically when SURFmap is loaded [default: 0]
    $config['resolve_hostnames'] = 1; // If enabled, hostnames will be resolved using DNS [default: 1]
    $config['show_warnings'] = 1; // If enabled, potential warnings are shown to the user [default: 1]
    $config['order_flow_records_by_start_time'] = 0; // Order flow records by their start time - 0: no, 1: yes [default: 0]
    
    /* [NfSen] */
    $config['nfsen_config'] = "/data/nfsen/etc/nfsen.conf"; // Path to NfSen configuration file [example: '/data/nfsen/etc/nfsen.conf']
    $config['nfsen_default_sources'] = ""; // NfSen sources which should be selected by default, if available. Separate multiple sources by a semicolon [default: '', example: 'core-router;backup-router']
    
    /* [GeoLocation] */
    $config['geolocation_db'] = "MaxMind"; // "IP2Location" or "MaxMind" [default: 'MaxMind']
    $config['maxmind_path'] = "lib/MaxMind/GeoLiteCity.dat"; // Will be ignored when $config['geolocation_db'] is not set to "MaxMind" [default: 'lib/MaxMind/GeoLiteCity.dat']
    $config['ip2location_path'] = "lib/IP2Location/IP-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE.BIN"; // Will be ignored when $GEOLOCATION_DB is not set to "IP2Location" [default: 'lib/IP2Location/IP-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE.BIN']
    
    /* [Internal traffic]
     * You can specify 'internal domains' here which force SURFmap to apply static geocoding. This is especially useful in NATed setups, where private IP addresses are used.
     * The default configuration can be extended by adding a new line to the $INTERNAL_DOMAINS array (Don't forget to add a comma after each line in the array, except for the last line!):
     *
     * "<domains>" => array("country" => "<country>", "region" => "<region>", "city" => "<city>")
     * 
     * Explanation of the fields:
     *      domains - NfSen filter subnet notation to indicate your internal domain (e.g. NATed) traffic. Multiple domains must be separated by a semicolon [example: '192.168/16;172.16/12;10.0/8']
     *      country - Indicates the country in which a NATed network relies. If left empty, matching flow records will be ignored.
     *      region - Indicates the region in which a NATed network relies. Leave this setting empty, if unknown..
     *      city - Indicates the city in which a NATed network relies. Leave this setting empty, if unknown..
     */
    $config['internal_domains'] = array(
            "192.168/16;172.16/12;10.0/8" => array("country" => "THE NETHERLANDS", "region" => "OVERIJSSEL", "city" => "ENSCHEDE")
    );
    $config['hide_internal_domain_traffic'] = 1; // Indicates whether your internal domain traffic should be visualized in SURFmap - 0: no, 1: yes [default: 1]
    $config['ignore_marker_internal_traffic_in_line_color_classification'] = 1; // Indicates whether traffic 'inside' a marker (e.g., inside a country, region or city) should be ignored in the line color classification process - 0: no, 1: yes [default: 1]

    /* [Demo Mode] */
    $config['demo_mode'] = 0; // Enables or disables SURFmap's demo mode - 0: no, 1: yes [default: 0]
    $config['demo_mode_page_title'] = "Current network traffic across the world"; // Title to be displayed in demo mode
    
    /* [Proxy] */
    $config['use_proxy'] = 0; // Only enable this setting if your Web server is behind a proxy [default: 0]
    $config['proxy_ip'] = "127.0.0.1"; // IP address of the proxy
    $config['proxy_port'] = 8080; // Port to connect to the proxy [default: 8080]
    $config['proxy_user_authentication'] = 0; // Enable this setting if your proxy requires authentication (username and password) [default: 0]
    $config['proxy_username'] = "username"; // Username to be used for proxy authentication
    $config['proxy_password'] = "password"; // Password to be used for proxy authentication
    
?>