<?php
/******************************************************
 # getsessiondata.php
 # Author:      Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/
    
    require_once("../config.php");
    require_once("../constants.php");
    header("content-type: application/json");

    if (!session_id()) session_start();
    
    if (!isset($_SESSION['profileinfo'])) {
        $result['status'] = 1;
        $result['status_message'] = "NfSen profile not initialized";
        echo json_encode($result);
        die();
    }
    
    if (isset($_POST['params'])) {
        $nfsen_profile_data_dir = $_POST['params']['nfsen_profile_data_dir'];
    } else {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    $result = array();
    $result['session_data'] = array();

    // Initialize flow_record_count
    if (!isset($_SESSION['SURFmap']['flow_record_count'])) {
        $_SESSION['SURFmap']['flow_record_count'] = $config['default_flow_record_count'];
    }
    
    // Initialize flow_filter
    if (!isset($_SESSION['SURFmap']['flow_filter']) || !isset($_SESSION['SURFmap']['flow_display_filter'])) {
        $_SESSION['SURFmap']['flow_filter'] = '';
            
        // ***** 1. Prepare filters *****
        foreach ($config['internal_domains'] as $key => $value) {
            if (strlen($key) != 0) {
                $internalDomains = explode(";", $key);
                foreach ($internalDomains as $domain) {
                    if (isset($static_filter_internal_domain_traffic)) {
                        $static_filter_internal_domain_traffic .= " and not (src net ".$domain." and dst net ".$domain.")";
                    } else {
                        $static_filter_internal_domain_traffic = "not (src net ".$domain." and dst net ".$domain.")";
                    }
                }
                unset($domain);
            }
        }
        unset($key, $value);

        $static_filter_broadcast_traffic = "not host 255.255.255.255";
        $static_filter_multicast_traffic = "not net 224.0/4";
        $static_filter_ipv6_traffic = "not ipv6";
        $static_filters = array();
            
        // ***** 2. Collect filters if needed *****
        if ($config['hide_internal_domain_traffic'] && isset($static_filter_internal_domain_traffic) && strpos($_SESSION['SURFmap']['flow_filter'], $static_filter_internal_domain_traffic) === false) {
            array_push($static_filters, $static_filter_internal_domain_traffic);
        }
        if (strpos($_SESSION['SURFmap']['flow_filter'], $static_filter_broadcast_traffic) === false) {
            array_push($static_filters, $static_filter_broadcast_traffic);
        }
        if (strpos($_SESSION['SURFmap']['flow_filter'], $static_filter_multicast_traffic) === false) {
            array_push($static_filters, $static_filter_multicast_traffic);
        }
        if (strpos($_SESSION['SURFmap']['flow_filter'], $static_filter_ipv6_traffic) === false) {
            array_push($static_filters, $static_filter_ipv6_traffic);
        }
        
        $combined_static_filter = implode(" and ", $static_filters);
        
        if (sizeof($static_filters) > 0) {
            if ($_SESSION['SURFmap']['flow_filter'] == "") {
                $_SESSION['SURFmap']['flow_filter'] = $combined_static_filter;
            } else {
                $_SESSION['SURFmap']['flow_filter'] .= " and ".$combined_static_filter;
            }
        }
        
        // ***** 3. Remove static filters from display filter *****
        $_SESSION['SURFmap']['flow_display_filter'] = $_SESSION['SURFmap']['flow_filter'];
        if (strpos($_SESSION['SURFmap']['flow_display_filter'], $static_filter_internal_domain_traffic) === 0) {
            $_SESSION['SURFmap']['flow_display_filter'] = str_replace($static_filter_internal_domain_traffic, "", $_SESSION['SURFmap']['flow_display_filter']);
        } else {
            $_SESSION['SURFmap']['flow_display_filter'] = str_replace(" and ".$static_filter_internal_domain_traffic, "", $_SESSION['SURFmap']['flow_display_filter']);
        }
        if (strpos($_SESSION['SURFmap']['flow_display_filter'], $static_filter_broadcast_traffic) === 0) {
            $_SESSION['SURFmap']['flow_display_filter'] = str_replace($static_filter_broadcast_traffic, "", $_SESSION['SURFmap']['flow_display_filter']);
        } else {
            $_SESSION['SURFmap']['flow_display_filter'] = str_replace(" and ".$static_filter_broadcast_traffic, "", $_SESSION['SURFmap']['flow_display_filter']);
        }
        if (strpos($_SESSION['SURFmap']['flow_display_filter'], $static_filter_multicast_traffic) === 0) {
            $_SESSION['SURFmap']['flow_display_filter'] = str_replace($static_filter_multicast_traffic, "", $_SESSION['SURFmap']['flow_display_filter']);
        } else {
            $_SESSION['SURFmap']['flow_display_filter'] = str_replace(" and ".$static_filter_multicast_traffic, "", $_SESSION['SURFmap']['flow_display_filter']);
        }
        if (strpos($_SESSION['SURFmap']['flow_display_filter'], $static_filter_ipv6_traffic) === 0) {
            $_SESSION['SURFmap']['flow_display_filter'] = str_replace($static_filter_ipv6_traffic, "", $_SESSION['SURFmap']['flow_display_filter']);
        } else {
            $_SESSION['SURFmap']['flow_display_filter'] = str_replace(" and ".$static_filter_ipv6_traffic, "", $_SESSION['SURFmap']['flow_display_filter']);
        }
    }
    
    // Initialize geo_filter
    if (!isset($_SESSION['SURFmap']['geo_filter'])) {
        $_SESSION['SURFmap']['geo_filter'] = '';
    }
    
    // Initialize nfsen_option
    if (!isset($_SESSION['SURFmap']['nfsen_option'])) {
        $_SESSION['SURFmap']['nfsen_option'] = $config['default_query_type'];
    }
    
    // Initialize nfsen_option
    if (!isset($_SESSION['SURFmap']['nfsen_stat_order'])) {
        $_SESSION['SURFmap']['nfsen_stat_order'] = $config['default_query_type_stat_order'];
    }
    
    // Initialize nfsen_profile
    $_SESSION['SURFmap']['nfsen_profile'] = $_SESSION['profileswitch'];
    
    // Initialize nfsen_profile_type
    $_SESSION['SURFmap']['nfsen_profile_type'] = ($_SESSION['profileinfo']['type'] & 4) > 0 ? "shadow" : "real";
    
    // Initialize nfsen_all_sources
    if (!isset($_SESSION['SURFmap']['nfsen_all_sources'])) {
        $_SESSION['SURFmap']['nfsen_all_sources'] = array();
        foreach ($_SESSION['profileinfo']['channel'] as $source) {
            array_push($_SESSION['SURFmap']['nfsen_all_sources'], $source['name']);
        }
        unset($source);
    }
    
    // Initialize nfsen_selected_sources
    if (!isset($_SESSION['SURFmap']['nfsen_selected_sources'])) {
        $_SESSION['SURFmap']['nfsen_selected_sources'] = array();
        if (strlen($config['nfsen_default_sources']) > 0) {
            // Check whether configured default sources exist
            foreach (explode(";", $config['nfsen_default_sources']) as $source) {
                if (in_array($source, $_SESSION['SURFmap']['nfsen_all_sources']) !== false) {
                    array_push($_SESSION['SURFmap']['nfsen_selected_sources'], $source);
                }
            }
            unset($source);
        }

        /*
         * If none of the configured default sources was available or no default source
         * was configured at all, select all available sources.
         */
        if (count($_SESSION['SURFmap']['nfsen_selected_sources']) == 0) {
            $_SESSION['SURFmap']['nfsen_selected_sources'] = $_SESSION['SURFmap']['nfsen_all_sources'];
        }
    }
    
    // Initialize refresh
    if (!isset($_SESSION['SURFmap']['refresh'])) {
        // Prevent frontend from refreshing page every 5 minutes
        $_SESSION['SURFmap']['refresh'] = 0;
        $_SESSION['refresh'] = 0;
    }
    
    // Initialize dates and times   
    if (!isset($_SESSION['SURFmap']['date1']) || !isset($_SESSION['SURFmap']['date2'])) {
        $latest_date = generateDateString(5);
        $latest_time = generateTimeString(5);
        $latest_hour = substr($latest_time, 0, 2);
        $latest_minute = substr($latest_time, 3, 2);
            
        // In case the source files do not exist (yet) for a 5 min. buffer time, create timestamps based on 10 min. buffer time
        if (!sourceFilesExist($nfsen_profile_data_dir, $_SESSION['SURFmap']['nfsen_selected_sources'][0], $latest_date, $latest_hour, $latest_minute)) {
            $latest_date = generateDateString(10);
            $latest_time = generateTimeString(10);
            $latest_hour = substr($latest_time, 0, 2);
            $latest_minute = substr($latest_time, 3, 2);
        }
        
        $_SESSION['SURFmap']['date1'] = $latest_date;
        $_SESSION['SURFmap']['date2'] = $latest_date;
        
        $_SESSION['SURFmap']['hours1'] = $latest_hour;
        $_SESSION['SURFmap']['minutes1'] = $latest_minute;
        $_SESSION['SURFmap']['hours2'] = $latest_hour;
        $_SESSION['SURFmap']['minutes2'] = $latest_minute;
    }
    
    // Initialize map_center
    if (!isset($_SESSION['SURFmap']['map_center'])) {
        $_SESSION['SURFmap']['map_center'] = $config['map_center'];
    }
    
    // Initialize map_center_wo_gray
    if (!isset($_SESSION['SURFmap']['map_center_wo_gray'])) {
        $_SESSION['SURFmap']['map_center_wo_gray'] = $config['map_center'];
    }
    
    // Initialize zoom_level
    if (!isset($_SESSION['SURFmap']['zoom_level'])) { // Google Maps zoom level
        switch ($config['default_zoom_level']) {
            case 0:     $_SESSION['SURFmap']['zoom_level'] = 2;
                        break;
                    
            case 1:     $_SESSION['SURFmap']['zoom_level'] = 5;
                        break;
                    
            case 2:     $_SESSION['SURFmap']['zoom_level'] = 8;
                        break;
                    
            default:    $_SESSION['SURFmap']['zoom_level'] = 11;
                        break;
        }
    }
    
    // Check whether cURL has been loaded
    if (!isset($_SESSION['SURFmap']['curl_loaded'])) {
        $_SESSION['SURFmap']['curl_loaded'] = (extension_loaded("curl")) ? 1 : 0;
    }
    
    // Check whether the SQLite DB can be used
    if (!isset($_SESSION['SURFmap']['use_db'])) {
        $_SESSION['SURFmap']['use_db'] = 0;
        try {
            if (in_array("sqlite", PDO::getAvailableDrivers())) {
                if ($db = new PDO("sqlite:../".$constants['cache_db'])) {
                    $_SESSION['SURFmap']['use_db'] = 1;
                }
            }
        } catch(PDOException $e) {}
    }
    
    // Retrieve geocoder history
    if ($_SESSION['SURFmap']['use_db']) {
        try {
            $db = new PDO("sqlite:../".$constants['cache_db']);
            
            // Client
            $query = "SELECT * FROM geocoder_history_client WHERE date = :date";
            $stmnt = $db->prepare($query);
            $stmnt->bindParam(":date", date("Y-m-d"));
            $query_result = $stmnt->execute();
            
            if (!$query_result) {
                $error_info = $stmnt->errorInfo();
                switch ($error_info[1]) {
                    case 8:     $result['status_message'] = "No write permissions for the database.";
                                break;
                                    
                    default:    $result['status_message'] = "Data could not be retrieved from the database (SQLite error: ".$error_info[1].").";
                                break;
                }
                    
                $result['status'] = 1;
                echo json_encode($result);
                die();
            }
            
            $query_result = $stmnt->fetch(PDO::FETCH_ASSOC);
            
            if ($query_result === false) { // No entry in DB
                $result['session_data']['geocoder_history']['client'] = array(
                        'requests_success' => 0,
                        'requests_blocked' => 0,
                        'requests_error' => 0,
                        'requests_skipped' => 0
                );
            } else {
                $result['session_data']['geocoder_history']['client'] = array(
                        'requests_success' => intval($query_result['requests_success']),
                        'requests_blocked' => intval($query_result['requests_blocked']),
                        'requests_error' => intval($query_result['requests_error']),
                        'requests_skipped' => intval($query_result['requests_skipped'])
                );
            }
            
            unset($query_result);
            
            // Server
            $query = "SELECT * FROM geocoder_history_server WHERE date = :date";
            $stmnt = $db->prepare($query);
            $stmnt->bindParam(":date", date("Y-m-d"));
            $query_result = $stmnt->execute();
            
            if (!$query_result) {
                $error_info = $stmnt->errorInfo();
                switch ($error_info[1]) {
                    case 8:     $result['status_message'] = "No write permissions for geocoder cache DB.";
                                break;
                                    
                    default:    $result['status_message'] = "Data could not be retrieved from geocoder cache DB (SQLite error: ".$error_info[1].").";
                                break;
                }
                    
                $result['status'] = 1;
                echo json_encode($result);
                die();
            }
            
            $query_result = $stmnt->fetch(PDO::FETCH_ASSOC);
            
            if ($query_result === false) { // No entry in DB
                $result['session_data']['geocoder_history']['server'] = array(
                        'requests_success' => 0,
                        'requests_blocked' => 0,
                        'requests_error' => 0,
                        'requests_skipped' => 0
                );
            } else {
                $result['session_data']['geocoder_history']['server'] = array(
                        'requests_success' => intval($query_result['requests_success']),
                        'requests_blocked' => intval($query_result['requests_blocked']),
                        'requests_error' => intval($query_result['requests_error']),
                        'requests_skipped' => intval($query_result['requests_skipped'])
                );
            }
            
            unset($query_result);
        } catch(PDOException $e) {
            $result['status'] = 1;
            $result['status_message'] = "PHP PDO driver for SQLite 3 is missing";
            echo json_encode($result);
            die();
        }
    }
    
    $result['session_data']['flow_record_count'] = $_SESSION['SURFmap']['flow_record_count'];
    $result['session_data']['flow_filter'] = $_SESSION['SURFmap']['flow_filter'];
    $result['session_data']['flow_display_filter'] = $_SESSION['SURFmap']['flow_display_filter'];
    $result['session_data']['geo_filter'] = $_SESSION['SURFmap']['geo_filter'];
    $result['session_data']['nfsen_option'] = $_SESSION['SURFmap']['nfsen_option'];
    $result['session_data']['nfsen_stat_order'] = $_SESSION['SURFmap']['nfsen_stat_order'];
    $result['session_data']['nfsen_profile'] = $_SESSION['SURFmap']['nfsen_profile'];
    $result['session_data']['nfsen_profile_type'] = $_SESSION['SURFmap']['nfsen_profile_type'];
    $result['session_data']['nfsen_all_sources'] = $_SESSION['SURFmap']['nfsen_all_sources'];
    $result['session_data']['nfsen_selected_sources'] = $_SESSION['SURFmap']['nfsen_selected_sources'];
    $result['session_data']['refresh'] = $_SESSION['SURFmap']['refresh'];
    $result['session_data']['date1'] = $_SESSION['SURFmap']['date1'];
    $result['session_data']['date2'] = $_SESSION['SURFmap']['date2'];
    $result['session_data']['hours1'] = $_SESSION['SURFmap']['hours1'];
    $result['session_data']['hours2'] = $_SESSION['SURFmap']['hours2'];
    $result['session_data']['minutes1'] = $_SESSION['SURFmap']['minutes1'];
    $result['session_data']['minutes2'] = $_SESSION['SURFmap']['minutes2'];
    $result['session_data']['map_center'] = $_SESSION['SURFmap']['map_center'];
    $result['session_data']['zoom_level'] = $_SESSION['SURFmap']['zoom_level'];
    $result['session_data']['curl_loaded'] = $_SESSION['SURFmap']['curl_loaded'];
    $result['session_data']['use_db'] = $_SESSION['SURFmap']['use_db'];
    
    // Not needed, as it is already set above
    // $result['session_data']['geocoder_history'] = ...;
    
    $result['status'] = 0;
    echo json_encode($result);
    die();
    
    /*
     * Generates a date String (yyyymmdd) from either 1) a date selector in the
     * SURFmap interface, or 2) the last available date for which an nfcapd dump
     * file is available on the file system.
     * Parameters:
     *      bufferTime - buffer time between the real time and the most recent
     *                      profile update, in minutes (default: 5)
     */
    function generateDateString ($bufferTime) {
        $unprocessed_date = date("Ymd");

        // If time is in interval [00:00, 00:{bufferTime}>, the date has to contain the previous day (and eventually month and year)
        if (date("H") == 00 && date("i") < $bufferTime) {
            $year = substr($unprocessed_date, 0, 4);
            $month = substr($unprocessed_date, 4, 2);
            $day = substr($unprocessed_date, 6, 2);

            if ($month == 01 && $day == 01) {
                $year--;
                $month = 12;
                $day = 31;
            } else if (checkdate($month, $day - 1, $year)) {
                $day--;
            } else if (checkdate($month - 1, 31, $year)) {
                $day = 31;
                $month--;
            } else if (checkdate($month - 1, 30, $year)) {
                $day = 30;
                $month--;
            } else if (checkdate($month - 1, 29, $year)) {
                $day = 29;
                $month--;
            } else if (checkdate($month - 1, 28, $year)) {
                $day = 28;
                $month--;
            }

            if (strlen($day) < 2) $day = "0".$day;
            if (strlen($month) < 2) $month = "0".$month;

            $date = $year.$month.$day;
        } else {
            $date = $unprocessed_date;
        }
        
        return $date;
    }

    /*
     * Generates a time String (hhmm) from either 1) a time selector in the
     * SURFmap interface, or 2) the last available time for which an nfcapd dump
     * file is available on the file system.
     * Parameters:
     *      bufferTime - buffer time between the real time and the most recent
     *                      profile update, in minutes (default: 5)
     */
    function generateTimeString ($bufferTime) {
        $hours = date("H");
        $minutes = date("i") - (date("i") % 5);

        if ($minutes < $bufferTime) {
            if ($hours != 00) {
                $hours--; // 'previous' hour of "00" is "23"
            } else {
                $hours = 23;
            }

            $minutes = 60 - ($bufferTime - $minutes);
        } else {
            $minutes = $minutes - $bufferTime;
        }
        
        if (strlen($hours) < 2) $hours = "0".$hours;
        if (strlen($minutes) < 2) $minutes = "0".$minutes;

        return $hours.":".$minutes;
    }
    
    /*
     * Generates a file name based on the specified file name format (in config.php)
     * and the specified parameters.
     * Parameters:
     *      date - Date for the file name (should be of the following format: yyyyMMdd)
     *      hours - Hours for the file name (should be of the following format: hh)
     *      minutes - Minutes for the file name (should be of the following format: mm)
     */
    function generateFileName ($date, $hours, $minutes) {
        global $nfsenConfig;
        
        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);
        $day = substr($date, 6, 2);
        
        /*
         Possible layouts:
         0             no hierachy levels - flat layout
         1 %Y/%m/%d    year/month/day
         2 %Y/%m/%d/%H year/month/day/hour
        */
        switch(intval($nfsenConfig['SUBDIRLAYOUT'])) {
            case 0:     $fileName = "nfcapd.".$date.$hours.$minutes;
                        break;
                        
            case 1:     $fileName = $year."/".$month."/".$day."/nfcapd.".$date.$hours.$minutes;
                        break;
                        
            case 2:     $fileName = $year."/".$month."/".$day."/".$hours."/nfcapd.".$date.$hours.$minutes;
                        break;
                    
            default:    $fileName = "nfcapd.".$date.$hours.$minutes;
                        break;
        }
        
        return $fileName;
    }
    
    /*
     * Checks whether the 2nd timestamp is later (in time) than the first timestamp.
     */ 
    function isTimeRangeIsPositive ($date1, $hours1, $minutes1, $date2, $hours2, $minutes2) {
        $result = false;

        // the resulting time stamp is in GMT (instead of GMT+1), but that shouldn't be a problem; only the difference between the time stamps is important
        if (mktime($hours1, $minutes1, 0, substr($date1, 4, 2), substr($date1, 6, 2), 
                substr($date1, 0, 4)) <= mktime($hours2, $minutes2, 0, substr($date2, 4, 2), substr($date2, 6, 2), 
                substr($date2, 0, 4))) {
            $result = true;     
        }
        
        return $result;
    }

    /*
     * Verify whether the source files for the specified time window(s) exist.
     * Parameters:
     *      profile_data_dir - directory containing NfSen profile/source data
     *      source - name of the NfSen source
     *      date - date in the following format 'YYYYMMDD'
     *      hours - date in the following format 'HH' (with leading zeros)
     *      minutes - date in the following format 'MM' (with leading zeros)
     */
    function sourceFilesExist ($profile_data_dir, $source, $date, $hours, $minutes) {
        // Use 'live' profile data if shadow profile has been selected
        if ($_SESSION['SURFmap']['nfsen_profile_type'] === "real") {
            $actualProfile = $_SESSION['SURFmap']['nfsen_profile'];
            $actualSource = $source;
        } else {
            $actualProfile = "live";
            $actualSource = "*";
        }
        
        $directory = (substr($profile_data_dir, strlen($profile_data_dir) - 1) === "/") ? $profile_data_dir : $profile_data_dir."/";
        $directory .= $actualProfile."/".$actualSource."/";
        
        $fileName = generateFileName($date, $hours, $minutes);
        $files = glob($directory.$fileName);
        
        return (count($files) >= 1 && @file_exists($files[0]));
    }   

?>