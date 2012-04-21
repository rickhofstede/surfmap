<?php
	/*******************************
	 # surfmaputil.php [SURFmap]
	 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
	 # University of Twente, The Netherlands
	 #
	 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
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
	
?>
