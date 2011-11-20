<?php
	/*******************************
	 * objects.php [SURFmap]
	 * Author: Rick Hofstede
	 * University of Twente, The Netherlands
	 *******************************/
	
	class NetFlowFlow {
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

?>