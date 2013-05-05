<?php
    /*******************************
     # retrievelocation.php [SURFmap]
     # Author: Rick Hofstede
     # University of Twente, The Netherlands
     #
     # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
     *******************************/
	
    require_once("../config.php");
    require_once("../util.php");
    require_once("../lib/MaxMind/geoipcity.inc");
    require_once("../lib/IP2Location/ip2location.class.php");
	
	// Retrieve External IP address and location
	$ext_IP = (!getenv("SERVER_ADDR")) ? "127.0.0.1" : getenv("SERVER_ADDR");
	if ($ext_IP == "127.0.0.1") {
		$ext_IP_NAT = true;
	} else {
		$ext_IP_NAT = false;
		
		foreach ($config['internal_domains'] as $key => $value) {
			$internal_domain_nets = explode(";", $key);
			
			foreach($internal_domain_nets as $subnet) {
				if (ip_address_belongs_to_net($ext_IP, $subnet)) {
					$ext_IP_NAT = true;
					break;
				}
			}
            unset($subnet);
		}
        unset($key, $value);
	}
	
	/*
	 * If the found (external) IP address of the server is the localhost
	 * address or a NATed address, try do find it using external resources.
	 */
	if ($ext_IP_NAT) {
		$NAT_IP = $ext_IP;
		try {
			if (extension_loaded("curl")) {
				for ($i = 0; $i < 6; $i++) {
					$curl_handle = curl_init();
					
					if ($i < 3) {
						curl_setopt($curl_handle, CURLOPT_URL, "http://surfmap.sourceforge.net/get_ext_ip.php");
					} else {
						curl_setopt($curl_handle, CURLOPT_URL, "http://whatismyip.org/"); 
					}

					curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
					
					if ($config['use_proxy']) {
						curl_setopt($curl_handle, CURLOPT_PROXYTYPE, 'HTTP');
						curl_setopt($curl_handle, CURLOPT_PROXY, $config['proxy_ip']);
						curl_setopt($curl_handle, CURLOPT_PROXYPORT, $config['proxy_port']);
					
						if ($config['proxy_user_authentication']) {
							curl_setopt($curl_handle, CURLOPT_PROXYUSERPWD, $config['proxy_username'].":".$config['proxy_password']);
						}
					}
					
					$ext_IP = curl_exec($curl_handle);
					curl_close($curl_handle);

					if (substr_count($ext_IP, ".") == 3) {
                        if ($ext_IP == $NAT_IP) $ext_IP_NAT = false;
                        break;
					}
					
                    sleep(1);
				}
			}

			/*
			 * If 'substr_count($extIP, ".") != 3' it means that it was not an IP address that was downloaded,
             * which can be the case when whatismyip.org spawns an error message.
			 */
			if (substr_count($ext_IP, ".") != 3  || !extension_loaded("curl")) {
				$ext_IP = $NAT_IP;
				$ext_IP_error = "Unable to retrieve external IP address";
			}
		} catch (Exception $e) {}
	}
	
	if ($config['geolocation_db'] == "IP2Location") {
		$GEO_database = new ip2location();
		$GEO_database->open("../".$config['ip2location_path']);
		$data = $GEO_database->getAll($ext_IP);
		
		$ext_IP_country = $data->countryLong;
		if ($ext_IP_country == "-") $ext_IP_country = "(UNKNOWN)";
		
		$ext_IP_region = $data->region;
		if ($ext_IP_region == "-") $ext_IP_region = "(UNKNOWN)";
		
		$ext_IP_city = $data->city;
		if ($ext_IP_city == "-") $ext_IP_city = "(UNKNOWN)";
	} else if ($config['geolocation_db'] == "MaxMind") {
		$GEO_database = geoip_open("../".$config['maxmind_path'], GEOIP_STANDARD);
		$data = geoip_record_by_addr($GEO_database, $ext_IP);
		
		if (isset($data->country_name)) {
			$ext_IP_country = strtoupper($data->country_name);
		}
		if (!isset($ext_IP_country) || $ext_IP_country == "") $ext_IP_country = "(UNKNOWN)";

		if (isset($data->country_code) && isset($data->region)
				&& array_key_exists($data->country_code, $GEOIP_REGION_NAME)
				&& array_key_exists($data->region, $GEOIP_REGION_NAME[$data->country_code])) {
			$ext_IP_region = strtoupper($GEOIP_REGION_NAME[$data->country_code][$data->region]);
		}
		if (!isset($ext_IP_region) || $ext_IP_region == "") $ext_IP_region = "(UNKNOWN)";

		if (isset($data->city)) {
			$ext_IP_city = strtoupper($data->city);
		}
		if (!isset($ext_IP_city) || $ext_IP_city == "") $ext_IP_city = "(UNKNOWN)";
	} else {
		$ext_IP_country = "(UNKNOWN)";
		$ext_IP_region = "(UNKNOWN)";
		$ext_IP_city = "(UNKNOWN)";
	}
	
	$ext_IP_country = replace_accented_characters($ext_IP_country);
	$ext_IP_region = replace_accented_characters($ext_IP_region);
	$ext_IP_city = replace_accented_characters($ext_IP_city);
	
	if ($ext_IP_city != "(UNKNOWN)") {
		$lat_lng = geocode($ext_IP_city);
	} else if ($ext_IP_region != "(UNKNOWN)") {
		$lat_lng = geocode($ext_IP_region);
	} else if ($ext_IP_country != "(UNKNOWN)") {
		$lat_lng = geocode($ext_IP_country);
	}
	
	$location = $ext_IP_country.",".$ext_IP_region.",".$ext_IP_city;
	if (isset($lat_lng) && is_array($lat_lng)) {
		$location .= ",".$lat_lng[0].",".$lat_lng[1];
	} else {
		$location .= ",(UNKNOWN),(UNKNOWN)";
	}
	
	/**
	 * Starts calls to the Google Maps API GeoCoder. It is derived from the 'geocode()'
	 * method in [index.php].
	 * Return:
	 *		array(lat, lng) on success, or 'false' (bool) on failure
	 */	
	function geocode($place) {
		global $FORCE_HTTPS;
		
		$requestURL = "https://maps.google.com/maps/api/geocode/xml?address=" . urlencode($place) ."&sensor=false";
		
		// Prefer cURL over the 'simplexml_load_file' command, for increased stability
		if (extension_loaded("curl")) {
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $requestURL);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
			$result = curl_exec($curl_handle);
			curl_close($curl_handle);
			$xml = simplexml_load_string($result);
		} else {
			$xml = simplexml_load_file($requestURL);
		}
		
		$status = $xml->status;
		if (isset($xml->result->geometry)) {
			$lat = $xml->result->geometry->location->lat;
		    $lng = $xml->result->geometry->location->lng;
		}
		
		if ($status == "OVER_QUERY_LIMIT") {
			time_nanosleep(0, 1000000000);
			geocode($place);
		}
		
		return ($status == "OK" && isset($lat) && isset($lng)) ? array($lat, $lng) : false;
	}

