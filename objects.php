<?php
/*******************************
 * objects.php [SURFmap]
 * Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 * University of Twente, The Netherlands
 *
 * LICENSE TERMS: outlined in BSD-license.html
 *******************************/
	
	class FlowRecord {
		var $ipv4_src;
		var $ipv4_dst;
		var $port_src;
		var $port_dst;
		var $protocol;
		var $packets;
		var $octets;
		var $duration;
		var $flows; // is not a NetFlow field, but used later on
	}

	class FlowCoordinates {
		// all variables consist of an array (size of 2, with latitude and longitude)
		var $srcCountry;
		var $srcRegion;
		var $srcCity;
		var $srcHost;
		
		var $dstCountry;
		var $dstRegion;
		var $dstCity;
		var $dstHost;
		
		function writeVariable($endPoint, $level, $value) {
			if($endPoint == 0) { // source
				switch($level) {
					case 0:	$this->srcCountry = $value;
							break;
					
					case 1: $this->srcRegion = $value;
							break;
							
					case 2:	$this->srcCity = $value;
							break;
							
					case 3:	$this->srcHost = $value;
							break;
							
					default:break;
				}
			} else { // destination
				switch($level) {
					case 0:	$this->dstCountry = $value;
							break;
					
					case 1: $this->dstRegion = $value;
							break;
							
					case 2:	$this->dstCity = $value;
							break;
							
					case 3:	$this->dstHost = $value;
							break;
							
					default:break;
				}
			}
		}
	}

	/*
	 * Stores session data that shouldn't be stored in the PHP session data
	 */
	class SessionData {
		var $flowRecordCount;
		var $query;
		var $latestDate;
		var $latestHour;
		var $latestMinute;
		var $originalDate1Window;
		var $originalTime1Window;
		var $originalDate2Window;
		var $originalTime2Window;
		
		var $NetFlowData;
		var $nfsenDisplayFilter; // Filter string without static filters
		var $firstNfSenSource = "";
		var $geoLocationData;
		var $geoCoderData;
		var $geocoderRequests = 0; // Geocoder request history for current day
		
		/*
		 * 	0: no error
		 *	1: filter error
		 *	2: invalid date/time window (selector 1)
		 *	3: invalid date/time window (selector 2)
		 *	4: invalid date/time window (selector 1+2)
		 *  5: no data error
		 *  6: profile error
		 */
		var $errorCode = 0;
		var $errorMessage = "";
	}

?>