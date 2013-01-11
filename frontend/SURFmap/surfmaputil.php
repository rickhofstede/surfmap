<?php
	/*******************************
	 # surfmaputil.php [SURFmap]
	 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
	 # University of Twente, The Netherlands
	 #
	 # LICENSE TERMS: outlined in BSD-license.html
	 *******************************/

	function stringifyNetFlowData($data, $type) {
		global $sessionData;

		$delimiter = "__";
		$subDelimiter = "_"; 
		$result = "";

		if ($type == "IP") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $data[$i]->ipv4_src.$subDelimiter.$data[$i]->ipv4_dst;

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}
		} else if ($type == "PACKETS") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $data[$i]->packets;

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}
		} else if ($type == "OCTETS") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $data[$i]->octets;

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}
		} else if ($type == "DURATION") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $data[$i]->duration;

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}
		} else if ($type == "PROTOCOL") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $data[$i]->protocol;

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}	
		} else if ($type == "PORT") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $data[$i]->port_src.$subDelimiter.$data[$i]->port_dst;

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}
		} else if ($type == "FLOWS") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $data[$i]->flows;

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}																
		} else {
			return false;
		}

		return $result;
	}

	function stringifyGeoData($data, $type) {
		global $sessionData;

		$delimiter = "__";
		$subDelimiter = "_"; 
		$result = "";

		if ($type == "COUNTRY") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $data[$i][0]['COUNTRY'].$subDelimiter.$data[$i][1]['COUNTRY'];

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}
		} else if ($type == "REGION") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $data[$i][0]['REGION'].$subDelimiter.$data[$i][1]['REGION'];

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}
		} else if ($type == "CITY") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $data[$i][0]['CITY'].$subDelimiter.$data[$i][1]['CITY'];

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}							
		} else {
			return false;
		}

		return $result;
	}

	function stringifyGeoCoderData($type) {
		global $sessionData;

		$delimiter = "___";
		$subDelimiter = "__";
		$subSubDelimiter = "_"; 
		$result = "";

		if ($type == "COUNTRY") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $sessionData->geoCoderData[$i]->srcCountry[0].$subSubDelimiter
						.$sessionData->geoCoderData[$i]->srcCountry[1].$subDelimiter
						.$sessionData->geoCoderData[$i]->dstCountry[0].$subSubDelimiter
						.$sessionData->geoCoderData[$i]->dstCountry[1];

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}
		} else if ($type == "REGION") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {				
				$result .= $sessionData->geoCoderData[$i]->srcRegion[0].$subSubDelimiter
						.$sessionData->geoCoderData[$i]->srcRegion[1].$subDelimiter
						.$sessionData->geoCoderData[$i]->dstRegion[0].$subSubDelimiter
						.$sessionData->geoCoderData[$i]->dstRegion[1];

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}
		} else if ($type == "CITY") {
			for ($i = 0; $i < $sessionData->flowRecordCount; $i++) {
				$result .= $sessionData->geoCoderData[$i]->srcCity[0].$subSubDelimiter
						.$sessionData->geoCoderData[$i]->srcCity[1].$subDelimiter.
						$sessionData->geoCoderData[$i]->dstCity[0].$subSubDelimiter.
						$sessionData->geoCoderData[$i]->dstCity[1];

				if ($i !== ($sessionData->flowRecordCount - 1)) $result .= $delimiter;
			}
		} else {
			return false;
		}

		return $result;
	}
    
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
	
?>