?>

<!DOCTYPE html>
<html>
	<head>
		<title>SURFmap / Retrieve Location</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	</head>
	<body>
		<h1>SURFmap / Retrieve Location</h1>
			
		<div id="setup_guidelines">You can use the following settings in config.php:<br /><br /></div>		
		<div id="config_data" style="display:none;"><?php echo $location; ?></div>
		
		<script type="text/javascript">
			var ext_IP_NAT = <?php if ($ext_IP_NAT) echo "1"; else echo "0"; ?>;
			var ext_IP_error = "<?php if (isset($ext_IP_error)) echo $ext_IP_error; ?>";
			var ext_IP_country = "<?php echo $ext_IP_country; ?>";
			var ext_IP_region = "<?php echo $ext_IP_region; ?>";
			var ext_IP_city = "<?php echo $ext_IP_city; ?>";
			var ext_IP_coordinates = "<?php if (isset($lat_lng) && is_array($lat_lng)) { echo $lat_lng[0].','.$lat_lng[1]; } ?>";
            var first_internal_domain = "<?php reset($config['internal_domains']); echo key($config['internal_domains']); ?>";

			// Setup guidelines
			if (ext_IP_coordinates != "") {
				document.getElementById("setup_guidelines").innerHTML += "$MAP_CENTER=\"" + ext_IP_coordinates + "\";<br /><br />";
			}
			
			if (ext_IP_NAT || ext_IP_error != "") {
				document.getElementById("setup_guidelines").style.display = "none";
			} else if (ext_IP_country != "(UNKNOWN)") {
				var region = (ext_IP_region == "(UNKNOWN)") ? "" : ext_IP_region;
				var city = (ext_IP_city == "(UNKNOWN)") ? "" : ext_IP_city;
				document.getElementById("setup_guidelines").innerHTML += "$config['internal_domains'] = array( <br />\
								<span style=\"padding-left: 50px;\">\"" + first_internal_domain + "\" => array(\"country\" => \"" + ext_IP_country + "\", \"region\" => \"" + region + "\", \"city\" => \"" + city + "\")</span><br /> \
						);"
			}
		</script>
	</body>
</html>