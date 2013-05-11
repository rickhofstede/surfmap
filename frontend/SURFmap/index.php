<?php
    /******************************
     # index.php [SURFmap]
     # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
     # University of Twente, The Netherlands
     #
     # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
     *******************************/
     
     header("Content-type: text/html; charset=utf-8");
     require_once("config.php");
     require_once("constants.php");
     
     $version = "3.0b3 (20130511)";

     // Initialize session
     if (!isset($_SESSION['SURFmap'])) $_SESSION['SURFmap'] = array();
     
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>SURFmap -- A Network Monitoring Tool Based on the Google Maps API</title>
    <link type="text/css" rel="stylesheet" href="lib/jquery/css/start/jquery-ui-1.10.2.custom.min.css" />
    <link type="text/css" rel="stylesheet" href="css/surfmap.css" />
    <script type="text/javascript" src="https://maps.google.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="lib/jquery/js/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="lib/jquery/js/jquery-ui-1.10.2.custom.min.js"></script>
    <!-- <script src="https://code.jquery.com/jquery-migrate-1.2.0.js"></script> -->
    <script type="text/javascript" src="lib/jquery_browser/jquery.browser.js"></script> <!-- https://github.com/gabceb/jquery-browser-plugin -->
    <script type="text/javascript" src="lib/json2/json2.js"></script> <!-- https://github.com/carhartl/jquery-cookie -->
    <script type="text/javascript" src="lib/jquery_cookie/jquery.cookie.js"></script> <!-- https://github.com/carhartl/jquery-cookie -->
    <script type="text/javascript" src="lib/jquery_multiselect/jquery.multiselect.min.js"></script> <!-- http://www.erichynds.com/examples/jquery-ui-multiselect-widget/demos/ -->
    <script type="text/javascript" src="lib/jquery_timepicker/jquery-ui-timepicker-addon.js"></script> <!-- http://trentrichardson.com/examples/timepicker/ -->
    <script type="text/javascript" src="js/dialogs.js"></script>
    <script type="text/javascript" src="js/events.js"></script>
    <script type="text/javascript" src="js/maputil.js"></script>
    <script type="text/javascript" src="js/util.js"></script>
    <script type="text/javascript">
        var ajax_error = 0;
        
        // Enable JSON support in cookies
        $.cookie.json = true;
    
        var version = "<?php echo $version; ?>";
        var config;
        var constants;
        var extensions;
        var nfsen_config;
        var session_data;
        
        // Handles for Javascript setTimeout and setInterval
        var auto_refresh_handle;
        var loading_message_timeout_handle;
        var store_session_data_handle;
        
        // Variables containing raw data (from server)
        var flow_data;
        var geolocation_data;
        var geocoder_data_server;
        var geocoder_data_client;
        var resolved_hostnames;
        
        // Dialog queues
        var error_dialog_queue = [];
        var warning_dialog_queue = [];
        
        // Google Maps-related elements
        var lines;
        var markers;
        var map;
        var geocoder = new google.maps.Geocoder();
        var info_window = new google.maps.InfoWindow();
        var blue_marker = new google.maps.MarkerImage("images/markers/blue-dot.png", new google.maps.Size(30, 30));
        var green_marker = new google.maps.MarkerImage("images/markers/green-dot.png", new google.maps.Size(30, 30));
        
        var zoom_levels = {
            0:  'country',
            1:  'region',
            2:  'city',
            3:  'host'
        };
        
        var global_line_minima = {
            'country':  -1,
            'region':   -1,
            'city':     -1
        }
        var global_line_maxima = {
            'country':  -1,
            'region':   -1,
            'city':     -1
        }
        
        jQuery.ajaxSetup({
            cache: false,
            dataType: 'json',
            proccessData: false,
            type: 'POST'
        });
        
        $(document).ajaxError(function(event, jqXHR, ajaxSettings, exception) {
            ajax_error = 1;
            $(document).trigger('loading_cancelled');
            
            if (jqXHR.status === 0) {
                // show_error(800, "Could not connect to the server. Please check your network connectivity.");
            } else if (jqXHR.status == 404) {
                show_error(800, "The requested page could not be found (HTTP 404).");
            } else if (jqXHR.status == 500) {
                show_error(800, "Internal server error (HTTP 500).");
            } else if (exception === 'parsererror') {
                show_error(800, "The requested JSON document could not be parsed.");;
            } else if (exception === 'timeout') {
                show_error(800, "Timeout error.");
            } else if (exception === 'abort') {
                show_error(800, "The AJAX request has been aborted.");
            } else {
                show_error(800);
            }
        });
        
        // Retrieve config
        $.ajax({
            url: 'json/getconfig.php',
            success: function(data) {
                if (data.status == 0) { // Success
                    config = data.config;
                    $(document).trigger('config_loaded');
                } else {
                    show_error(801, data.status_message);
                }
            }
        });
        
        // Retrieve constants
        $.ajax({
            url: 'json/getconstants.php',
            success: function(data) {
                if (data.status == 0) { // Success
                    constants = data.constants;
                    $(document).trigger('constants_loaded');
                } else {
                    show_error(813, data.status_message);
                }
            }
        });
        
        // Retrieve NfSen config
        $.ajax({
            url: 'json/getnfsenconfig.php',
            success: function(data) {
                if (data.status == 0) { // Success
                    nfsen_config = data.config;
                    $(document).trigger('nfsen_config_loaded');
                } else {
                    show_error(802, data.status_message);
                }
            }
        });
        
        // Retrieve errors from server
        var cookie_value = get_cookie_value('SURFmap', 'errors_retrieved');
        if (cookie_value == undefined || cookie_value == 0) {
            $.ajax({
                url: 'json/geterrors.php',
                success: function(data) {
                    if (data.status == 0) { // Success
                        if (data.error_codes.length == 0) {
                            /* Set cookie so that errors are not retrieved
                             * another time within the same session
                             */
                            update_cookie_value('SURFmap', 'errors_retrieved', 1);
                        } else {
                            $('#error_messages').empty();
                            $.each(data.error_codes, function (i, error_code) {
                                var error = $('<p />', { 'id': 'error-' + i });
                                var icon = $('<span />', { 'class': 'ui-icon ui-icon-alert' });
                                var message = $('<div />', { 'id': 'error-text' });
                                message.html(get_backend_error_description(error_code));
            
                                error.append(icon);
                                error.append(message);
                                $('#error_messages').append(error);
                            });
        
                            $('#error_messages').show();
                        }
                    } else {
                        show_error(810, data.status_message);
                    }
                }
            });
        }
    </script>
