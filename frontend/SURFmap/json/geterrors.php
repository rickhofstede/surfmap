<?php
/******************************************************
 # geterrors.php
 # Author:      Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/

    require_once("../config.php");
    require_once("../constants.php");
    header("content-type: application/json");

    $result = array();
    $result['error_codes'] = array();
    
    // Check PDO SQLite3 driver availability
    if (!in_array("sqlite", PDO::getAvailableDrivers())) {
        array_push($result['error_codes'], 0);
    }
    
    // Check database exists
    if (!file_exists('../'.$constants['cache_db'])) {
        array_push($result['error_codes'], 1);
    }
    
    // Check database readable
    if (!is_readable('../'.$constants['cache_db'])) {
        array_push($result['error_codes'], 2);
    }
    
    // Check database writable
    if (!is_writable('../'.$constants['cache_db'])) {
        array_push($result['error_codes'], 3);
    }
    
    // Check geolocation database available
    if ($config['geolocation_db'] == 'MaxMind') {
        $MaxMind_path = $config['maxmind_path'];
        
        // Check for absolute or relative path
        if (substr($MaxMind_path, 0, 1) != "/") {
            $MaxMind_path = "../".$MaxMind_path;
        }
        
        if (!@file_exists($MaxMind_path)) {
            array_push($result['error_codes'], 4);
        }
        
        if (!is_readable($MaxMind_path)) {
            array_push($result['error_codes'], 5);
        }
    } else if ($config['geolocation_db'] == 'IP2Location') {
        $IP2Location_path = $config['ip2location_path'];
        
        // Check for absolute or relative path
        if (substr($IP2Location_path, 0, 1) != "/") {
            $IP2Location_path = "../".$IP2Location_path;
        }
        
        if (!@file_exists($IP2Location_path)) {
            array_push($result['error_codes'], 6);
        }
        
        if (!is_readable($IP2Location_path)) {
            array_push($result['error_codes'], 7);
        }
    }
    
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>