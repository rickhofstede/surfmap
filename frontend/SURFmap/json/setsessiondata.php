<?php
/******************************************************
 # setsessiondata.php
 # Author:      Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/
    
    if (!function_exists('ReportLog')) {
    	function ReportLog() {
    	    // dummy function to avoid PHP errors
    	}
    }
    
    require_once("../config.php");
    require_once("../constants.php");
    require_once("../util.php");
    require_once("../../../conf.php");
    require_once("../../../nfsenutil.php");
    
    header("content-type: application/json");

    if (!session_id()) session_start();
    
    if (!isset($_SESSION['profileinfo'])) {
        $result['status'] = 1;
        $result['status_message'] = "NfSen profile not initialized";
        echo json_encode($result);
        die();
    }
    
    $result = array();
    $result['status'] = 0;
    $result['session_data'] = array();

    // Set flow_record_count
    if (isset($_POST['params']['flow_record_count'])) {
        $_SESSION['SURFmap']['flow_record_count'] = intval($_POST['params']['flow_record_count']);
        $result['session_data']['flow_record_count'] = $_SESSION['SURFmap']['flow_record_count'];
    }
    
    // Initialize aggregation fields
    if (isset($_POST['params']['aggregation_fields'])) {
        foreach ($_POST['params']['aggregation_fields'] as $key => $value) {
            $_SESSION['SURFmap']['aggregation_fields'][$key] = intval($value);
        }
        
        $result['session_data']['aggregation_fields'] = $_SESSION['SURFmap']['aggregation_fields'];
    }
    
    // Set flow_filter & flow_display_filter
    // No need to check for $_POST['params']['flow_filter'] since only flow_display_filter is sent by frontend
    if (isset($_POST['params']['flow_display_filter'])) {
        $_SESSION['SURFmap']['flow_filter'] = $_POST['params']['flow_display_filter'];
            
        // ***** 1. Prepare filters *****
        foreach ($config['internal_domains'] as $key => $value) {
            if (strlen($key) != 0) {
                $internal_domains = explode(";", $key);
                foreach ($internal_domains as $domain) {
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
        $static_filter_ipv6_linklocal_trafic = "not net fe80::/10";
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
        if (strpos($_SESSION['SURFmap']['flow_filter'], $static_filter_ipv6_linklocal_trafic) === false) {
            array_push($static_filters, $static_filter_ipv6_linklocal_trafic);
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
        if (strpos($_SESSION['SURFmap']['flow_display_filter'], $static_filter_ipv6_linklocal_trafic) === 0) {
            $_SESSION['SURFmap']['flow_display_filter'] = str_replace($static_filter_ipv6_linklocal_trafic, "", $_SESSION['SURFmap']['flow_display_filter']);
        } else {
            $_SESSION['SURFmap']['flow_display_filter'] = str_replace(" and ".$static_filter_ipv6_linklocal_trafic, "", $_SESSION['SURFmap']['flow_display_filter']);
        }
        
        $result['session_data']['flow_filter'] = $_SESSION['SURFmap']['flow_filter'];
        $result['session_data']['flow_display_filter'] = $_SESSION['SURFmap']['flow_display_filter'];
    }
    
    // Set geo_filter
    if (isset($_POST['params']['geo_filter'])) {
        $_SESSION['SURFmap']['geo_filter'] = $_POST['params']['geo_filter'];
        $result['session_data']['geo_filter'] = $_SESSION['SURFmap']['geo_filter'];
    }
    
    // Set nfsen_option
    if (isset($_POST['params']['nfsen_option'])) {
        $_SESSION['SURFmap']['nfsen_option'] = $_POST['params']['nfsen_option'];
        $result['session_data']['nfsen_option'] = $_SESSION['SURFmap']['nfsen_option'];
    }
    
    // Set nfsen_option
    if (isset($_POST['params']['nfsen_stat_order'])) {
        $_SESSION['SURFmap']['nfsen_stat_order'] = intval($_POST['params']['nfsen_stat_order']);
        $result['session_data']['nfsen_stat_order'] = $_SESSION['SURFmap']['nfsen_stat_order'];
    }
    
    // Set nfsen_selected_sources
    if (isset($_POST['params']['nfsen_selected_sources'])) {
        // If no source has been selected, do nothing
        if (count($_POST['params']['nfsen_selected_sources']) == 0) {
            break;
        }
        
        $_SESSION['SURFmap']['nfsen_selected_sources'] = array(); // Clear current list
        foreach ($_POST['params']['nfsen_selected_sources'] as $source) {
            // Only add source if it exists in the current profile
            if (in_array($source, $_SESSION['SURFmap']['nfsen_all_sources'])) {
                array_push($_SESSION['SURFmap']['nfsen_selected_sources'], $source);
            }
        }
        unset($source);
        
        $result['session_data']['nfsen_selected_sources'] = $_SESSION['SURFmap']['nfsen_selected_sources'];
    }
    
    // Set refresh
    if (isset($_POST['params']['refresh'])) {
        // Prevent frontend from refreshing page every 5 minutes
        $_SESSION['SURFmap']['refresh'] = intval($_POST['params']['refresh']);
        $result['session_data']['refresh'] = $_SESSION['SURFmap']['refresh'];
    }
    
    $out_list = nfsend_query("SURFmap::get_nfsen_profiledatadir", array());
    $nfsen_profile_data_dir = $out_list['nfsen_profiledatadir'];
    unset($out_list);
    
    // Set dates and times (1)
    if (isset($_POST['params']['date1']) || isset($_POST['params']['hours1']) || isset($_POST['params']['minutes1'])) {
        if (nfcapd_files_exist($nfsen_profile_data_dir, $_SESSION['SURFmap']['nfsen_selected_sources'][0], $_POST['params']['date1'], $_POST['params']['hours1'], $_POST['params']['minutes1'])) {
            $_SESSION['SURFmap']['date1'] = $_POST['params']['date1'];
            $_SESSION['SURFmap']['hours1'] = $_POST['params']['hours1'];
            $_SESSION['SURFmap']['minutes1'] = $_POST['params']['minutes1'];
            
            $result['session_data']['date1'] = $_SESSION['SURFmap']['date1'];
            $result['session_data']['hours1'] = $_SESSION['SURFmap']['hours1'];
            $result['session_data']['minutes1'] = $_SESSION['SURFmap']['minutes1'];
        } else {
            $result['status'] = 1;
            $result['status_message'] = "No flow data files could be found matching the selected 'begin' timeslot. Reverting 'begin' timeslot.";
        }
    }
    
    // Set dates and times (2)
    if (isset($_POST['params']['date2']) || isset($_POST['params']['hours2']) || isset($_POST['params']['minutes2'])) {
        if (nfcapd_files_exist($nfsen_profile_data_dir, $_SESSION['SURFmap']['nfsen_selected_sources'][0], $_POST['params']['date2'], $_POST['params']['hours2'], $_POST['params']['minutes2'])) {
            $_SESSION['SURFmap']['date2'] = $_POST['params']['date2'];
            $_SESSION['SURFmap']['hours2'] = $_POST['params']['hours2'];
            $_SESSION['SURFmap']['minutes2'] = $_POST['params']['minutes2'];
            
            $result['session_data']['date2'] = $_SESSION['SURFmap']['date2'];
            $result['session_data']['hours2'] = $_SESSION['SURFmap']['hours2'];
            $result['session_data']['minutes2'] = $_SESSION['SURFmap']['minutes2'];
        } else {
            $result['status'] = 1;
            $result['status_message'] = "No flow data files could be found matching the selected 'end' timeslot. Reverting 'end' timeslot.";
        }
    }
    
    // Set map_center
    if (isset($_POST['params']['map_center'])) {
        $_SESSION['SURFmap']['map_center'] = $_POST['params']['map_center'];
        $result['session_data']['map_center'] = $_SESSION['SURFmap']['map_center'];
    }
    
    // Set map_center_wo_gray
    if (isset($_POST['params']['map_center_wo_gray'])) {
        $_SESSION['SURFmap']['map_center_wo_gray'] = $_POST['params']['map_center_wo_gray'];
        $result['session_data']['map_center_wo_gray'] = $_SESSION['SURFmap']['map_center_wo_gray'];
    }
    
    // Set zoom_level
    if (isset($_POST['params']['zoom_level'])) { // Google Maps zoom level
        $_SESSION['SURFmap']['zoom_level'] = floatval($_POST['params']['zoom_level']);
        $result['session_data']['zoom_level'] = $_SESSION['SURFmap']['zoom_level'];
    }
    
    // Set geocoder history
    if (isset($_POST['params']['geocoder_history'])) {
        $db = new PDO("sqlite:../".$constants['cache_db']);
        $date = date("Y-m-d");
        
        foreach ($_POST['params']['geocoder_history'] as $geocoding_type => $parameters) {
            try {
                $requests_success = $_POST['params']['geocoder_history'][$geocoding_type]['requests_success'];
                $requests_error = $_POST['params']['geocoder_history'][$geocoding_type]['requests_error'];
                $requests_skipped = $_POST['params']['geocoder_history'][$geocoding_type]['requests_skipped'];
                $requests_blocked = $_POST['params']['geocoder_history'][$geocoding_type]['requests_blocked'];
                
                $query = "SELECT * FROM geocoder_history_".$geocoding_type." WHERE date = :date";
                $stmnt = $db->prepare($query);
                $stmnt->bindParam(":date", $date);
                $stmnt->execute();
                $query_result = $stmnt->fetch(PDO::FETCH_ASSOC);
                
                unset($query, $stmnt);
            
                if ($query_result === false) { // No entry in DB
                    $query = "INSERT INTO geocoder_history_".$geocoding_type." (date, requests_success, requests_error, requests_skipped, requests_blocked) VALUES (:date, :requests_success, :requests_error, :requests_skipped, :requests_blocked)";
                    $stmnt = $db->prepare($query);
                    $stmnt->bindParam(":date", $date);
                    $stmnt->bindParam(":requests_success", $requests_success);
                    $stmnt->bindParam(":requests_error", $requests_error);
                    $stmnt->bindParam(":requests_skipped", $requests_skipped);
                    $stmnt->bindParam(":requests_blocked", $requests_blocked);
                    $query_result = $stmnt->execute();
                } else {
                    $query = "UPDATE geocoder_history_".$geocoding_type." SET requests_success = :requests_success, requests_error = :requests_error, requests_skipped = :requests_skipped, requests_blocked = :requests_blocked WHERE date = :date";
                    $stmnt = $db->prepare($query);
                    $stmnt->bindParam(":date", $date);
                    $stmnt->bindParam(":requests_success", $requests_success);
                    $stmnt->bindParam(":requests_error", $requests_error);
                    $stmnt->bindParam(":requests_skipped", $requests_skipped);
                    $stmnt->bindParam(":requests_blocked", $requests_blocked);
                    $query_result = $stmnt->execute();
                }
                
                if ($query_result) {
                    $result['session_data']['geocoder_history'][$geocoding_type]['requests_success'] = $requests_success;
                    $result['session_data']['geocoder_history'][$geocoding_type]['requests_error'] = $requests_error;
                    $result['session_data']['geocoder_history'][$geocoding_type]['requests_skipped'] = $requests_skipped;
                    $result['session_data']['geocoder_history'][$geocoding_type]['requests_blocked'] = $requests_blocked;
                } else {
                    $error_info = $stmnt->errorInfo();
                    switch ($error_info[1]) {
                        case 8:     $result['status_message'] = "No write permissions for geocoder cache DB.";
                                    break;
                                    
                        default:    $result['status_message'] = "Data could not be written to geocoder cache DB (SQLite error: ".$error_info[1].").";
                                    break;
                    }
                    
                    $result['status'] = 1;
                    echo json_encode($result);
                    die();
                }
            
                unset($query_result, $row);
            } catch(PDOException $e) {
                $result['status'] = 1;
                $result['status_message'] = "A PHP PDO driver error has occurred ".$e->getMessage().")";
                echo json_encode($result);
                die();
            }
        }
    }
    
    nfsend_disconnect();
    
    echo json_encode($result);
    die(); 

?>