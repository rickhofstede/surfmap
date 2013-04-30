<?php
/******************************************************
 # geocode.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/
    
    header("content-type: application/json");
    
    if (!isset($_POST['params'])) {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    $inter_request_time = 250000; // 250ms

    $result = array();
    $result['geocoder_data'] = array();
    $result['requests_success'] = 0;
    $result['requests_blocked'] = 0;
    $result['requests_error'] = 0;
    $result['requests_skipped'] = 0;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);

    foreach ($_POST['params'] as $request) {
        $string = str_replace(" ", "+", urlencode($request));
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$string."&sensor=false";
        
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // $response = json_decode(curl_exec($ch), true);
        try {
            $response = json_decode(curl_exec($ch), true);
        } catch (Exception $e) {
        }
        
        // Status code can be OK, ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
        if ($response['status'] == 'OK') {
            $result['requests_success']++;
            $geometry = $response['results'][0]['geometry'];
            $lat = $geometry['location']['lat'];
            $lng = $geometry['location']['lng'];
            array_push($result['geocoder_data'], array('request' => $request, 'lat' => floatval($lat), 'lng' => floatval($lng), 'status_message' => $response['status']));
        } else if ($response['status'] == 'OVER_QUERY_LIMIT') {
            $result['requests_blocked']++;
            
            // Add current request another time to $_POST['params'] for a retry
            array_push($_POST['params'], $request);
            $inter_request_time += 100000; // 100 ms
        } else {
            $result['requests_error']++;
            array_push($result['geocoder_data'], array('request' => $request, 'status_message' => $response['status']));
        }
        
        usleep($inter_request_time);
    }
    unset($request);
    
    curl_close($ch);
    
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>