/*******************************
 * objects.js [SURFmap]
 * Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 * University of Twente, The Netherlands
 *
 * LICENSE TERMS: BSD-license.html
 *******************************/

   /**
	* Represents a single flow record, including geolocation information (of the flow end points).
	* Please keep in mind that this flow record can consist of multiple flows, in the case of
	* NfSen's StatTopN option, for instance.
    */			
	function FlowRecord(srcIP, srcPort, dstIP, dstPort, protocol) {
		this.srcIP = srcIP;
		this.srcPort = srcPort;
		this.dstIP = dstIP;
		this.dstPort = dstPort;
		this.protocol = protocol;
		this.packets = -1;
		this.octets = -1;
		this.duration = -1;
		this.srcCountry = "";
		this.srcCountryLat = "";
		this.srcCountryLng = "";
		this.dstCountry = "";
		this.dstCountryLat = "";
		this.dstCountryLng = "";
		this.srcRegion = "";
		this.srcRegionLat = "";
		this.srcRegionLng = "";
		this.dstRegion = "";
		this.dstRegionLat = "";
		this.dstRegionLng = "";
		this.srcCity = "";
		this.srcCityLat = "";
		this.srcCityLng = "";
		this.dstCity = "";
		this.dstCityLat = "";
		this.dstCityLng = "";
		this.flows = 1;
	}

	function LineProperties(lat1, lng1, lat2, lng2) {
		this.lat1 = lat1;
		this.lng1 = lng1;
		this.lat2 = lat2;
		this.lng2 = lng2;
		this.lineRecords = new Array();
	}
	
	function LineRecord(srcName, dstName) {
		this.srcName = srcName;
		this.dstName = dstName;
		this.packets = -1;
		this.octets = -1;
		this.duration = -1;
		this.throughput = -1;
		this.flows = 1;
		this.flowRecordIDs = new Array();
		
		this.srcParentCountryName = "";
		this.dstParentCountryName = "";		
		this.srcParentRegionName = "";
		this.dstParentRegionName = "";
		this.srcParentCityName = "";
		this.dstParentCityName = "";
	}
	
	function MarkerProperties(lat, lng) {
		this.lat = lat;
		this.lng = lng;
		this.markerRecords = new Array();
	}
	
	function MarkerRecord(name) {
		this.name = name;
		this.hosts = 1;
		this.flowRecordIDs = new Array();
		
		// Host zoom level
		this.IP = "";
		this.flows = 1;
		this.protocol = "";
		this.port = -1;
		this.countryName = "";
		this.regionName = "";
		this.cityName = "";
	}

	function GeocodedPlace(place, lat, lng) {
		this.place = place;
		this.lat = lat;
		this.lng = lng;
	}
	
	function SessionData(type, value) {
		this.type = type;
		this.value = value;
	}
	
	function StatData(type, value) {
		this.type = type;
		this.value = value;
	}
	