/******************************
 # panel.js [SURFmap]
 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *******************************/
    
    /* 
     * Performs all menu panel initialization tasks that can be performed
     * without the session data (i.e. before session data retrieval has completed).
     */
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
    
    /* 
     * Performs all menu panel initialization tasks that can only be performed
     * with the session data (i.e. after session data retrieval has completed).
     */
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
        
        // Auto-refresh
        $('#auto-refresh').click(function (event) {
            if ($('#auto-refresh').is(':checked')) {
                $(document).trigger('session_data_changed', { 'refresh': 1 } );
                store_session_data_handle = setInterval(function () {
                    // Wait until all connections to server are closed (and all session data is written to server)
                    if ($.active == 0) {
                        clearInterval(store_session_data_handle);
                        auto_refresh_handle = setInterval(function () {
                            $(document).trigger('load_session_data', { 'update_time_period': 1 });
                        }, constants['refresh_interval'] * 1000);
                
                        // Trigger refresh immediately when 'auto-refresh' is enabled during current session
                        $(document).trigger('load_session_data', { 'update_time_period': 1 });
                    }
                }, 500);
            } else {
                $(document).trigger('session_data_changed', { 'refresh': 0 } );
                clearInterval(auto_refresh_handle);
            }
        });
        
        if (session_data['refresh'] == 1) {
            $('#auto-refresh').prop('checked', 1);
            auto_refresh_handle = setInterval(function () {
                $(document).trigger('load_session_data', { 'update_time_period': 1 });
            }, constants['refresh_interval'] * 1000);
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
            maxDateTime:    new Date(),
            stepMinute:     5,
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
                        || new_date_time.getMonth() != parseInt(session_data[selector_date].substring(4,6)) - 1 // Months are zero-indexed
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
        
        var new_date_1 = new Date(
                session_data['date1'].substring(0,4),
                session_data['date1'].substring(4,6) - 1, // Months are zero-indexed
                session_data['date1'].substring(6,8),
                session_data['hours1'],
                session_data['minutes1'],
                0 // milliseconds
        );
        var new_date_2 = new Date(
                session_data['date2'].substring(0,4),
                session_data['date2'].substring(4,6) - 1, // Months are zero-indexed
                session_data['date2'].substring(6,8),
                session_data['hours2'],
                session_data['minutes2'],
                0 // milliseconds
        );
        
        $('#date_start').datetimepicker('setDate', new_date_1);
        $('#date_end').datetimepicker('setDate', new_date_2);
        
        /* Set date/time twice because of a bug in date/time selector that causes
         * minutes not to be updated after the first action. See copy_date_time_selector
         * for more details.
         */
        if ($('#date_start').datetimepicker('getDate') != new_date_1) {
            $('#date_start').datetimepicker('setDate', new_date_1);
        }
        if ($('#date_end').datetimepicker('getDate') != new_date_2) {
            $('#date_end').datetimepicker('setDate', new_date_2);
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
     * Updates the date/time selectors of the menu panel.
     */
    function update_panel () {
        var new_date_1 = new Date(
                session_data['date1'].substring(0,4),
                session_data['date1'].substring(4,6) - 1, // Months are zero-indexed
                session_data['date1'].substring(6,8),
                session_data['hours1'],
                session_data['minutes1'],
                0 // milliseconds
        );
        var new_date_2 = new Date(
                session_data['date2'].substring(0,4),
                session_data['date2'].substring(4,6) - 1, // Months are zero-indexed
                session_data['date2'].substring(6,8),
                session_data['hours2'],
                session_data['minutes2'],
                0 // milliseconds
        );
        
        $('#date_start').datetimepicker('setDate', new_date_1);
        $('#date_end').datetimepicker('setDate', new_date_2);
        
        /* Set date/time twice because of a bug in date/time selector that causes
         * minutes not to be updated after the first action. See copy_date_time_selector
         * for more details.
         */
        if ($('#date_start').datetimepicker('getDate') != new_date_1) {
            $('#date_start').datetimepicker('setDate', new_date_1);
        }
        if ($('#date_end').datetimepicker('getDate') != new_date_2) {
            $('#date_end').datetimepicker('setDate', new_date_2);
        }
    }
