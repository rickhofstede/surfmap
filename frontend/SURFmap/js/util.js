/******************************
 # util.js [SURFmap]
 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *******************************/
		
	/*
	 * Applies the SI scale to the provided number (not String!).
	 * For example: 1000000000 -> 1 G
	 *			   1000000 -> 1 M
				   1000 -> 1 k
	 * Parameters:
	 *		number - the amount of packets/octets,flows etc. that needs to be converted
	 */	
	function apply_SI_Scale (number) {
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
	* Copies date/time from one jQuery date/time selector to another.
	* Parameters:
	*		selector1 - ID of the source date/time selector
	*		selector2 - ID of the destination date/time selector
	*/			
	function copy_date_time_selector (selector1, selector2) {
		$("#" + selector2).datetimepicker('setDate', new Date($("#" + selector1).datetimepicker('getDate')));
			
		// Workaround for date/time picker copying, as described here: https://github.com/trentrichardson/jQuery-Timepicker-Addon/issues/280
		var setDate = $("#" + selector2).datetimepicker('getDate');
		if (setDate.getHours() == 0 && setDate.getMinutes() == 0) {
			$("#" + selector2).datetimepicker('setDate', new Date($("#" + selector1).datetimepicker('getDate')));
		}
	}
    
	/*
	 * Converts a specified name to a proper display format.
	 * For example: UNITED STATES -> United States
	 *			   ZUID-HOLLAND -> Zuid-Holland
	 * Parameters:
	 *		name - name that has to be converted
	 */			
	function format_location_name (name) {
		var name = name.toLowerCase();
        
	     // Many geolocation databases use 'NETHERLANDS' instead of 'THE NETHERLANDS'
	     if (name == 'netherlands') name = 'the netherlands';
        
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
	 * This function converts a provided throughput amount to a proper display format.
	 * Parameters:
	 *		throughput - the initial throughput, which needs to be converted
	 */	
	function format_throughput (throughput) {
		var format;
		
		if (throughput == "N/A") {
			format = throughput;
		} else if ((throughput / 1000000000) > 1) {
			format = (throughput / 1000000000).toFixed(1) + " GBps";
		} else if ((throughput / 1000000) > 1) {
			format = (throughput / 1000000).toFixed(1) + " MBps";
		} else if ((throughput / 1000) > 1) {
			format = (throughput / 1000).toFixed(1) + " kBps";
		} else if (!isNaN(throughput)) {
			format = throughput.toFixed(1) + " Bps";
		} else {
			format = throughput;
		}
		
		return format;
	}
    
	/*
	 * Adds the specified key-value-pair to the specified cookie. If either the
     * cookie or the key does not exist yet, they are created.
	 * Parameters:
	 *		cookie_name - Name of the cookie
     *      key - key of the key-value pair that needs to be stored
     *      vale - value of the key-value pair that needs to be stored
	 */	
	function update_cookie_value (cookie_name, key, value) {
        var cookie = $.cookie(cookie_name);
        if (cookie == undefined) {
            var data = {};
            data[key] = value;
            $.cookie(cookie_name, data);
        } else { // Cookie exists
            cookie[key] = 1;
            $.cookie(cookie_name, cookie);
        }
	}
