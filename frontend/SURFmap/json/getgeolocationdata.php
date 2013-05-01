<?php
/******************************************************
 # getgeolocationdata.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/

    require_once("../config.php");
    require_once("../util.php");
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

?>