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
     *             1000000 -> 1 M
                   1000 -> 1 k
     * Parameters:
     *      number - the amount of packets/octets,flows etc. that needs to be converted
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
    *       selector1 - ID of the source date/time selector
    *       selector2 - ID of the destination date/time selector
    */          
    function copy_date_time_selector (selector1, selector2) {
        var date = new Date($('#' + selector1).datetimepicker('getDate'));
        
        // Workaround for date/time picker copying, as described here: https://github.com/trentrichardson/jQuery-Timepicker-Addon/issues/280
        for (var i = 0; i < 2; i++) {
            $('#' + selector2).datetimepicker('setDate', date);
        }
        
        /* The code below is similar to the code executed by the 'close' handler of the date/time pickers */
        
        var selector_date, selector_hours, selector_minutes;
        if (selector2 == 'date_start') {
            selector_date = "date1";
            selector_hours = "hours1";
            selector_minutes = "minutes1";
        } else {
            selector_date = "date2";
            selector_hours = "hours2";
            selector_minutes = "minutes2";
        }
        
        // We assume the date/time of selector2 to have changed (so no need to check for change)
        var date_string = date.getFullYear().toString();
        if (date.getMonth() + 1 < 10) {
            date_string += "0";
        }
        date_string += (date.getMonth() + 1).toString();
        if (date.getDate() < 10) {
            date_string += "0";
        }
        date_string += date.getDate().toString();
        
        var hours_string = (date.getHours() < 10) ? "0" : "";
        hours_string += date.getHours().toString();
        
        var minutes_string = (date.getMinutes() < 10) ? "0" : "";
        minutes_string += date.getMinutes().toString();
        
        var obj = {};
        obj[selector_date] = date_string;
        obj[selector_hours] = hours_string;
        obj[selector_minutes] = minutes_string;
        $(document).trigger('session_data_changed', obj);
    }
    
    /*
     * Converts a specified name to a proper display format.
     * For example: UNITED STATES -> United States
     *             ZUID-HOLLAND -> Zuid-Holland
     * Parameters:
     *      name - name that has to be converted
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
     *      throughput - the initial throughput, which needs to be converted
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
     * Retrieves the specified key-value-pair of the specified cookie.
     * Parameters:
     *      cookie_name - Name of the cookie
     *      key - key of the key-value pair that needs to be retrieved
     */ 
    function get_cookie_value (cookie_name, key) {
        var cookie = $.cookie(cookie_name);
        return (cookie == undefined || cookie[key] == undefined) ? undefined : cookie[key];
    }
    
    /*
     * Adds the specified key-value-pair to the specified cookie. If either the
     * cookie or the key does not exist yet, they are created.
     * Parameters:
     *      cookie_name - Name of the cookie
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
