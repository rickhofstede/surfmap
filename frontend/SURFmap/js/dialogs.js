/******************************
 # dialogs.js [SURFmap]
 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *******************************/

    /*
     * Prepares a jQuery warning dialog.
     * Parameters:
     *       code - error code
     *       optional_message - optional error message (mostly used for server messages (AJAX/JSON))
     */  
    function show_warning (code, optional_message) {
        // Close processing message dialog before showing the warning
        if ($('#loading_dialog').dialog('isOpen')) {
            $('#loading_dialog').dialog('close');
        }
        
        // If error dialog is already open, queue the new dialog
        if ($('#warning_dialog').dialog('isOpen')) {
            var new_warning_dialog = {
                'code':             code,
                'optional_message': optional_message
            };
            warning_dialog_queue.push(new_warning_dialog);
        } else {
            $('#warning_dialog').empty();
        
            var message = "";
            switch (code) {
                case 1:     message = "You are running Microsoft Internet Explorer. Please note that SURFmap has been optimized for Mozilla Firefox, Google Chrome and Apple Safari.";
                            break;
                        
                case 2:     message = "As a consequence of the applied filters, no flow data can be shown. Please specify different filters.";
                            break;
            
                default:    message = "An unknown warning occured.";
                            break;
            }
            $('#warning_dialog').append("<p><span class=\"ui-icon ui-icon-info\" style=\"float: left; margin: 0 7px 50px 0;\"></span>" + message + "</p>");
        
            if (optional_message != undefined) {
                $('#warning_dialog').append("<p>Server message: <span style=\"font-style:italic\">" + optional_message + "</span>");
            }
        
            $('#warning_dialog').dialog({
                buttons: {
                    OK: function() {
                        $(this).dialog('close');
                    }
                },
                close: function (event, ui) {
                    if (warning_dialog_queue.length > 0) {
                        var new_warning_dialog = warning_dialog_queue.shift();
                        show_warning(new_warning_dialog.code, new_warning_dialog.optional_message);
                    }
                },
                closeOnEscape: true,
                height: 'auto',
                modal: true,
                position: 'center',
                resizable: false,
                stack: true,
                title: 'Warning',
                width: 'auto'
            }).dialog('open');
        }
    }

    /*
     * Prepares a jQuery error dialog.
     * Parameters:
     *       code - error code
     *       optional_message - optional error message (mostly used for server messages (AJAX/JSON))
     */  
    function show_error (code, optional_message) {
        // Close processing message dialog before showing the error
        if ($('#loading_dialog').dialog('isOpen')) {
            $('#loading_dialog').dialog('close');
        }
        
        // If error dialog is already open, queue the new dialog
        if ($('#error_dialog').dialog('isOpen')) {
            var new_error_dialog = {
                'code':             code,
                'optional_message': optional_message
            };
            error_dialog_queue.push(new_error_dialog);
        } else {
            $('#error_dialog').empty();
        
            var message = "";
            switch (code) {
                // case 1:     message = "The flow filter you provided does not adhere to the expected syntax.<br /><br /> \
                //                     <b>Filter</b>: " + flowFilter + "<br /> \
                //                     <b>Error message</b>: " +  errorMessage + "</br /><br /> \
                //                     Please check <a href='http://nfdump.sourceforge.net/' style='text-decoration:underline;' target='_blank'>http://nfdump.sourceforge.net/</a> for the filter syntax.";
                //             break;
                //         
                // case 2:     // The first (normal) selected date/time is invalid.
                //             message = "The selected date/time window (" + originalDate1Window + " " + originalTime1Window + ") does not exist.<br /><br /> \
                //                     The last available/valid time window will be selected.";
                //             break;
                //         
                // case 3:     // The second (time range) selected date/time is invalid.
                //             message = "The (second) selected date/time window (" + originalDate2Window + " " + originalTime2Window + ") does not exist.<br /><br /> \
                //                     The last available/valid time window will be selected.";
                //             break;
                //         
                // case 4:     // The second (time range) selected date/time is invalid.
                //             message = "Both selected date/time windows (" + originalDate1Window + " " + originalTime1Window + " - " + originalDate2Window + " " + originalTime2Window + ") do not exist.<br /><br /> \
                //                     The last available/valid time window will be selected.";
                //             break;
                //                 
                // case 5:     message = "No NetFlow data has been found for the selected profile, source and filter. Please change your settings.";
                //             break;
                //         
                // case 6:     message = "You have an error in your configuration. <br /><br /><b>Error message</b>: " +  errorMessage;
                //             break;
                //         
                // case 7:     message = "The geo filter you provided does not adhere to the expected syntax.<br /><br /> \
                //                     <b>Filter</b>: " + geoFilter + "<br /> \
                //                     <b>Error message</b>: " +  errorMessage + "</br /><br /> \
                //                     Please check the SURFmap manual for the filter syntax.";
                //             break;
                //             
                // case 8:     message = "You have killed your flow query. Please select another query.";
                //             break;
                //         
                // case 9:     message = "The NfSen session has not been set properly. Please start SURFmap from the 'Plugins' tab in NfSen.";
                //             break;
            
                // AJAX/JSON communication error codes
                case 800:   message = "An error occurred while communicating with your Web server. Check your network connectivity and try again.";
                            break;
            
                case 801:   message = "Could not load configuration.";
                            break;
                        
                case 802:   message = "Could not load NfSen configuration.";
                            break;
                        
                case 803:   message = "Could not load session data.";
                            break;
            
                case 804:   message = "Could not retrieve flow data.";
                            break;
                                    
                case 805:   message = "Could not retrieve geolocation data.";
                            break;
                        
                case 806:   message = "Could not retrieve geocoder data.";
                            break;
                        
                case 807:   message = "Could not store session data.";
                            break;
                        
                case 808:   message = "Could not retrieve geocoder data (server).";
                            break;
                        
                case 809:   message = "Could not store geocoder data.";
                            break;
                        
                case 810:   message = "Could not retrieve backend status.";
                            break;
                        
                case 811:   message = "Could not retrieve extensions.";
                            break;
                        
                case 812:   message = "Could not retrieve last used version number.";
                            break;
                        
                case 812:   message = "Could not update last used version number.";
                            break;
                        
                case 813:   message = "Could not load configuration (constants).";
                            break;
                        
                case 814:   message = "Could not apply geo filter.";
                            break;
                        
                case 815:   message = "Could not resolve hostname(s).";
                            break;
                        
                case 816:   message = "Could not write to syslog.";
                            break;
            
                // Client-side-only error codes
                case 996:   message = "You have specified an invalid map center. Please check your configuration.";
                            break;
                            
                case 997:   message = "You have selected an invalid time range. The end time should come after the begin time.";
                            break;
            
                case 998:   message = "You have limited the number of selected flow records to 0. Please enter a number of flow records > 0.";
                            break;
                        
                case 999:   message = "You have to select at least one source to continue.";
                            break;
                        
                default:    message = "An unknown error occured.";
                            break;
            }
        
            $('#error_dialog').append("<p><span class=\"ui-icon ui-icon-alert\" style=\"float: left; margin: 0 7px 50px 0;\"></span>" + message + "</p>");
        
            if (optional_message != undefined) {
                $('#error_dialog').append("<p>Server message: <span style=\"font-style:italic\">" + optional_message + "</span>");
            }
        
            $('#error_dialog').dialog({
                buttons: {
                    OK: function() {
                        $(this).dialog('close');
                    }
                },
                close: function (event, ui) {
                    if (error_dialog_queue.length > 0) {
                        var new_error_dialog = error_dialog_queue.shift();
                        show_error(new_error_dialog.code, new_error_dialog.optional_message);
                    }
                },
                closeOnEscape: true,
                height: 'auto',
                modal: false,
                position: 'center',
                resizable: false,
                stack: true,
                title: 'Error',
                width: 'auto'
            }).dialog('open');
        }
    }

    function show_info (type) {
        var text;
        $('#info_dialog').empty();
        
        if (type == 'about') {
            text = 'SURFmap has been developed by:<br /><br /> \
                    Rick Hofstede<br /> \
                    <i>University of Twente, The Netherlands</i><br /><hr /> \
                    Included third-party software: \
                    <ul> \
                        <li><a href=\"http://www.erichynds.com/jquery/jquery-ui-multiselect-widget/\" target=\"_blank\" style=\"text-decoration:underline;\">jQuery UI MultiSelect Widget</a> / Eric Hynds</li> \
                        <li><a href=\"http://trentrichardson.com/examples/timepicker/\" target=\"_blank\" style=\"text-decoration:underline;\">jQuery UI Datepicker</a> / Trent Richardson</li> \
                        <li><a href="https://github.com/gabceb/jquery-browser-plugin" target="_blank" style=\"text-decoration:underline;\">jQuery Browser</a></li> \
                        <li><a href="https://github.com/carhartl/jquery-cookie" target="_blank" style=\"text-decoration:underline;\">jQuery.cookie</a> / Klaus Hartl</li> \
                        <li><a href="https://github.com/douglascrockford/JSON-js" target="_blank" style=\"text-decoration:underline;\">JSON-js</a> / Douglas Crockford</li> \
                    </ul><hr /> \
                    SURFmap is available on <a href=\"http://sourceforge.net/p/surfmap\" target=\"_blank\" style=\"text-decoration:underline;\">SourceForge</a> \
                    and is distributed under the <a href=\"javascript:show_info(\'license\')\" style=\"text-decoration:underline;\">BSD license</a>.<br /><br /> \
                    Special thanks to Pavel Celeda from INVEA-TECH, for his valuable contributions.<br /><br />';
            $('#info_dialog').append(text);
            
            var footer = $('<div />', { 'id': 'info_dialog_footer' });
            
            // Version
            footer.append($('<span />').css({
                'float':        'left',
                'padding-top':  '2px'
            }).text('Application version: ' + version));
            
            // Update check
            var update_type = (version.indexOf('dev') == -1) ? 'stable' : 'dev';
            var update_result = $('<img />').css({
                'float':        'left',
                'width':        '20px',
                'margin-left':  '5px'
            });
            
            var current_version = version.substring(1, version.indexOf(' '));
            
            $.ajax({
                url: 'json/getversion.php',
                data: {
                    params: {
                        'current_version': current_version,
                        'type': update_type,
                        'user_agent': navigator.userAgent
                    }
                },
                success: function(data) {
                    if (data.status == 0) { // Success
                        if (current_version >= data.version) {
                            update_result.attr('src', 'images/check.gif').attr('title', 'SURFmap is up-to-date.');
                        } else {
                            update_result.attr('src', 'images/information.gif').attr('title', 'A newer version of SURFmap is available for download at http://surfmap.sf.net/.');
                        }
                    }
                }
            });
            
            footer.append(update_result);
            
            // Logo
            var logo = $('<img />').css({
                'float':    'right',
                'width':    '90px'
            });
            if (config['geolocation_db'] == "IP2Location") {
                logo.attr('src', 'images/ip2location.gif').attr('alt', 'IP2Location');
            } else if (config['geolocation_db'] == "MaxMind") {
                logo.attr('src', 'images/maxmind.png').attr('alt', 'MaxMind');
            }
            footer.append(logo);
            
            $('#info_dialog').append(footer);
            $('#info_dialog').dialog({
                closeOnEscape: true,
                height: 'auto',
                modal: true,
                position: 'center',
                resizable: false,
                stack: true,
                title: 'About',
                width: 'auto'
            }).dialog('open');
        } else if (type == 'license') {
            $('#info_dialog').load('license.html #license', function() {
                $('#info_dialog').dialog({
                    closeOnEscape: true,
                    height: 'auto',
                    modal: true,
                    position: 'center',
                    resizable: false,
                    stack: true,
                    title: 'License',
                    width: 'auto'
                }).dialog('open');
            });
        } else if (type == 'help') {
            $('#info_dialog').html('Welcome to the SURFmap help. Some main principles of SURFmap are explained here.<br /><br /> \
                    <table id=\"help\"> \
                        <tr> \
                            <td class=\"help_item\">Marker</td> \
                            <td>Markers represent hosts and show information about them, such as IPv4 addresses and the country, region and city they\'re located in. A green marker indicates the presence of a flow of which the source and destination are located \'inside\' the same marker.<hr /></td> \
                        </tr> \
                        <tr> \
                            <td class=\"help_item\">Line</td> \
                            <td>Lines represent a flow between two hosts (so between markers) and show information about that flow, like the geographical information of the two end points, the exchanged amount of packets, octets and throughput per flow.<hr /></td> \
                        </tr> \
                        <tr> \
                            <td class=\"help_item\">Zoom levels table</td> \
                            <td>This tables shows the current zoom level. The four zoom levels are also clickable, so that you can zoom in or out to a particular zoom level directly.<hr /></td> \
                        </tr> \
                        <tr> \
                            <td class=\"help_item\">NfSen options</td> \
                            <td>The main NfSen options - <i>List Flows</i> or <i>Stat TopN</i> - can be set here. The first option lists the first N flows of the selected time slot (N and the selected time slot will be discussed later). <i>Stat TopN</i> shows top N statistics about the network data in the selected time slot. The value of N can be set in the <i>Limit to</i> field, while the time slot can be set in the <i>Begin</i> and <i>End</i> fields.</td> \
                        </tr> \
                    </table>');
            $('#info_dialog').dialog({
                closeOnEscape: true,
                height: 'auto',
                modal: false,
                position: 'center',
                resizable: false,
                stack: true,
                title: 'Help',
                width: '500px'
            }).dialog('open');
        } else if (type == 'flow_details') {
            var field_names = [];
            field_names['ipv4_src']  = 'Src. address';
            field_names['ipv4_dst']  = 'Dst. address';
            field_names['port_src']  = 'Src. port';
            field_names['port_dst']  = 'Dst. port';
            field_names['protocol']  = 'Protocol';
            field_names['packets']   = 'Packets';
            field_names['octets']    = 'Octets';
            field_names['duration']  = 'Duration';
            field_names['flows']     = 'Flows';
            field_names['location_src'] = 'Source location';
            field_names['location_dst'] = 'Destination location';
            
            field_count = 11 + extensions.length;
            
            protocols = [];
            protocols[1] = 'ICMP';
            protocols[2] = 'IGMP';
            protocols[6] = 'TCP';
            protocols[17] = 'UDP';
            protocols[47] = 'GRE';
            
            // TODO Add support for extensions
            
            var body = $('<tbody/>');
            var header_line = $('<tr/>', {'class': 'header'});
            var key_index = 0;
            for (var key in field_names) {
                var element = $('<th/>').text(field_names[key]);
                
                if (key_index == 0) { // First field
                    element.addClass('left');
                } else if (key_index == field_count - 1) { // Last field
                    element.addClass('right');
                }
                
                if (key == 'ipv4_src') {
                    element.addClass('src_column');
                } else if (key == 'ipv4_dst') {
                    element.addClass('dst_column');
                }
                
                header_line.append(element);
                key_index++;
            }
            body.append(header_line);
            
            var line_class = 'odd';
            $.each(flow_data, function (flow_index, flow_item) {
                var body_line = $('<tr/>', {'class': line_class});
                
                for (var key in field_names) {
                    var field = $('<td/>');
                    
                    if (key == 'ipv4_src') {
                        field.addClass('src_column');
                    } else if (key == 'ipv4_dst') {
                        field.addClass('dst_column');
                    }
                    
                    if (key == 'protocol') {
                        // Replace protocol number by protocol name, if known
                        if (protocols[flow_item[key]] != undefined) {
                            field.text(protocols[flow_item[key]]);
                        } else {
                            field.text(flow_item[key]);
                        }
                    } else if (key == 'location_src') {
                        var location_string = format_location_name(flow_item['src_country']);
                        
                        if (flow_item['src_region'] != "(UNKNOWN)") {
                            location_string += ", " + format_location_name(flow_item['src_region']);
                        }
                        
                        if (flow_item['src_city'] != "(UNKNOWN)") {
                            location_string += ", " + format_location_name(flow_item['src_city']);
                        }
                        
                        field.text(location_string).css('padding-right', '5px');
                    } else if (key == 'location_dst') {
                        var location_string = format_location_name(flow_item['dst_country']);
                        
                        if (flow_item['dst_region'] != "(UNKNOWN)") {
                            location_string += ", " + format_location_name(flow_item['dst_region']);
                        }
                        
                        if (flow_item['dst_city'] != "(UNKNOWN)") {
                            location_string += ", " + format_location_name(flow_item['dst_city']);
                        }
                        
                        field.text(location_string).css('padding-left', '5px');
                    } else {
                        field.text(flow_item[key]);
                    }
                    
                    body_line.append(field);
                }
                
                body.append(body_line);
                line_class = (line_class == 'odd') ? 'even' : 'odd';
            });
            
            $('#info_dialog').html("<table class=\"flow_info_table\">" + body.html() + "</table>");
            $('#info_dialog').dialog({
                closeOnEscape: true,
                height: 'auto',
                modal: false,
                position: 'center',
                resizable: true,
                stack: true,
                title: 'Flow details',
                width: 'auto'
            }).dialog('open');
        }
    }
    
    /*
     * Shows a loading message.
     *
     * Parameters:
     *       text - Text shown as part of the loading message (optional)
     */ 
    function show_loading_message (text) {
        if (!$('#loading_dialog').dialog('isOpen')) {
            $('#loading_dialog').html("<div id='processing' style='text-align:center; clear:both;'> \
                    <img src='images/load.gif' alt='Loading SURFmap'><br /> \
                    <div style='font-size:8pt; margin-top:15px;'> \
                    <p id='loading_text_upper'>Loading...</p> \
                    <p id='loading_text_lower'></p> \
                    </div> \
                </div>");
            
            $('#loading_dialog').dialog({
                closeOnEscape: false,
                dialogClass: 'dialog_no_title',
                modal: true,
                position: 'center',
                resizable: false,
                stack: true,
                width: 250,
                close: function (event, ui) {
                    clearTimeout(loading_message_timeout_handle);
                }
            }).dialog('open');
            
            // loading_message_timeout_handle has been declared in index.php
            loading_message_timeout_handle = setTimeout(
                function () {
                    if ($('#loading_dialog').dialog('isOpen')) {
                        $('#loading_dialog').dialog('close');
                        $('#loading_text_upper').text("Your request is still being \
                                processed by the server. Please don't refresh the page, \
                                as that will result in serious performance degradation \
                                of your server.");
                        $('#loading_text_lower').hide();
                        $('#loading_dialog').dialog({
                            closeOnEscape: false,
                            dialogClass: 'dialog_no_title',
                            modal: true,
                            position: 'center',
                            resizable: false,
                            stack: true,
                            width: 450,
                            close: function (event, ui) {
                                clearTimeout(loading_message_timeout_handle);
                            }
                        }).dialog('open');
                    }
                }, 20000);
        }
        
        if (text == '' || text == undefined) {
            $('#loading_dialog').dialog('option', 'height', 90);
            $('#loading_text_lower').hide();
        } else {
            if (text.charAt(text.length - 1) != ".") {
                text += "...";
            }
            $('#loading_text_lower').text(text);
            if (!$('#loading_text_lower').is('visible')) {
                $('#loading_dialog').dialog('option', 'height', 115);
                $('#loading_text_lower').show();
            }
        }
    }
