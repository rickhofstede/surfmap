<?php
/******************************************************
 # getgeolocationdata.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/

    require_once("../config.php");
    require_once("../lib/MaxMind/geoipcity.inc");
    require_once("../lib/IP2Location/ip2location.class.php");
    header("content-type: application/json");

    if (isset($_POST['params'])) {
        $request = $_POST['params'];
    } else {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }

    $result = array();
    $result['geolocation_data'] = array();

    foreach ($config['internal_domains'] as $key => $value) {
    	$internalDomainNets = explode(";", $key);
				
    	foreach ($request as $address) {
    		/*
    		 * Check whether a NATed setup was used. If so, use the geolocation data provided
    		 * in the configuration file. Otherwise, use a geolocation service.
    		 */
    		$internal_address = false;
				
    		foreach ($internalDomainNets as $subNet) {
    			if (ip_address_belongs_to_net($address, $subNet)) {
    				$internal_address = true;
    				break;
    			}
    		}
    		unset($subNet);
				
    		if ($internal_address) { // NATed setup is used
                $country = ($value["country"] === "") ? "(UNKNOWN)" : strtoupper($value["country"]);
                $region = ($value["region"] === "") ? "(UNKNOWN)" : strtoupper($value["region"]);
                $city = ($value["city"] === "") ? "(UNKNOWN)" : strtoupper($value["city"]);
    		} else if ($config['geolocation_db'] == "IP2Location") {
    			$GEO_database = new ip2location();
    			$GEO_database->open("../".$config['ip2location_path']);
    			$data = $GEO_database->getAll($address);
            
                $country = ($data->countryLong === "-") ? "(UNKNOWN)" : $data->countryLong;
                $region = ($data->region === "-") ? "(UNKNOWN)" : $data->region;
                $city = ($data->city === "-") ? "(UNKNOWN)" : $data->city;
    		} else if ($config['geolocation_db'] == "MaxMind") {
    			$GEO_database = geoip_open("../".$config['maxmind_path'], GEOIP_STANDARD);
    			$data = geoip_record_by_addr($GEO_database, $address);
            
                $country = (!isset($data->country_name) || $data->country_name === "-") ? "(UNKNOWN)" : strtoupper($data->country_name);
                $region = (!isset($data->country_code) || !isset($data->region) || 
                        !array_key_exists($data->country_code, $GEOIP_REGION_NAME) || 
                        !array_key_exists($data->region, $GEOIP_REGION_NAME[$data->country_code]) || 
                        $GEOIP_REGION_NAME[$data->country_code][$data->region] === "") ? "(UNKNOWN)" : strtoupper($GEOIP_REGION_NAME[$data->country_code][$data->region]);
                $city = (!isset($data->city) || $data->city === "-") ? "(UNKNOWN)" : strtoupper($data->city);
    		} else {
    			$country = "";
    			$region = "";
    			$city = "";
    		}
        
    		$country = fix_comma_separated_name(replace_accented_characters(utf8_encode($country)));
    		$region = fix_comma_separated_name(replace_accented_characters(utf8_encode($region)));
            $city = fix_comma_separated_name(replace_accented_characters(utf8_encode($city)));
        
            array_push($result['geolocation_data'], array("address" => $address, "country" => $country, "region" => $region, "city" => $city));
					
    		// Reset variables for next iteration
    		unset($country, $region, $city);
    	}
        unset($address);
    }
    unset($key, $value);

    $result['status'] = 0;
    echo json_encode($result);
    die();
    
    function fix_comma_separated_name ($name) {
        $result = $name;
    	$comma_position = strpos($name, ",");
    
    	if ($comma_position !== false) {
    		$result = substr($name, $comma_position + 2); // +2 to remove trailing white space
    		$result .= " ".substr($name, 0, $comma_position);
    	}
    
        return $result;
    }

    function replace_accented_characters ($name) {
        $search = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
            
        $replace = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
    
        return strtoupper(str_replace($search, $replace, $name));
    }

    /*
     * Checks whether the specified IPv4 address belongs to the specified IP
     * address range (net).
     * Parameters:
     *		ipAddress - IPv4 address in octet notation (e.g. '192.168.1.1')
     * 		ipNet - IPv4 subnet range, in nfdump filter notation
     */
    function ip_address_belongs_to_net ($ipAddress, $ipNet) {
    	if (substr_count($ipAddress, ".") != 3) return false; // A valid IPv4 address should have 3 dots
    	if (substr_count($ipAddress, ".") < 1 && substr_count($ipAddress, "/") != 1) return false; // A valid IPv4 subNet should have at least 1 dot and exactly 1 slash
		
    	$ipAddressOctets = explode(".", $ipAddress);		
    	$ipAddressDec = ($ipAddressOctets[0] << 24) + ($ipAddressOctets[1] << 16) + ($ipAddressOctets[2] << 8) + $ipAddressOctets[3];
		
    	$netMask = intval(substr($ipNet, strpos($ipNet, "/") + 1));
		
    	// Since we use nfdump subnet notation, we need to make the subnet address complete
    	$completeIPNet = substr($ipNet, 0, strpos($ipNet, "/"));
    	for ($i = 3 - substr_count($ipNet, "."); $i > 0; $i--) {
    		$completeIPNet .= ".0";
    	}

    	$ipNetOctets = explode(".", $completeIPNet);
    	$ipNetDec = ($ipNetOctets[0] << 24) + ($ipNetOctets[1] << 16) + ($ipNetOctets[2] << 8) + $ipNetOctets[3];

    	return ($ipAddressDec & (-1 << (32 - $netMask))) == $ipNetDec;
    }

?>