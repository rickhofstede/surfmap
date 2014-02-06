<?php
/******************************************************
 # geocode.php
 # Author:      Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/
    
    require_once("../config.php");
    require_once("../constants.php");
    
    header("content-type: application/json");
    
    if (!isset($_POST['params'])) {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    $inter_request_time = $constants['default_geocoder_request_interval'] * 1000;

    $result = array();
    $result['geocoder_data'] = array();
    $result['requests_success'] = 0;
    $result['requests_blocked'] = 0;
    $result['requests_error'] = 0;
    $result['requests_skipped'] = 0;
    
    if (extension_loaded('curl')) {
        // Used if cURL detects some IPv6-related connectivity problems
        $IPv6_problem = 0;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        
        if ($config['use_proxy']) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, $config['proxy_type']);
            curl_setopt($ch, CURLOPT_PROXY, $config['proxy_ip']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $config['proxy_port']);

            if ($config['proxy_user_authentication']) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $config['proxy_username'].":".$config['proxy_password']);
            }
        }
    
        while (sizeof($_POST['params']) > 0) {
            $request = array_shift($_POST['params']);
            $formatted_request = str_replace(" ", "+", urlencode($request));
            $url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$formatted_request."&sensor=false";
            curl_setopt($ch, CURLOPT_URL, $url);
        
            if ($IPv6_problem) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
        
            $response = curl_exec($ch);
            if ($response === false && curl_error($ch) == "name lookup timed out") {
                $IPv6_problem = 1;
            } else {
                try {
                    $response = json_decode($response, true);
                } catch (Exception $e) {}
            
                // Status code can be OK, ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
                if ($response['status'] == 'OK') {
                    $result['requests_success']++;
                    $geometry = $response['results'][0]['geometry'];
                    $lat = $geometry['location']['lat'];
                    $lng = $geometry['location']['lng'];
                    array_push($result['geocoder_data'], array('request' => $request, 'lat' => floatval($lat), 'lng' => floatval($lng), 'status_message' => $response['status']));
                } else if ($response['status'] == 'OVER_QUERY_LIMIT') {
                    $result['requests_blocked']++;
        
                    // Add current request to $_POST['params'] again for a retry
                    array_push($_POST['params'], $request);
                    $inter_request_time += 100000; // 100 ms
                } else {
                    $result['requests_error']++;
                    array_push($result['geocoder_data'], array('request' => $request, 'status_message' => $response['status']));
                }
                usleep($inter_request_time);
            }
        }
    
        curl_close($ch);
    } else {
        $result['status'] = 1;
        $result['status_message'] = "PHP cURL module is not installed";
        echo json_encode($result);
        die();
    }
    
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>