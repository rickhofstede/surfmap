/******************************
 # maputil.js [SURFmap]
 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *******************************/
        
   /*
    * Creates Polylines according to the specified coordinates and puts 
    * the specified text into the line's information window.
    * Parameters:
    *     coordinate1 - one end point of the line
    *     coordinate2 - one end point of the line
    *     text - the text that has to be put into the line's information window
    *     color - color of the line (used for line color classification)
    *     weight - width of the line (in pixels)
    */
    function create_line (coordinate1, coordinate2, text, color, weight) {
        var lineOptions = {
            geodesic: true,
            path: [coordinate1, coordinate2],
            strokeColor: color,
            strokeOpacity: 0.7,
            strokeWeight: weight
        }
        var line = new google.maps.Polyline(lineOptions);
            
        google.maps.event.addListener(line, "click", function(event) {
            map.setCenter(event.latLng);
            info_window.close();
                
            if (event.latLng == undefined) {
                // When click_random_line() is used, a google.maps.LatLng object is passed as the 'event' parameter
                info_window.setPosition(event);
            } else {
                info_window.setPosition(event.latLng);
            }
                
            info_window.setContent(text);
            info_window.open(map);
            
            // Find index of line that has been clicked
            var flow_details_button = $(".flow_info_table a:contains(Flow Details)");
            var lines_index = parseInt(flow_details_button.attr('id').substr(flow_details_button.attr('id').lastIndexOf("-") + 1));
            var associated_flow_indices = lines[lines_index].associated_flow_indices;
                        
            // Find IP addresses to be resolved to hostnames
            var IP_addresses = [];
            $.each(associated_flow_indices, function () {
                // Only add IP address if it is not already present
                if (jQuery.inArray(flow_data[this]['ipv4_src'], IP_addresses) == -1) {
                    IP_addresses.push(flow_data[this]['ipv4_src']);
                }
                if (jQuery.inArray(flow_data[this]['ipv4_dst'], IP_addresses) == -1) {
                    IP_addresses.push(flow_data[this]['ipv4_dst']);
                }
            });
            
            // Check which IP addresses have already been resolved and remove them from the list
            var IP_addresses_to_be_removed = [];
            $.each(IP_addresses, function (address_index, address) {
                if (resolved_hostnames != undefined) {
                    $.each(resolved_hostnames, function (index, tuple) {
                        if (tuple.address == address) {
                            IP_addresses_to_be_removed.push(address_index);
                            
                            // Add (previously resolved) hostname as tooltip to IP address
                            $('#map_canvas .flow_info_table td:contains(' + tuple.address + ')').attr('title', tuple.hostname);
                            
                            return false;
                        }
                    });
                }
            });
            
            // Perform IP address removal
            $.each(IP_addresses_to_be_removed, function (address_counter, address_index) {
                // Indices need to be compensated for removal
                IP_addresses.splice(address_index - address_counter, 1);
            });
            
            // Resolve hostnames
            if (config['resolve_hostnames'] && IP_addresses.length > 0) {
                $.ajax({
                    url: 'json/resolvehostnames.php',
                    data: { 
                        params: IP_addresses
                    },
                    success: function(data) {
                        if (data.status == 0) { // Success
                            if (resolved_hostnames == undefined) resolved_hostnames = [];
                            
                            $.each(data.hostnames, function (index, tuple) {
                                resolved_hostnames.push(tuple);
                                
                                // Add hostnames as tooltip to IP addresses
                                $('#map_canvas .flow_info_table td:contains(' + tuple.address + ')').attr('title', tuple.hostname);
                            });
                        } else {
                            show_error(815, data.status_message);
                        }
                    },
                    error: function() {
                        show_error(800);
                    }
                });
            }
            
            // Attach click handler for opening Flow Details
            flow_details_button.click(function (event) {
                show_flow_details(associated_flow_indices);
            });
            
            // Make all instances of 'Not available' in information windows italic
            $('.flow_info_table td:contains(Not available)').css('font-style', 'italic');
        });
            
        return line;
    }
    
   /*
    * Creates Markers according to the specified coordinates and the specified text
    * into the marker's information window.
    * Parameters:
    *     coordinates - the coordinates on which the marker should be created
    *     title - tooltip to be shown on rollover
    *     text - the text that has to be put into the marker's information window
    *     color - can be 'green' or 'red' (undefined results in 'red')
    */          
    function create_marker (coordinates, title, text, color) {
        var marker_options = {
                position: coordinates,
                title: title
        }
        if (color == 'green') {
            var green_marker = new google.maps.MarkerImage("images/markers/green-dot.png", new google.maps.Size(30, 30));
            marker_options['icon'] = green_marker;
        } else if (color == 'blue') {
            var blue_marker = new google.maps.MarkerImage("images/markers/blue-dot.png", new google.maps.Size(30, 30));
            marker_options['icon'] = blue_marker;
        }
        
        var marker = new google.maps.Marker(marker_options);

        google.maps.event.addListener(marker, "click", function(event) {
            map.setCenter(event.latLng);
            info_window.close();
            info_window.setContent(text);
            info_window.open(map, marker);
            
            // Find IP addresses to be resolved to hostnames
            var IP_addresses = [];
            $.each($('#map_canvas .flow_info_table td[class*=ip_address]:visible'), function () {
                // Only add IP address if it is not already present
                if (jQuery.inArray($(this).text(), IP_addresses) == -1) {
                    IP_addresses.push($(this).text());
                }
            });
            
            // Check which IP addresses have already been resolved and remove them from the list
            var IP_addresses_to_be_removed = [];
            $.each(IP_addresses, function (address_index, address) {
                if (resolved_hostnames != undefined) {
                    $.each(resolved_hostnames, function (index, tuple) {
                        if (tuple.address == address) {
                            IP_addresses_to_be_removed.push(address_index);
                            
                            // Add (previously resolved) hostname as tooltip to IP address
                            $('.flow_info_table td:contains(' + tuple.address + ')').attr('title', tuple.hostname);
                            
                            return false;
                        }
                    });
                }
            });
            
            // Perform IP address removal
            $.each(IP_addresses_to_be_removed, function (address_counter, address_index) {
                // Indices need to be compensated for removal
                IP_addresses.splice(address_index - address_counter, 1);
            });
            
            // Resolve hostnames
            if (config['resolve_hostnames'] && IP_addresses.length > 0) {
                $.ajax({
                    url: 'json/resolvehostnames.php',
                    data: { 
                        params: IP_addresses
                    },
                    success: function(data) {
                        if (data.status == 0) { // Success
                            if (resolved_hostnames == undefined) resolved_hostnames = [];
                            
                            $.each(data.hostnames, function (index, tuple) {
                                resolved_hostnames.push(tuple);
                                
                                // Add hostnames as tooltip to IP addresses
                                $('#map_canvas .flow_info_table td:contains(' + tuple.address + ')').attr('title', tuple.hostname);
                            });
                        } else {
                            show_error(815, data.status_message);
                        }
                    },
                    error: function() {
                        show_error(800);
                    }
                });
            }
                
            // Make all instances of 'Not available' in information windows italic
            $('.flow_info_table td:contains(Not available)').css('font-style', 'italic');
        });

        return marker;
    }
    
    /*
     * Generates the HTML (table) code for a line, based on the specified
     * line entries.
     * Parameters:
     *     lines_index - index of line in 'lines' array
     *     line_entries - entry data structure, of which the contents need to
     *         be included in the information window
     */ 
    function generate_line_info_window_contents (lines_index, line_entries) {
        var body = $('<tbody/>');
        var header_line = $('<tr/>', {'class': 'header'});
        header_line.append($('<th/>', {'class': 'src_column left'}).text('Source'));
        header_line.append($('<th/>', {'class': 'dst_column'}).text('Destination'));
        header_line.append($('<th/>').text('Flows'));
        header_line.append($('<th/>').text('Packets'));
        header_line.append($('<th/>').text('Bytes'));
        header_line.append($('<th/>', {'class': 'right'}).text('Throughput'));
        body.append(header_line);
        
        var line_class = 'odd';
        $.each(line_entries, function (entry_index, entry) {
            var body_line = $('<tr/>', {'class': line_class});
            
            // Country names can never be undefined, so no need to check it
            body_line.append($('<td/>', {'class': 'src_column'}).text(format_location_name(entry.src_text.country)));
            body_line.append($('<td/>', {'class': 'dst_column'}).text(format_location_name(entry.dst_text.country)));
            body_line.append($('<td/>').text(apply_SI_Scale(entry.flows)));
            body_line.append($('<td/>').text(apply_SI_Scale(entry.packets)));
            body_line.append($('<td/>').text(apply_SI_Scale(entry.octets)));
            
            var throughput = entry.octets / entry.duration;
            if (throughput == 'Infinity') throughput = 'Not available';
            body_line.append($('<td/>').text(format_throughput(throughput)));
            body.append(body_line);
            
            if (!(entry.src_text.region == undefined && entry.dst_text.region == undefined)) {
                body_line = $('<tr/>', {'class': line_class});
                
                if (entry.src_text.region == undefined) {
                    body_line.append($('<td/>'));
                } else {
                    body_line.append($('<td/>', {'class': 'src_column'}).text(format_location_name(entry.src_text.region)));
                }
            
                if (entry.dst_text.region == undefined) {
                    body_line.append($('<td/>'));
                } else {
                    body_line.append($('<td/>', {'class': 'dst_column'}).text(format_location_name(entry.dst_text.region)));
                }
            
                body_line.append($('<td/><td/><td/><td/>'));
                body.append(body_line);
            }
            
            if (!(entry.src_text.city == undefined && entry.dst_text.city == undefined)) {
                body_line = $('<tr/>', {'class': line_class});
                
                if (entry.src_text.city == undefined) {
                    body_line.append($('<td/>'));
                } else {
                    body_line.append($('<td/>', {'class': 'src_column'}).text(format_location_name(entry.src_text.city)));
                }
            
                if (entry.dst_text.city == undefined) {
                    body_line.append($('<td/>'));
                } else {
                    body_line.append($('<td/>', {'class': 'dst_column'}).text(format_location_name(entry.dst_text.city)));
                }
            
                body_line.append($('<td/><td/><td/><td/>'));
                body.append(body_line);
            }
            
            if (!(entry.src_text.ip_address == undefined && entry.dst_text.ip_address == undefined)) {
                body_line = $('<tr/>', {'class': line_class});
                
                if (entry.src_text.ip_address == undefined) {
                    body_line.append($('<td/>'));
                } else {
                    body_line.append($('<td/>', {'class': 'src_column ip_address'}).text(entry.src_text.ip_address));
                }
            
                if (entry.dst_text.ip_address == undefined) {
                    body_line.append($('<td/>'));
                } else {
                    body_line.append($('<td/>', {'class': 'dst_column ip_address'}).text(entry.dst_text.ip_address));
                }
            
                body_line.append($('<td/><td/><td/><td/>'));
                body.append(body_line);
            }
            
            line_class = (line_class == 'odd') ? 'even' : 'odd';
        });
        
        var footer = $('<tr/>', {'class': 'footer'});
        
        var column_count = 6;
        var footer_contents = $('<td/>', { 'colspan': column_count });
        var zoom_in = $('<a/>', { 'href': 'Javascript:zoom(0)' }).text('Zoom In');
        var zoom_out = $('<a/>', { 'href': 'Javascript:zoom(1)' }).text('Zoom Out');
        var quick_zoom_in = $('<a/>', { 'href': 'Javascript:quick_zoom(0)' }).text('Quick Zoom In');
        var quick_zoom_out = $('<a/>', { 'href': 'Javascript:quick_zoom(1)' }).text('Quick Zoom Out');
        var flow_details = $('<a/>', { 'id': 'flow_details lines_index-' + lines_index, 'href': 'Javascript:' }).text('Flow Details'); // Click handler is attached in create_line when info_window is opened
        var jump_source = $('<a/>', { 'href': 'Javascript:map.setCenter(lines[' + lines_index + '].point1)' }).text('Jump to source marker');
        var jump_destination = $('<a/>', { 'href': 'Javascript:map.setCenter(lines[' + lines_index + '].point2)' }).text('Jump to destination marker');
        
        footer_contents.append(zoom_in).append(' - ').append(zoom_out).append(' | ');
        footer_contents.append(quick_zoom_in).append(' - ').append(quick_zoom_out).append(' | ').append(flow_details).append('<br/>');
        footer_contents.append(jump_source).append(' - ').append(jump_destination);
        
        footer.append(footer_contents);
        body.append(footer);
        return body.html();
    }

    /*
     * Generates the HTML (table) code for a marker, based on the specified
     * marker entries.
     * Parameters:
     *     marker_entries - entry data structure, of which the contents need to
     *         be included in the information window
     */ 
    function generate_marker_info_window_contents (marker_entries) {
        var body = $('<tbody/>');
        var header_line = $('<tr/>', {'class': 'header'});
        
        if (marker_entries[0].hosts == undefined) { // Host
            header_line.append($('<th/>').text('IP address'));
            header_line.append($('<th/>').text('Flows'));
        } else { // Country, region, city
            header_line.append($('<th/>').text('Location'));
            header_line.append($('<th/>').text('Hosts'));
        }
        
        body.append(header_line);
        
        var line_class = 'odd';
        $.each(marker_entries, function (entry_index, entry) {
            var body_line = $('<tr/>', {'class': line_class});
            
            if (marker_entries[0].hosts == undefined) { // Host
                body_line.append($('<td/>').text(entry.text).addClass('ip_address'));
                body_line.append($('<td/>').text(entry.flows));
            } else { // Country, region, city
                body_line.append($('<td/>').text(format_location_name(entry.text)));
                body_line.append($('<td/>').text(entry.hosts.length));
            }
            
            body.append(body_line);
            
            line_class = (line_class == 'odd') ? 'even' : 'odd';
        });
        
        var footer = $('<tr/>', {'class': 'footer'});
        
        var column_count = 2;
        var footer_contents = $('<td/>', { 'colspan': column_count });
        var zoom_in = $('<a/>', { 'href': 'Javascript:zoom(0)' }).text('Zoom In');
        var zoom_out = $('<a/>', { 'href': 'Javascript:zoom(1)' }).text('Zoom Out');
        var quick_zoom_in = $('<a/>', { 'href': 'Javascript:quick_zoom(0)' }).text('Quick Zoom In');
        var quick_zoom_out = $('<a/>', { 'href': 'Javascript:quick_zoom(1)' }).text('Quick Zoom Out');
        
        footer_contents.append(zoom_in).append(' - ').append(zoom_out).append('<br/>');
        footer_contents.append(quick_zoom_in).append(' - ').append(quick_zoom_out);
        
        footer.append(footer_contents);
        body.append(footer);
        return body.html();
    }

    /*
     * Returns the SURFmap zoom level of the specified Google Maps zoom level.
     * Parameters:
     *      gm_level - the Google Maps zoom level that has to be converted to a SURFmap zoom level
     */
    function get_SM_zoom_level (gm_level) {
        var level = -1;
        
        if (gm_level <= 4) level = 0;                           // Country: 2-4
        else if (gm_level >= 5 && gm_level <= 7) level = 1;     // Region: 5-7
        else if (gm_level >= 8 && gm_level <= 10) level = 2;    // City: 8-10
        else if (gm_level >= 11 && gm_level <= 13) level = 3;   // Host: 11-13
        
        return level;
    }

    /*
     * Returns the Google Maps zoom level of the specified SURFmap zoom level.
     * Parameters:
     *     smZoomLevel - the SURFmap zoom level that has to be converted to a Google Maps zoom level
     */          
    function get_GM_zoom_level (sm_level) {
        if (sm_level == 0) return 2;
        else if (sm_level == 1) return 5;
        else if (sm_level == 2) return 8;
        else return 11;
    }
    
    /*
     * Initializes the Google Maps map object and adds listeners to it.
     */
    function init_map () {
        var map_center = new google.maps.LatLng(
                parseFloat(session_data['map_center'].substring(0, session_data['map_center'].indexOf(","))),
                parseFloat(session_data['map_center'].substring(session_data['map_center'].indexOf(",") + 1)));
        
        if (isNaN(map_center.lat()) || isNaN(map_center.lng())) {
            show_error(996);
        }
            
        map = new google.maps.Map(document.getElementById("map_canvas"), {
            zoom: parseFloat(session_data['zoom_level']),
            minZoom: 2,
            maxZoom: 13,
            center: map_center,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            mapTypeControl: false
        });
        google.maps.event.addListener(map, "click", function () {
            info_window.close();
        });
        google.maps.event.addListenerOnce(map, "bounds_changed", function () {
            /*
             * To make sure that bounds are set after the map has been loaded.
             * If a gray area is present at the top or bottom of the map, change its center.
             * Note that this command is called only once (because of addListenerOnce)
             */
            if (map.getBounds().getNorthEast().lat() > 85.0 || map.getBounds().getSouthWest().lat() < -85.0) {
                var map_center_wo_gray = hide_gray_map_area();
                $(document).trigger('session_data_changed', {
                    'map_center_wo_gray': map_center_wo_gray.lat() + "," + map_center_wo_gray.lng()
                });
            }
        });
        google.maps.event.addListener(map, "dragend", function () {
            $(document).trigger('session_data_changed', { 'map_center': map.getCenter().lat() + "," + map.getCenter().lng() } );
        });
        google.maps.event.addListener(map, "zoom_changed", function () {
            $(document).trigger('session_data_changed', { 'zoom_level': map.getZoom() } );
            
            var old_sm_zoom_level = get_SM_zoom_level(session_data['zoom_level']);
            var new_sm_zoom_level = get_SM_zoom_level(map.getZoom());
            
            if (old_sm_zoom_level != new_sm_zoom_level) {
                info_window.close();
                remove_map_overlays();
                add_map_overlays(new_sm_zoom_level);
                init_legend();
                
                switch (new_sm_zoom_level) {
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
            }
            
            google.maps.event.addListenerOnce(map, "idle", function() {
                var map_center = new google.maps.LatLng(
                        parseFloat(session_data['map_center'].substring(0, session_data['map_center'].indexOf(","))),
                        parseFloat(session_data['map_center'].substring(session_data['map_center'].indexOf(",") + 1)));
                
                // If gray map area is visible
                if (map.getBounds().getNorthEast().lat() > 85.0 || map.getBounds().getSouthWest().lat() < -85.0) { 
                    var map_center_wo_gray = hide_gray_map_area();
                    $(document).trigger('session_data_changed', {
                        'map_center_wo_gray': map_center_wo_gray.lat() + "," + map_center_wo_gray.lng()
                    });
                } else if (map.getCenter() != undefined && session_data['map_center_wo_gray'] != undefined) {
                    var map_center_wo_gray = new google.maps.LatLng(
                            parseFloat(session_data['map_center_wo_gray'].substring(0, session_data['map_center_wo_gray'].indexOf(","))),
                            parseFloat(session_data['map_center_wo_gray'].substring(session_data['map_center_wo_gray'].indexOf(",") + 1)));
                    
                    /*
                     * If the map center was adjusted due to a gray area at the top or bottom of the map, 
                     * change its center again to the actual configured map center.
                     * When called in demo mode, when a random line is clicked by SURFmap, map.getCenter() can be undefined.
                     */
                    if (map.getCenter().equals(map_center_wo_gray)) map.setCenter(map_center);
                }
            });
        });
    }
    
    /*
     * Removes all existing map overlays and adds new ones, based on the old and new zoom levels.
     * Parameters:
     *       new_sm_zoom_level - New/current SURFmap zoom level.
     */  
    function add_map_overlays (new_sm_zoom_level) {
        // Lines
        $.each(lines, function (line_index, line_item) {
            if (line_item.level == new_sm_zoom_level) {
                line_item.obj.setMap(map);
            }
        });
        
        // Markers
        $.each(markers, function (marker_index, marker_item) {
            if (marker_item.level == new_sm_zoom_level) {
                marker_item.obj.setMap(map);
            }
        });
    }
    
    /*
     * Removes existing map overlays.
     * Parameters:
     *      sm_zoom_level - SURFmap zoom level at which overlays should be removed. If undefined, all overlays are removed.
     */
    function remove_map_overlays (sm_zoom_level) {
        if (lines != undefined) {
            $.each(lines, function (line_index, line_item) {
                if (sm_zoom_level == undefined || (sm_zoom_level == line_item.level)) {
                    line_item.obj.setMap(null);
                }
            });
        }
        
        if (markers != undefined) {
            $.each(markers, function (marker_index, marker_item) {
                if (sm_zoom_level == undefined || (sm_zoom_level == marker_item.level)) {
                    marker_item.obj.setMap(null);
                }
            });
        }
    }
    
    /*
     * Fires a 'click' event on a randomly selected line at the current zoom level.
     */     
    function click_random_line () {
        var zoom_level = get_SM_zoom_level(map.getZoom());
        var lines_at_level = [];
        
        // Collect all line objects at the current zoom level
        if (lines != undefined) {
            $.each(lines, function (line_index, line) {
                if (line.level == zoom_level) lines_at_level.push(line);
            });
        
            // Randomly select one line out of the collected lines
            var selected_line = lines_at_level[Math.floor(Math.random() * lines_at_level.length)];
        
            var map_center = new google.maps.LatLng(
                    parseFloat(session_data['map_center'].substring(0, session_data['map_center'].indexOf(","))),
                    parseFloat(session_data['map_center'].substring(session_data['map_center'].indexOf(",") + 1)));
        
            // Measures for distance to map center
            var distance_point1 = Math.abs(selected_line.point1.lat() - map_center.lat()) + Math.abs(selected_line.point1.lng() - map_center.lng());
            var distance_point2 = Math.abs(selected_line.point2.lat() - map_center.lat()) + Math.abs(selected_line.point2.lng() - map_center.lng());
        
            // Calculate which line end point is closest to map center
            if (distance_point1 < distance_point2) {
                google.maps.event.trigger(selected_line.obj, 'click', 
                        new google.maps.LatLng(selected_line.point2.lat(), selected_line.point2.lng())); 
            } else {
                google.maps.event.trigger(selected_line.obj, 'click', 
                        new google.maps.LatLng(selected_line.point1.lat(), selected_line.point1.lng())); 
            }
        }
    }    
    
   /*
    * This function zooms the SURFmap map to a defined zoom level.
    * Parameters:
    *     direction - can be either 0 (in) or 1 (out)
    *     level - the destination zoom level (optional)
    */          
    function zoom (direction, level) {
        if (level == undefined) {
            if (direction == 0) {
                map.setZoom(map.getZoom() + 1);
            } else {
                map.setZoom(map.getZoom() - 1);
            }
        } else {
            map.setZoom(level);
        }
    }
    
    /*
     * This function quick zooms the SURFmap map to the next (SURFmap) zoom level.
     * Parameters:
     *     direction - can be either 0 (in) or 1 (out)
     */ 
    function quick_zoom (direction) {
        var current_level = get_SM_zoom_level(map.getZoom());
        if (direction == 0 && current_level < 3) {
            map.setZoom(5 + (current_level * 3));
        } else if (direction == 1 && current_level > 0) {
            map.setZoom(2 + ((current_level - 1) * 3));
        }
    }
    
    /*
     * Hides a gray map area by changing the map's center and return the map center after algorithm
     * completion (i.e. map center without visible gray areas).
     */
    function hide_gray_map_area () {
        if (map.getBounds().getNorthEast().lat() > 85.0) {
            while (map.getBounds().getNorthEast().lat() > 85.0) {
                map.setCenter(new google.maps.LatLng(map.getCenter().lat() - 0.5, map.getCenter().lng()));
            }
        } else if (map.getBounds().getSouthWest().lat() < -85.0) {
            while (map.getBounds().getNorthEast().lat() > 85.0) {
                map.setCenter(new google.maps.LatLng(map.getCenter().lat() + 0.5, map.getCenter().lng()));
            }
        }
        
        return map.getCenter();
    }