</head>
<body>
    <div id="header">
        <span id="logo"><a href="http://www.utwente.nl/en" target="_blank"><img src="images/UT_Logo.png" alt="University of Twente"/></a></span>
        <div id="header_line">&nbsp;</div>
        <div id="header_text">
            <p><span style="font-weight: bold;">SURFmap</span><br>A network monitoring tool based on the Google Maps API</p>
        </div>
    </div>
    <div id="error_messages" class="ui-state-error ui-corner-all" style="display:none;"></div>
    <div id="map_canvas"></div>
    <div id="footer">
        <div id="legend">
            <div id="legend_description"></div>
            <div id="legend_scale">
                <div id="legend_scale_color"></div>
                <span id="legend_scale_text">
                    <span id="legend_scale_text_left"></span>
                    <span id="legend_scale_text_mid"></span>
                    <span id="legend_scale_text_right"></span>
                </span>
            </div>
        </div>
        <div class="footer" id="footerfunctions" style='float:right;'>
            <a href="Javascript:show_flow_details();" title="Show flow details">Flow details</a> | 
            <a href="Javascript:show_info('help');" title="Show help information">Help</a> | 
            <a href="Javascript:show_info('about');" title="Show about information">About</a>
        </div>
    </div>
    
    <div class="panel">
        <div class="panel_section_title"><p>Zoom levels</p></div>
        <div class="panel_section_content">
            <table>
                <tr>
                    <td style="width:85px">
                        <form>
                            <input type="radio" name="zoom_level" id="zoom_level_country" onclick="zoom(0, 2)" /><label for="zoom_level_country" class="zoom_level_label clickable">Country</label><br />
                            <input type="radio" name="zoom_level" id="zoom_level_region" onclick="zoom(0, 5)" /><label for="zoom_level_region" class="zoom_level_label clickable">Region</label><br />
                            <input type="radio" name="zoom_level" id="zoom_level_city" onclick="zoom(0, 8)" /><label for="zoom_level_city" class="zoom_level_label clickable">City</label><br />
                            <input type="radio" name="zoom_level" id="zoom_level_host" onclick="zoom(0, 11)" /><label for="zoom_level_host" class="zoom_level_label clickable">Host</label><br />
                        </form>
                    </td>
                    <td style="vertical-align:bottom">
                        <input type="checkbox" id="auto-refresh" /><label for="auto-refresh">Auto-refresh</label>
                    </td>
                </tr>
            </table>
        </div>
        <hr />
        <div class="panel_section_title"><p>Options</p></div>
        <div class="panel_section_content" id="optionPanel">
            <form id="options">
                <table>
                    <tr>
                        <td style="width:90px;">Sources</td>
                        <td>
                            <select id="nfsensources" name="nfsensources[]" multiple="multiple"></select>
                        </td>
                    </tr>
                </table><br />
                <input type="radio" id="nfsen_option_stattopN" name="nfsen_option" value="1" onclick="if (!$('#nfsen_stat_order').is(':visible')) $('#nfsen_stat_order').toggle();" />
                <label for="nfsen_option_stattopN" class="clickable">Stat TopN</label><br />
                <div id="nfsen_stat_order" style="margin-top:10px; margin-bottom:10px; text-align:right;">
                    <input type="radio" name="nfsen_stat_order" value="0" id="nfsen_stat_order_flows" /><label for="nfsen_stat_order_flows">flows</label>
                    <input type="radio" name="nfsen_stat_order" value="1" id="nfsen_stat_order_packets" /><label for="nfsen_stat_order_packets">packets</label>
                    <input type="radio" name="nfsen_stat_order" value="2" id="nfsen_stat_order_bytes" /><label for="nfsen_stat_order_bytes">bytes</label>
                </div>
                <input type="radio" id="nfsen_option_listflows" name="nfsen_option" value="0" onclick="if ($('#nfsen_stat_order').is(':visible')) $('#nfsen_stat_order').toggle();" />
                <label for="nfsen_option_listflows" class="clickable">List Flows</label><br />
                <div style="margin-top:10px; width:195px;">
                    <span style="float:left; margin-top:3px;">Begin</span>
                    <input type="text" id="date_start" class="date_time_input" />
                    <div class="ui-state-default ui-corner-all no-icon-background" style="float:right; margin-top:2px;">
                        <span class="ui-icon ui-icon-arrowthick-1-e" title="Copy 'end' time to here" onclick="copy_date_time_selector('date_end', 'date_start');"></span>
                    </div>
                </div><br />
                <div style="margin-top:10px; width:195px;">
                    <span style="float:left; margin-top:3px;">End</span>
                    <input type="text" id="date_end" class="date_time_input" />
                    <div class="ui-state-default ui-corner-all no-icon-background" style="float:right; margin-top:2px;">
                        <span class="ui-icon ui-icon-arrowthick-1-e" title="Copy 'begin' time to here" onclick="copy_date_time_selector('date_start', 'date_end');"></span>
                    </div>
                </div><br />
                <div style="margin-top:10px; width:195px;">
                    <span style="float:left; margin-top:3px;">Limit to</span>
                    <span style="width:127px; float:right;"><input type="text" id="flow_record_count_input" style="width:35px; padding:2px 0px 2px 0px; text-align:center;" maxlength="4"><label for="flow_record_count_input"> flows</label><span>
                </div><br />
                <div style="margin-top:15px; width:195px;">
                    <div class="filter_label clickable" id="filter_flow" title="Show flow filter">
                        <div class="ui-state-default ui-corner-all no-icon-background" style="float:left;">
                            <span class="ui-icon filter_label_icon ui-icon-triangle-1-e"></span>
                        </div>
                        <span class="filter_label_text disable-select" style="float:left;">Flow filter</span><br />
                    </div>
                    <textarea class="filter" id="filter_flow_text" rows="2" cols="26"></textarea>
                </div><br />
                <div style="width:195px;">
                    <div class="filter_label clickable" id="filter_geo" title="Show geo filter">
                        <div class="ui-state-default ui-corner-all no-icon-background" style="float:left;">
                            <span class="ui-icon filter_label_icon ui-icon-triangle-1-e"></span>
                        </div>
                        <span class="filter_label_text disable-select" style="float:left;">Geo filter</span><br />
                    </div>
                    <textarea class="filter" id="filter_geo_text" rows="2" cols="26"></textarea>
                </div><br />
                <div style="text-align:center; width:195px;">
                    <input type="submit" name="submit" value="Load data" />
                </div>
            </form>
        </div>
    </div>
    <div class="panel_trigger" href="#">Menu</div>
    <div id="netflowDataDetails" style='margin-top: 10px;'></div>
    <div id="error_dialog"></div>
    <div id="warning_dialog"></div>
    <div id="info_dialog"></div>
    <div id="loading_dialog"></div>
    
    <script type="text/javascript">
       /*
        * Adds missing location information to flow data based on upper layers.
        */
        function complement_location_information () {
            $.each(flow_data, function (flow_index, flow_item) {
                $.each(zoom_levels, function (zoom_level_index, zoom_level) {                    
                    if (zoom_level_index == 1) { // Region                        
                        // If one end point is unkwnown (not geolocated)...
                        if (flow_item.src_region_lat == undefined) {
                            if (flow_item.src_country_lat == undefined) { // If country level also unknown, skip the current flow record
                                return true;
                            } else { // ... use upper level
                                flow_item.src_region_lat = flow_item.src_country_lat;
                                flow_item.src_region_lng = flow_item.src_country_lng;
                            }
                        }
                        if (flow_item.dst_region_lat == undefined) {
                            if (flow_item.dst_country_lat == undefined) { // If country level also unknown, skip the current flow record
                                return true;
                            } else { // ... use upper level
                                flow_item.dst_region_lat = flow_item.dst_country_lat;
                                flow_item.dst_region_lng = flow_item.dst_country_lng;
                            }
                        }
                    } else if (zoom_level_index == 2) { // City                        
                        // If one end point is unkwnown (not geolocated)...
                        if (flow_item.src_city_lat == undefined) {
                            if (flow_item.src_region_lat != undefined) { // If region level is available, use it...
                                flow_item.src_city_lat = flow_item.src_region_lat;
                                flow_item.src_city_lng = flow_item.src_region_lng;
                            } else if (flow_item.src_country_lat != undefined) { // If country level is available, use it...
                                flow_item.src_city_lat = flow_item.src_country_lat;
                                flow_item.src_city_lng = flow_item.src_country_lng;
                            } else { // Skip the current flow record
                                return true;
                            }
                        }
                        if (flow_item.dst_city_lat == undefined) {
                            if (flow_item.dst_region_lat != undefined) { // If region level is available, use it...
                                flow_item.dst_city_lat = flow_item.dst_region_lat;
                                flow_item.dst_city_lng = flow_item.dst_region_lng;
                            } else if (flow_item.dst_country_lat != undefined) { // If country level is available, use it...
                                flow_item.dst_city_lat = flow_item.dst_country_lat;
                                flow_item.dst_city_lng = flow_item.dst_country_lng;
                            } else { // Skip the current flow record
                                return true;
                            }
                        }
                    }
                });
            });
        }
        
        function init_lines () {
            var start_time = new Date();
            lines = [];
            global_line_minima = {
                'country':  -1,
                'region':   -1,
                'city':     -1,
                'host':     -1
            }
            global_line_maxima = {
                'country':  -1,
                'region':   -1,
                'city':     -1,
                'host':     -1
            }
            
            $.each(flow_data, function (flow_index, flow_item) {
                $.each(zoom_levels, function (zoom_level_index, zoom_level) {
                    if (zoom_level_index == 0) { // Country
                        // If one end point is unkwnown (not geolocated), skip the current flow record
                        if (flow_item.src_country_lat == undefined || flow_item.dst_country_lat == undefined) {
                            return true;
                        }
                    } else if (zoom_level_index == 1) { // Region
                        // If one end point is unkwnown (not geolocated), skip the current flow record
                        if (flow_item.src_region_lat == undefined || flow_item.dst_region_lat == undefined) {
                            return true;
                        }
                    } else if (zoom_level_index == 2) { // City
                        // If one end point is unkwnown (not geolocated), skip the current flow record
                        if (flow_item.src_city_lat == undefined || flow_item.dst_city_lat == undefined) {
                            return true;
                        }
                    }
                    
                    // Copy flow information to local-scope variables
                    var point1, point2;
                    if (zoom_level_index == 0) { // Country
                        point1 = new google.maps.LatLng(flow_item.src_country_lat, flow_item.src_country_lng);
                        point2 = new google.maps.LatLng(flow_item.dst_country_lat, flow_item.dst_country_lng);
                    } else if (zoom_level_index == 1) { // Region
                        point1 = new google.maps.LatLng(flow_item.src_region_lat, flow_item.src_region_lng);
                        point2 = new google.maps.LatLng(flow_item.dst_region_lat, flow_item.dst_region_lng);
                    } else { // City & Host
                        point1 = new google.maps.LatLng(flow_item.src_city_lat, flow_item.src_city_lng);
                        point2 = new google.maps.LatLng(flow_item.dst_city_lat, flow_item.dst_city_lng);
                    }
                    
                    // Find line (if it exists)
                    var lines_index = -1; // -1: line does not exist, >= 0: line index in 'lines' array
                    $.each(lines, function (line_index, line) {
                        if (line.level == zoom_level_index
                                && ((line.point1.equals(point1) && line.point2.equals(point2))
                                || (line.point1.equals(point2) && line.point2.equals(point1)))) {
                            lines_index = line_index;
                            return false;
                        }
                    });
                    
                    // Create line, if necessary
                    if (lines_index == -1) { // Line does NOT exist
                        var line = {};
                        line.point1 = point1;
                        line.point2 = point2;
                        line.level = zoom_level_index;
                        line.entries = [];
                        line.associated_flow_indices = [];
                        lines.push(line);
                        lines_index = lines.length - 1;
                    }
                    
                    // Update flow index association (i.e. index in 'flow_data' array)
                    lines[lines_index].associated_flow_indices.push(flow_index);
                    
                    // Find line entry (if it exists)
                    var entries_index = -1; // -1: entry does not exist, >= 0: entry index in 'entries' array
                    $.each(lines[lines_index].entries, function (entry_index, entry) {
                        if (zoom_level_index == 0 // Country
                                && entry.src_text.country == flow_item.src_country
                                && entry.dst_text.country == flow_item.dst_country) {
                            entries_index = entry_index;
                            return false;
                        } else if (zoom_level_index == 1 // Region
                                && entry.src_text.country == flow_item.src_country
                                && entry.dst_text.country == flow_item.dst_country
                                && entry.src_text.region == flow_item.src_region
                                && entry.dst_text.region == flow_item.dst_region) {
                            entries_index = entry_index;
                            return false;
                        } else if (zoom_level_index == 2 // City
                                && entry.src_text.country == flow_item.src_country
                                && entry.dst_text.country == flow_item.dst_country
                                && entry.src_text.region == flow_item.src_region
                                && entry.dst_text.region == flow_item.dst_region
                                && entry.src_text.city == flow_item.src_city
                                && entry.dst_text.city == flow_item.dst_city) {
                            entries_index = entry_index;
                            return false;
                        } else if (entry.src_text.country == flow_item.src_country // Host
                                && entry.dst_text.country == flow_item.dst_country
                                && entry.src_text.region == flow_item.src_region
                                && entry.dst_text.region == flow_item.dst_region
                                && entry.src_text.city == flow_item.src_city
                                && entry.dst_text.city == flow_item.dst_city
                                && entry.src_text.ip_address == flow_item.ipv4_src
                                && entry.dst_text.ip_address == flow_item.ipv4_dst) {
                            entries_index = entry_index;
                            return false;
                        }
                    });
                    
                    // Create line entry, if necessary. Otherwise, update (existing) line entry.
                    if (entries_index == -1) { // Line entry does NOT exist
                        var line_entry = {};
                        line_entry.packets = flow_item.packets;
                        line_entry.octets = flow_item.octets;
                        line_entry.flows = flow_item.flows;
                        line_entry.duration = flow_item.duration;
                        line_entry.src_text = {};
                        line_entry.dst_text = {};
                        
                        if (zoom_level_index == 0) { // Country
                            line_entry.src_text.country = flow_item.src_country;
                            line_entry.dst_text.country = flow_item.dst_country;
                        } else if (zoom_level_index == 1) { // Region
                            line_entry.src_text.country = flow_item.src_country;
                            line_entry.dst_text.country = flow_item.dst_country;
                            line_entry.src_text.region = flow_item.src_region;
                            line_entry.dst_text.region = flow_item.dst_region;
                        } else if (zoom_level_index == 2) { // City
                            line_entry.src_text.country = flow_item.src_country;
                            line_entry.dst_text.country = flow_item.dst_country;
                            line_entry.src_text.region = flow_item.src_region;
                            line_entry.dst_text.region = flow_item.dst_region;
                            line_entry.src_text.city = flow_item.src_city;
                            line_entry.dst_text.city = flow_item.dst_city;
                        } else { // Host
                            line_entry.src_text.country = flow_item.src_country;
                            line_entry.dst_text.country = flow_item.dst_country;
                            line_entry.src_text.region = flow_item.src_region;
                            line_entry.dst_text.region = flow_item.dst_region;
                            line_entry.src_text.city = flow_item.src_city;
                            line_entry.dst_text.city = flow_item.dst_city;
                            line_entry.src_text.ip_address = flow_item.ipv4_src;
                            line_entry.dst_text.ip_address = flow_item.ipv4_dst;
                        }
                        
                        // Add line entry to line
                        lines[lines_index].entries.push(line_entry);
                    } else { // Line entry exists
                        lines[lines_index].entries[entries_index].packets += flow_item.packets;
                        lines[lines_index].entries[entries_index].octets += flow_item.octets;
                        lines[lines_index].entries[entries_index].flows += flow_item.flows;
                        lines[lines_index].entries[entries_index].duration += flow_item.duration;
                    }
                });
            });
            
            // Determine maxima and sums, both global and per line
            $.each(zoom_levels, function (zoom_level_index, zoom_level) {
                $.each(lines, function (line_index, line) {
                    // Skip line if it doesn't belong to the current zoom level
                    if (line.level != zoom_level_index) return true;
                    
                    // Skip line if internal and that traffic should not be considered (setting in config.php)
                    if (config['ignore_marker_internal_traffic_in_line_color_classification'] &&
                            line.point1.equals(line.point2)) {
                        return true;
                    }
                    
                    line.flows_sum = 0;
                    line.packets_sum = 0;
                    line.octets_sum = 0;
                    $.each(line.entries, function (entry_index, entry_item) {
                        line.flows_sum += entry_item.flows;
                        line.packets_sum += entry_item.packets;
                        line.octets_sum += entry_item.octets;
                    });
                
                    var line_sum;
                    if (session_data['nfsen_option'] == 0 || session_data['nfsen_stat_order'] == 0) { // Flows
                        line_sum = line.flows_sum;
                    } else if (session_data['nfsen_stat_order'] == 1) { // Packets
                        line_sum = line.packets_sum;
                    } else { // Bytes
                        line_sum = line.octets_sum;
                    }
                    
                    if (global_line_minima[zoom_level] == -1 || global_line_maxima[zoom_level] == -1) { // Initial values
                        global_line_minima[zoom_level] = line_sum;
                        global_line_maxima[zoom_level] = line_sum;
                    } else if (line_sum < global_line_minima[zoom_level]) {
                        global_line_minima[zoom_level] = line_sum;
                    } else if (line_sum > global_line_maxima[zoom_level]) {
                        global_line_maxima[zoom_level] = line_sum;
                    }
                });
            });
            
            // Initialize line objects
            $.each(zoom_levels, function (zoom_level_index, zoom_level) {
                // Check whether global line minima/maxima are very close; if so, this may result in unbalanced color/thinkness ratios
                if (global_line_maxima[zoom_level] - global_line_minima[zoom_level] == 1) {
                    global_line_minima[zoom_level]--;
                    global_line_maxima[zoom_level]++;
                }
                
                // Check whether minimum value has become zero (which is semantically irrealistic)
                if (global_line_minima[zoom_level] == 0) {
                    global_line_minima[zoom_level]++;
                }
                
                $.each(lines, function (line_index, line) {
                    // Skip line if it doesn't belong to the current zoom level
                    if (line.level != zoom_level_index) return true;
                    
                    var line_sum;
                    if (session_data['nfsen_option'] == 0 || session_data['nfsen_stat_order'] == 0) { // Flows
                        line_sum = line.flows_sum;
                    } else if (session_data['nfsen_stat_order'] == 1) { // Packets
                        line_sum = line.packets_sum;
                    } else { // Bytes
                        line_sum = line.octets_sum;
                    }
                    
                    var ratio = (line_sum - global_line_minima[zoom_level]) / (global_line_maxima[zoom_level] - global_line_minima[zoom_level]);
                    if (isNaN(ratio)) ratio = 0.75;
                    
                    var color = jQuery.Color({ hue: (1 - ratio) * 120, saturation: 0.7, lightness: 0.5, alpha: 1 }).toHexString();
                    var thickness = Math.max((ratio + 1) * 3, 1.5);
                    var info_window_contents = "<table class=\"flow_info_table\">" + generate_line_info_window_contents(line_index, line.entries) + "</table>";
                    line.obj = create_line (line.point1, line.point2, info_window_contents, color, thickness);
                });
            });
        }
        
        function init_markers () {
            var start_time = new Date();
            markers = [];
            
            $.each(flow_data, function (flow_index, flow_item) {
                $.each(zoom_levels, function (zoom_level_index, zoom_level) {
                    $.each(['src', 'dst'], function () {
                        // Copy flow information to local-scope variables
                        var marker_text, entry_text, lat, lng;
                        if (zoom_level_index == 0 && this == 'src') { // Country / source
                            marker_text = flow_item.src_country;
                            entry_text = flow_item.src_region;
                            lat = flow_item.src_country_lat;
                            lng = flow_item.src_country_lng;
                        } else if (zoom_level_index == 0 && this == 'dst') { // Country / destination
                            marker_text = flow_item.dst_country;
                            entry_text = flow_item.dst_region;
                            lat = flow_item.dst_country_lat;
                            lng = flow_item.dst_country_lng;
                        } else if (zoom_level_index == 1 && this == 'src') { // Region / source
                            marker_text = flow_item.src_country + ", " + flow_item.src_region;
                            entry_text = flow_item.src_city;
                            lat = flow_item.src_region_lat;
                            lng = flow_item.src_region_lng;
                        } else if (zoom_level_index == 1 && this == 'dst') { // Region / destination
                            marker_text = flow_item.dst_country + ", " + flow_item.dst_region;
                            entry_text = flow_item.dst_city;
                            lat = flow_item.dst_region_lat;
                            lng = flow_item.dst_region_lng;
                        } else if (zoom_level_index == 2 && this == 'src') { // City / source
                            marker_text = flow_item.src_country + ", " + flow_item.src_region + ", " + flow_item.src_city;
                            entry_text = flow_item.src_city;
                            lat = flow_item.src_city_lat;
                            lng = flow_item.src_city_lng;
                        } else if (zoom_level_index == 2 && this == 'dst') { // City + Host / destination
                            marker_text = flow_item.dst_country + ", " + flow_item.dst_region + ", " + flow_item.dst_city;
                            entry_text = flow_item.dst_city;
                            lat = flow_item.dst_city_lat;
                            lng = flow_item.dst_city_lng;
                        } else if (this == 'src') { // Host / source
                            marker_text = flow_item.src_country + ", " + flow_item.src_region + ", " + flow_item.src_city;
                            entry_text = flow_item.ipv4_src;
                            lat = flow_item.src_city_lat;
                            lng = flow_item.src_city_lng;
                        } else { // Host / destination
                            marker_text = flow_item.dst_country + ", " + flow_item.dst_region + ", " + flow_item.dst_city;
                            entry_text = flow_item.ipv4_dst;
                            lat = flow_item.dst_city_lat;
                            lng = flow_item.dst_city_lng;
                        }
                        
                        // Find marker (if it exists)
                        var markers_index = -1; // -1: marker does not exist, >= 0: marker index in 'markers' array
                        $.each(markers, function (marker_index, marker) {
                            // Skip to next marker in case of wrong zoom level
                            if (marker.level != zoom_level_index) return true;
                            
                            if (marker.point.equals(new google.maps.LatLng(lat, lng))) {
                                markers_index = marker_index;
                                return false;
                            }
                        });
                        
                        // Create marker, if necessary
                        if (markers_index == -1) {
                            var marker = {};
                            marker.point = new google.maps.LatLng(lat, lng);
                            marker.level = zoom_level_index;
                            marker.entries = [];
                            marker.text = marker_text;
                            markers.push(marker);
                            markers_index = markers.length - 1;
                        }
                        
                        // Find marker entry (if it exists)
                        var entries_index = -1; // -1: entry does not exist, >= 0: entry index in 'entries' array
                        if (markers_index != -1) {
                            $.each(markers[markers_index].entries, function (entry_index, entry) {
                                if (entry.text == entry_text) {
                                    entries_index = entry_index;
                                    return false;
                                }
                            });
                        }
                        
                        // Create marker entry, if necessary. Otherwise, update (existing) marker entry
                        if (entries_index == -1) {
                            var marker_entry = {};
                            marker_entry.text = entry_text;
                            
                            if (zoom_level_index == 3) { // Host
                                marker_entry.flows = flow_item.flows;
                            } else { // Country, region, city
                                marker_entry.hosts = [];
                                
                                var host = (this == 'src') ? flow_item.ipv4_src : flow_item.ipv4_dst;
                                
                                // Only add hosts if they haven't been accounted yet
                                if (jQuery.inArray(host, marker_entry.hosts) == -1) { // Destination
                                    marker_entry.hosts.push(host);
                                }
                            }
                            
                            // Add marker entry to marker
                            markers[markers_index].entries.push(marker_entry);
                        } else {
                            var host = (this == 'src') ? flow_item.ipv4_src : flow_item.ipv4_dst;
                            
                            if (zoom_level_index == 3) { // Host
                                if (host == markers[markers_index].entries[entries_index].text) {
                                    markers[markers_index].entries[entries_index].flows += flow_item.flows;
                                }
                            } else { // Country, region, city
                                // Only add hosts if they haven't been accounted yet
                                if (jQuery.inArray(host, markers[markers_index].entries[entries_index].hosts) == -1) { // Destination
                                    markers[markers_index].entries[entries_index].hosts.push(host);
                                }
                            }
                        }
                    }); // End of source/destination
                }); // End of zoom levels
            }); // End of flow data
            
            if (is_extension_active('Location-aware exporting')) {
                var exporter_markers = [];
                
                $.each(flow_data, function (flow_index, flow_item) {
                    var lat = parseFloat(flow_item.loc_lat_int + "." + flow_item.loc_lat_dec);
                    var lng = parseFloat(flow_item.loc_lng_int + "." + flow_item.loc_lng_dec);
                    var marker_text = ""; // TODO Fill this
                    
                    // Find marker (if it exists)
                    var markers_index = -1; // -1: marker does not exist, >= 0: marker index in 'markers' array
                    $.each(exporter_markers, function (marker_index, marker) {
                        if (marker.point.equals(new google.maps.LatLng(lat, lng))) {
                            markers_index = marker_index;
                            return false;
                        }
                    });
                    
                    // Create marker, if necessary
                    if (markers_index == -1) {
                        var marker = {};
                        marker.point = new google.maps.LatLng(lat, lng);
                        marker.entries = [];
                        marker.text = marker_text;
                        exporter_markers.push(marker);
                        markers_index = exporter_markers.length - 1;
                    }
                });
                
                // TODO Merge marker arrays (extension and non-extension)
                // TODO Add marker to all four zoom levels
            }
            
            // Initialize marker objects
            $.each(zoom_levels, function (zoom_level_index, zoom_level) {
                $.each(markers, function (marker_index, marker) {
                    // Skip marker if it doesn't belong to the current zoom level
                    if (marker.level != zoom_level_index) return true;
                    
                    var info_window_contents = "<table class=\"flow_info_table\">" + generate_marker_info_window_contents(marker.entries) + "</table>";
                    
                    // Check for internal marker traffic
                    var internal_traffic = false;
                    $.each(lines, function (line_index, line) {
                        // Skip line if it doesn't belong to the current zoom level
                        if (marker.level != zoom_level_index) return true;
                        
                        // Check for internal traffic 'within' a marker
                        if (line.point1.equals(line.point2)
                                && line.point1.equals(marker.point)) {
                            internal_traffic = true;
                            return false;
                        }
                    });
                    
                    if (internal_traffic) {
                        marker.obj = create_marker (marker.point, format_location_name(marker.text), info_window_contents, 'green');
                    } else {
                        marker.obj = create_marker (marker.point, format_location_name(marker.text), info_window_contents);
                    }
                });
            });
        }
     
        /*
         * Optimizes various aspects of SURFmap for demo mode, low resolution screns, etc.
         */
        function optimize_display () {
            var client_height = parent.document.documentElement.clientHeight;
        
            /*
             * IE8/IE9 does not properly support an iFrame width/height of 100% 
             * when "<meta http-equiv="X-UA-Compatible" content="IE=edge" />" is used.
             * http://brondsema.net/blog/index.php/2007/06/06/100_height_iframe
             */
            if ($("meta[http-equiv='X-UA-Compatible'][content='IE=edge']").length > 0 // Check whether the problematic meta-tag has been set
                    && $.browser.msie && parseInt($.browser.version) >= 8) {
                parent.document.getElementById("surfmapParentIFrame").style.height = client_height +"px";
            }
            
            if (config['demo_mode']) {
                var time = session_data['hours1'] + ":" + session_data['minutes1'];
                var demo_mode_title = $('<div/>').text(config['demo_mode_page_title'] + " (" + time + ")");
                demo_mode_title.css('float', 'left').css('margin-top', '24px').css('font-size', '20pt');
                $('div#header').append(demo_mode_title);
                
                $('div#header').css('width', '100%');
                $('#map_canvas').css('width', '100%');
                $('#map_canvas').css('height', '100%');
                $('div.panel_trigger').hide();
                $('div#footer').hide();
            } 

            if (client_height < 850) {
                $('#logo').hide();
                
                $('div#header span#logo').hide();
                $('div#header div#header_line').hide();
                
                // Replace new line ('<br/>') by dash ('-')
                var header_text = $('div#header div#header_text p').html();
                header_text = header_text.replace("<br>", " - ");
                
                $('div#header div#header_text p').html(header_text);
                
                // Center header
                $('div#header div#header_text').css('width', '100%').css('margin', '0 auto');
                $('div#header div#header_text p').css('text-align', 'center');
            }
        }
        
        function init_legend () {
            // Since no legend is shown in demo mode, further processing can be skipped
            if (config['demo_mode']) return;
            
            if (session_data['nfsen_option'] == 0) {
                $('#legend_description').text("Flows:");
            } else {
                switch (session_data['nfsen_stat_order']) {
                    case 0:     $('#legend_description').text("Flows:");
                                break;
                                
                    case 1:     $('#legend_description').text("Packets:");
                                break;
                                
                    case 2:     $('#legend_description').text("Bytes:");
                                break;
                                
                    default:    break;
                }
            }
            
            // Reset legend
            $('#legend_scale_color').empty();
            
            var color;
            for (var i = 120; i >= 0; i = i-1.5) {
                color = jQuery.Color({ hue: i, saturation: 0.7, lightness: 0.5, alpha: 1 }).toHexString();
                $('#legend_scale_color').append("<div style=\"background-color:" + color + "; height:15px; width:10px; display:inline-block; \"></div>");
            }
            
            var zoom_level_name = "";
            switch (get_SM_zoom_level(map.getZoom())) {
                case 0:     zoom_level_name = "country";
                            break;
                            
                case 1:     zoom_level_name = "region";
                            break;
                            
                case 2:     zoom_level_name = "city";
                            break;
                            
                case 3:     zoom_level_name = "host";
                            break;
                            
                default:    break;
            }
            
            var min = global_line_minima[zoom_level_name];
            var max = global_line_maxima[zoom_level_name];
            var mid = (((max + min) / 2) % 1 == 0) ? ((max + min) / 2) : ((max + min) / 2).toFixed(1);
            
            // Hide legend if no lines are visible at all or only one line is visible
            if (min == -1 || max == -1 || min == max) {
                $('#legend #legend_scale_text').css({visibility: 'hidden'});
            } else {
                // Make legend text visible again
                if ($('#legend #legend_scale_text').css('visibility') == 'hidden') {
                    $('#legend #legend_scale_text').css({visibility: ''});
                }
                
                // If 'max < 1000', min is also < 1000 and therefore SI scales will not apply
                if (max < 1000) {
                    min = parseInt(min);
                    mid = parseInt(mid);
                    max = parseInt(max);
                } else {
                    min = apply_SI_Scale(parseInt(min));
                    mid = apply_SI_Scale(parseInt(mid));
                    max = apply_SI_Scale(parseInt(max));
                }
                
                $('#legend_scale_text_left').text(min);
                $('#legend_scale_text_mid').text(mid);
                $('#legend_scale_text_right').text(max);
                
                if (min == mid || max == mid) {
                    $('#legend_scale_text_mid').css({visibility: 'hidden'});
                }
                
                $('#legend_scale_text_mid').css('width', 810 - $('#legend_scale_text_left').width() - $('#legend_scale_text_right').width() - 20);
            }
        }
        
        function init_panel () {
            // Since no menu panel is shown in demo mode, further processing can be skipped
            if (config['demo_mode']) return;
            
            $('div.panel_trigger').click(function () {
                $('.panel').toggle('slow');
                $(this).toggleClass('active');
                return false;
            });
            
            // TODO Positioning of menu trigger using jQuery Position API
            // http://api.jqueryui.com/position/
            // $('div.panel_trigger').position({
//                     my: "right top",
//                     at: "right top",
//                     of: "#map_canvas",
//                     offset: "0 50"
//                 });
//              console.log($('div.panel_trigger').position());
            if (parent.document.documentElement.clientHeight < 850) {
                $('div.panel_trigger').css('top', '54px');
                $('.panel').css('top', '54px');
            } else {
                $('div.panel_trigger').css('top', '89px');
                $('.panel').css('top', '89px');
            }
        
            if (config['auto_open_menu'] == 1) {
                $('div.panel_trigger').trigger('click');
            }
            
            // Forbid entering 'new line' in filter input textarea
            $(".filter").keypress(function(event) {
                if (event.keyCode == 13) return false;
            });
            
            // On submit
            $('#options').submit(function() {
                // Close all open dialogs
                if ($('#error_dialog').dialog('isOpen')) {
                    $('#error_dialog').dialog('close');
                }
                if ($('#warning_dialog').dialog('isOpen')) {
                    $('#warning_dialog').dialog('close');
                }
                if ($('#info_dialog').dialog('isOpen')) {
                    $('#info_dialog').dialog('close');
                }
                
                // Close filter input text field, if empty
                if ($('#filter_flow_text').is(':visible') && $('#filter_flow_text').val() == "") {
                    $('#filter_flow').trigger('click');
                }
                if ($('#filter_geo_text').is(':visible') && $('#filter_geo_text').val() == "") {
                    $('#filter_geo').trigger('click');
                }
                
                if ($("#nfsensources").multiselect("widget").find("input:checked").length == 0) {
                    show_error(999);
                    return false;
                }
                
                if ($('#date_start').datetimepicker('getDate') > $('#date_end').datetimepicker('getDate')) {
                    show_error(997);
                    return false;
                }
                
                if ($('#flow_record_count_input').val() == '0') {
                    show_error(998);
                    return false;
                }
                
                // Check for heavy query
                var period = ($('#date_end').datetimepicker('getDate') - $('#date_start').datetimepicker('getDate')) / 1000;
                if (config['show_warnings'] && ($("#nfsensources").multiselect("widget").find("input:checked").length > 3
                        || (period > 3600 && session_data['nfsen_option'] == 1))) { // 3600 seconds -> 2 hours
                    $('#warning_dialog').empty();
                    var message = "You have selected a potentially heavy query.<br /><br />Are you sure you want to continue?";
                    $('#warning_dialog').append("<p><span class=\"ui-icon ui-icon-info\" style=\"float: left; margin: 0 7px 50px 0;\"></span>" + message + "</p>");
                    $('#warning_dialog').dialog({
                        buttons: {
                            Yes: function () {
                                $(this).dialog('close');
                                start();
                                return false;
                            },
                            No: function () {
                                $(this).dialog('close');
                            }
                        },
                        closeOnEscape: false,
                        height: 'auto',
                        modal: true,
                        position: 'center',
                        resizable: false,
                        title: 'Warning',
                        width: 'auto'
                    }).dialog('open');
                    return false;
                } else {
                    start();
                    return false;
                }
                
                function start () {
                    store_session_data_handle = setInterval(function () {
                        // Wait until all connections to server are closed (and all session data is written to server)
                        if ($.active == 0) {
                            clearInterval(store_session_data_handle);
                            $(document).trigger('load_flow_data');
                        }
                    }, 500);
                    
                    info_window.close();    
                    $('input[type=submit]', this).prop('disabled', true);
                    $('div.panel_trigger').trigger('click');
                    return false;
                }
            });
        }
        
        function configure_panel () {
            // Zoom level table
            switch (get_SM_zoom_level(map.getZoom())) {
                case 0:     $('#zoom_level_country').prop('checked', true);
                            break;
                            
                case 1:     $('#zoom_level_region').prop('checked', true);
                            break;
                                        
                case 2:     $('#zoom_level_city').prop('checked', true);
                            break;
                            
                case 3:     $('#zoom_level_host').prop('checked', true);
                            break;
                                                    
                default:    break;
            }
            
            // Auto refresh
            $('#auto-refresh').prop('checked', session_data['refresh'] > 0).click(function (event) {
                // Trigger refresh immediately when 'auto-refresh' is enabled during current session
                if ($('#auto-refresh').is(':checked')) {
                    $(document).trigger('load_flow_data');
                } else {
                    clearInterval(auto_refresh_handle);
                }
            });
            if (session_data['refresh'] > 0) {
                auto_refresh_handle = setInterval(function () {
                    $(document).trigger('load_flow_data');
                }, constants['refresh_interval']);
            }
            
            // NfSen sources
            var truncated_nfsen_profile = (session_data['nfsen_profile'].length > 22) ? session_data['nfsen_profile'].substr(0, 22) + "..." : session_data['nfsen_profile'];
            jQuery('<optgroup/>', {
                label: "Profile '" + truncated_nfsen_profile + "'"
            }).appendTo('#nfsensources');
            for (var i = 0; i < session_data['nfsen_all_sources'].length; i++) {
                var source_selected = (jQuery.inArray(session_data['nfsen_all_sources'][i], session_data['nfsen_selected_sources']) >= 0);
                
                jQuery('<option/>', {
                    // selected: source_selected,
                    text: session_data['nfsen_all_sources'][i]
                }).prop('selected', source_selected).appendTo('#nfsensources optgroup');
            }
            
            // Initialize source selector (jQuery)
            $('#nfsensources').multiselect({
                minWidth: 135,
                header: true,
                open: function() {
                    $('div.ui-multiselect-menu').css('left', '');
                    $('div.ui-multiselect-menu').css('right', '23px');
                    $('div.ui-multiselect-menu').css('width', '180px');
                },
                close: function() {
                    var selected_nfsen_sources = [];
                    $('#nfsensources option:selected').each(function() {
                        selected_nfsen_sources.push($(this).val());
                    });
                    
                    // Compare arrays; also works if elements are not in the same order
                    if (!($(selected_nfsen_sources).not(session_data['nfsen_selected_sources']).length == 0 
                            && $(session_data['nfsen_selected_sources']).not(selected_nfsen_sources).length == 0)) {
                        $(document).trigger('session_data_changed', { 'nfsen_selected_sources': selected_nfsen_sources } );
                    }
                }
            });
            
            // NfSen option
            if (session_data['nfsen_option'] == 1) { // Stat TopN
                $('#nfsen_option_stattopN').prop('checked', true);
            } else {
                $('#nfsen_option_listflows').prop('checked', true);
                $('#nfsen_stat_order').hide();
            }
            $('input:radio[name=\'nfsen_option\']').change(function () {
                if ($('input:radio[name=\'nfsen_option\']:checked').val() != session_data['nfsen_option']) {
                    $(document).trigger('session_data_changed', { 'nfsen_option': $('input:radio[name=\'nfsen_option\']:checked').val() } );
                }
            });
            
            // NfSen stat order
            $.each($('#nfsen_stat_order').children('input[name=\'nfsen_stat_order\']'), function(index, value) {
                if($(this).val() == session_data['nfsen_stat_order']) {
                    $(this).prop('checked', true);
                }
            });
            
            // Initialize NfSen stat order button set (jQuery)
            $('#nfsen_stat_order').buttonset().change(function () {
                if($('input[name=\'nfsen_stat_order\']:checked').val() != session_data['nfsen_stat_order']) {
                    $(document).trigger('session_data_changed', { 'nfsen_stat_order': $('input[name=\'nfsen_stat_order\']:checked').val() } );
                }
            });
            
            // Initialize date/time pickers
            $('.date_time_input').datetimepicker({
                maxDateTime: new Date(),
                stepMinute: 5,
                onClose: function(dateText, inst) {
                    var new_date_time = $(this).datetimepicker('getDate');
                    var selector_date, selector_hours, selector_minutes;
                    if ($(this).attr('id') == 'date_start') {
                        selector_date = "date1";
                        selector_hours = "hours1";
                        selector_minutes = "minutes1";
                    } else {
                        selector_date = "date2";
                        selector_hours = "hours2";
                        selector_minutes = "minutes2";
                    }
                    
                    // If date and/or time has changed
                    if (new_date_time.getFullYear() != parseInt(session_data[selector_date].substring(0,4))
                            || (new_date_time.getMonth() + 1) != parseInt(session_data[selector_date].substring(4,6))
                            || new_date_time.getDate() != parseInt(session_data[selector_date].substring(6,8))
                            || new_date_time.getHours() != parseInt(session_data[selector_hours])
                            || new_date_time.getMinutes() != parseInt(session_data[selector_minutes])) {
                        var date_string = new_date_time.getFullYear().toString();
                        if (new_date_time.getMonth() + 1 < 10) {
                            date_string += "0";
                        }
                        date_string += (new_date_time.getMonth() + 1).toString();
                        if (new_date_time.getDate() < 10) {
                            date_string += "0";
                        }
                        date_string += new_date_time.getDate().toString();
                        
                        var hours_string = (new_date_time.getHours() < 10) ? "0" : "";
                        hours_string += new_date_time.getHours().toString();
                        
                        var minutes_string = (new_date_time.getMinutes() < 10) ? "0" : "";
                        minutes_string += new_date_time.getMinutes().toString();
                        
                        var obj = {};
                        obj[selector_date] = date_string;
                        obj[selector_hours] = hours_string;
                        obj[selector_minutes] = minutes_string;
                        $(document).trigger('session_data_changed', obj);
                    }
                },
                controlType: {
                    create: function(tp_inst, obj, unit, val, min, max, step) {
                        $('<input class="ui-timepicker-input" value="' + val + '" style="width:50%">')
                            .appendTo(obj)
                            .spinner({
                                min: min,
                                max: max,
                                step: step,
                                change: function(e,ui) { // key events
                                    tp_inst._onTimeChange();
                                    tp_inst._onSelectHandler();
                                },
                                spin: function(e,ui) { // spin events
                                    tp_inst.control.value(tp_inst, obj, unit, ui.value);
                                    tp_inst._onTimeChange();
                                    tp_inst._onSelectHandler();
                                }
                            });
                        return obj;
                    },
                    options: function(tp_inst, obj, unit, opts, val) {
                        if(typeof(opts) == 'string' && val !== undefined)
                                return obj.find('.ui-timepicker-input').spinner(opts, val);
                        return obj.find('.ui-timepicker-input').spinner(opts);
                    },
                    value: function(tp_inst, obj, unit, val) {
                        if(val !== undefined)
                            return obj.find('.ui-timepicker-input').spinner('value', val);
                        return obj.find('.ui-timepicker-input').spinner('value');
                    }
                }
            });
            
            /* Set date/time twice because of a bug in date/time selector that causes
             * minutes not to be updated after the first action. See copy_date_time_selector
             * for more details.
             */
            for (var i = 0; i < 2; i++) {
                $('#date_start').datetimepicker('setDate', new Date(
                        session_data['date1'].substring(0,4),
                        session_data['date1'].substring(4,6) - 1, // Months are zero-indexed
                        session_data['date1'].substring(6,8),
                        session_data['hours1'],
                        session_data['minutes1'],
                        0 // milliseconds
                ));
                $('#date_end').datetimepicker('setDate', new Date(
                        session_data['date2'].substring(0,4),
                        session_data['date2'].substring(4,6) - 1, // Months are zero-indexed
                        session_data['date2'].substring(6,8),
                        session_data['hours2'],
                        session_data['minutes2'],
                        0 // milliseconds
                ));
            }
            
            // Initialize flow record count
            $('#flow_record_count_input').val(session_data['flow_record_count'].toString()).change(function () {
                if (parseInt($('#flow_record_count_input').val()) != session_data['flow_record_count']) {
                    $(document).trigger('session_data_changed', { 'flow_record_count': parseInt($('#flow_record_count_input').val()) } );
                }
            });
            
            // Initialize filter fields
            $('.filter_label').click(function(event) {
                var icon;
                if (event.target.className == 'filter_label_text') {
                    icon = $(this).parent().find('span:first');
                } else if (event.target.className == 'filter_label_text') {
                    icon = $(this).prev().find('span:first');
                } else if (event.target.className.indexOf('filter_label') != -1) {
                    icon = $(this).find('span:first');
                }
                icon.toggleClass('ui-icon-triangle-1-e').toggleClass('ui-icon-triangle-1-s');
                
                var text_area = $(this).parent().find('textarea:first');
                if (icon.hasClass('ui-icon-triangle-1-s')) {
                    text_area.show();
                } else {
                    text_area.hide();
                }
            });
            
            // Show filter input fields when filter is set
            if (session_data['flow_display_filter'] != '') {
                $('#filter_flow_text').val(session_data['flow_display_filter']);
                $('#filter_flow').trigger('click');
            }
            if (session_data['geo_filter'] != '') {
                $('#filter_geo_text').val(session_data['geo_filter']);
                $('#filter_geo').trigger('click');
            }
            
            $('#filter_flow_text').change(function () {
                if ($('#filter_flow_text').val() != session_data['flow_display_filter']) {
                    $(document).trigger('session_data_changed', { 'flow_display_filter': $('#filter_flow_text').val() } );
                }
            });
            $('#filter_geo_text').change(function () {
                if ($('#filter_geo_text').val() != session_data['geo_filter']) {
                    $(document).trigger('session_data_changed', { 'geo_filter': $('#filter_geo_text').val() } );
                }
            });
        }
        
        /*
         * Determines the error message belonging to a certain backend error code.
         */
        function get_backend_error_description (error_code) {
            var description;
            switch (error_code) {
                case 0:     description = "PHP PDO driver for SQLite3 is not installed.";
                            break;
                            
                case 1:     description = "Could not find database file.";
                            break;
                            
                case 2:     description = "The database file is not readable.";
                            break;
                                        
                case 3:     description = "The database file is not writable.";
                            break;
                            
                case 4:     description = "Could not find the geolocation database (MaxMind).";
                            break;
                            
                case 5:     description = "The geolocation database (MaxMind) is not readable.";
                            break;
                            
                case 6:     description = "Could not find the geolocation database (IP2Location).";
                            break;
                
                case 7:     description = "The geolocation database (IP2Location) is not readable.";
                            break;
                            
                default:    description = "Unknown error code (" + error_code + ").";
            }
            
            return description;
        }
        
        /*
         * Retrieves the version number of the last used SURFmap application
         * on this particular system. This is used to determine whether someone
         * has upgrade to a newer version.
         */
        function retrieve_last_used_version_number () {
            if (session_data['use_db']) {
                $.ajax({
                    url: 'json/getmiscvalue.php',
                    data: {
                        params: [ 'last_used_version' ]
                    },
                    success: function(data) {
                        if (data.status == 0) { // Success
                            var last_used_version = data.values[0].value;
                    
                            if (last_used_version != version) {
                                show_info('about');
                        
                                // Store new version number
                                $.ajax({
                                    url: 'json/storemiscvalue.php',
                                    data: {
                                        params: {
                                            'last_used_version': version
                                        }
                                    },
                                    success: function(data) {
                                        if (data.status == 0) {
                                            /* Set cookie so that last used version number is not
                                             * retrieved another time within the same session
                                             */
                                            update_cookie_value('SURFmap', 'last_used_version_number_retrieved', 1);
                                        } else {
                                            show_error(817, data.status_message);
                                        }
                                    }
                                });
                            }
                        } else {
                            show_error(812, data.status_message);
                        }
                    }
                });
            }
        }
        
        /*
         * Writes client-related information to syslog. This method needs to stay
         * in index.php (in contrast to the other debug logging actions in event.js),
         * since it contains several PHP statements.
         */
        function log_system_information () {
            var log_data = [
                "Application version: " + version,
                "PHP version: <?php echo phpversion(); ?>",
                "PHP loaded extensions:  <?php echo implode(', ', get_loaded_extensions()); ?>",
                "CLient Web browser: " + navigator.userAgent,
                "CLient Web browser cookies enabled: " + navigator.cookieEnabled
            ]
            
            $.ajax({
                url: 'json/writetosyslog.php',
                data: {
                    params: {
                        'type': "debug",
                        'lines' : log_data
                    }
                },
                success: function(data) {
                    if (data.status != 0) { // Failure
                        show_error(816, data.status_message);
                    }
                }
            });
        }
    </script>
</body>
</html>
