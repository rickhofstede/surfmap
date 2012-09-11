<?php
/******************************************************
 # getgeolocationdata.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/

require_once("../../config.php");
require_once("../../MaxMind/geoipcity.inc");
require_once("../../IP2Location/ip2location.class.php");
header("content-type: application/json");

/*
 * Checks whether the specified IPv4 address belongs to the specified IP
 * address range (net).
 * Parameters:
 *		ipAddress - IPv4 address in octet notation (e.g. '192.168.1.1')
 * 		ipNet - IPv4 subnet range, in nfdump filter notation
 */
function ipAddressBelongsToNet ($ipAddress, $ipNet) {
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

if (isset($_POST['request'])) {
    $request = $_POST['request'];
} else {
    $result['status'] = 1;
    echo json_encode($result);
    die();
}

global $GEOLOCATION_DB, $IP2LOCATION_PATH, $MAXMIND_PATH, $INTERNAL_DOMAINS, $GEOIP_REGION_NAME; // $GEOIP_REGION_NAME is part of the MaxMind API

$result = array();
$result['geolocation_data'] = array();

foreach ($INTERNAL_DOMAINS as $key => $value) {
	$internalDomainNets = explode(";", $key);
				
	foreach ($request as $address) {
		/*
		 * Check whether a NATed setup was used. If so, use the geolocation data provided
		 * in the configuration file. Otherwise, use a geolocation service.
		 */
		$internal_address = false;
				
		foreach ($internalDomainNets as $subNet) {
			if (ipAddressBelongsToNet($address, $subNet)) {
				$internal_address = true;
				break;
			}
		}
		unset($subNet);
				
		if ($internal_address === true) { // NATed setup is used
            $country = ($value["country"] === "") ? "(UNKNOWN)" : strtoupper($value["country"]);
            $region = ($value["region"] === "") ? "(UNKNOWN)" : strtoupper($value["region"]);
            $city = ($value["city"] === "") ? "(UNKNOWN)" : strtoupper($value["city"]);
		} else if ($GEOLOCATION_DB == "IP2Location") {
			$GEO_database = new ip2location();
			$GEO_database->open("../../".$IP2LOCATION_PATH);
			$data = $GEO_database->getAll($address);
            
            $country = ($data->countryLong === "-") ? "(UNKNOWN)" : $data->countryLong;
            $region = ($data->region === "-") ? "(UNKNOWN)" : $data->region;
            $city = ($data->city === "-") ? "(UNKNOWN)" : $data->city;
		} else if ($GEOLOCATION_DB == "MaxMind") {
			$GEO_database = geoip_open("../../".$MAXMIND_PATH, GEOIP_STANDARD);
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
        
		$country = utf8_encode($country);
		$region = utf8_encode($region);
        $city = utf8_encode($city);
        
        foreach (array($country, $region, $city) as $name) {
            // Replace accented characters
    		$search  = explode(",","Ç,Ḉ,Æ,Œ,Á,É,Í,Ó,Ú,À,È,Ì,Ò,Ù,Ä,Ë,Ï,Ö,Ü,Ÿ,Â,Ê,Î,Ô,Ȗ,Å,Ã,Ñ,Ø,Ý,Ț,Ů,Ž,Č,Ď,Ě,Ň,Ř,Š,ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,ø,ý,ã,ñ");
    		$replace = explode(",","C,C,AE,OE,A,E,I,O,U,A,E,I,O,U,A,E,I,O,U,Y,A,E,I,O,U,A,A,N,O,Y,T,U,Z,C,D,E,N,R,S,C,AE,OE,A,E,I,O,U,A,E,I,O,U,A,E,I,O,U,Y,A,E,I,O,U,A,O,Y,A,N");
        	str_replace($search, $replace, $name);
            
            // Fix comma-separated names
        	$comma_position = strpos($name, ",");
        	if ($comma_position !== false) {
        		$new_name = substr($name, $comma_position + 2); // +2 to remove trailing white space
        		$new_name .= " ".substr($name, 0, $comma_position);
        		$name = $new_name;
        	}
        }
        
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

?>