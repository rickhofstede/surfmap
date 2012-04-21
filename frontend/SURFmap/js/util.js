/******************************
 # util.js [SURFmap]
 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *******************************/
	
/*
 * This function converts a created string of database records, based on delimiters, to an array.
 * Parameters:
 * 		string - the that has to be converted.
 *		type - type of the contents of the string.
 *		recordCount - Amount of actual flow records (which is smaller than or equal to 'entryCount')
 */
function stringToArray (string, type, recordCount) {
	var result_array, temp_array;
		
	if (recordCount == 0) {
		result_array = new Array(1);
	} else {
		if (type == "IP" || type == "PORT" || type == "COUNTRY" || type == "REGION" || type == "CITY" || type == "LATITUDE" || type == "LONGITUDE") {
			temp_array = string.split("__");
			result_array = new Array(temp_array.length);

			for (var i = 0; i < temp_array.length; i++) {
				result_array[i] = temp_array[i].split("_");
			}
		} else if (type == "GeoCoder_COUNTRY" || type == "GeoCoder_REGION" || type == "GeoCoder_CITY") {
			var flows_array = string.split("___");
			var source_destination_array = new Array(flows_array.length);

			for (var i = 0; i < flows_array.length; i++) {
				source_destination_array[i] = flows_array[i].split("__");
			}

			var lat_lng_array = new Array(flows_array.length);
			for (var i = 0; i < lat_lng_array.length; i++) {
				lat_lng_array[i] = new Array(2);
			}

			for (var i = 0; i < source_destination_array.length; i++) {
				for (var j = 0; j < 2; j++) {
					lat_lng_array[i][j] = source_destination_array[i][j].split("_");
				}
			}

			result_array = lat_lng_array;
		} else if (type == "PACKETS" || type == "OCTETS" || type == "DURATION" || type == "PROTOCOL" || type == "FLOWS") {
			result_array = string.split("__");
		} else {
			alert("Type error in stringToArray()! Type: " + type);
		}
	}

	return result_array;
}	

/*
 * This function converts a provided name to a proper display format.
 * For example: UNITED STATES -> United States
 *			   ZUID-HOLLAND -> Zuid-Holland
 * Parameters:
 *		name - the name that needs to be converted
 */			
function formatName (name) {
	var name = name.toLowerCase();
	var result = "";

	if (name == "-" || name == "" || name == " " || name.indexOf("nknown") != -1) {
		result = "Not available";
	} else {
		for (var i = 0; i < name.length; i++) {
			if (i == 0) {
				result += name.charAt(i).toUpperCase();
			} else if (name.charAt(i - 1) == "-" || name.charAt(i - 1) == " " || name.charAt(i - 1) == "(") {
				result += name.charAt(i).toUpperCase();
			} else {
				result += name.charAt(i);
			}
		}
	}

	return result;
}
	
/*
 * Applies the SI scale to the provided number (not String!).
 * For example: 1000000000 -> 1 G
 *			   1000000 -> 1 M
			   1000 -> 1 k
 * Parameters:
 *		number - the amount of packets/octets,flows etc. that needs to be converted
 */	
function applySIScale (number) {
	var newNumber;
		
	if ((number / 1000000000) > 1) {
		newNumber = (number / 1000000000).toFixed(1) + " G";
	} else if ((number / 1000000) > 1) {
		newNumber = (number / 1000000).toFixed(1) + " M";
	} else if ((number / 1000) > 1) {
		newNumber = (number / 1000).toFixed(1) + " k";
	} else {
		newNumber = number;
	}
		
	return newNumber;
}

/*
 * This function converts a provided throughput amount to a proper display format.
 * Parameters:
 *		throughput - the initial throughput, which needs to be converted
 */	
function formatThroughput (throughput) {
	var formattedThroughput;
		
	if (throughput == "N/A") {
		return throughput;
	} else if ((throughput / 1000000000) > 1) {
		formattedThroughput = (throughput / 1000000000).toFixed(1) + " GBps";
	} else if ((throughput / 1000000) > 1) {
		formattedThroughput = (throughput / 1000000).toFixed(1) + " MBps";
	} else if ((throughput / 1000) > 1) {
		formattedThroughput = (throughput / 1000).toFixed(1) + " kBps";
	} else {
		formattedThroughput = throughput + " Bps";
	}
		
	return formattedThroughput;
}
