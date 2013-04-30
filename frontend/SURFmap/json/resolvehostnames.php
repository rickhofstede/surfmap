<?php
/******************************************************
 # resolvehostnames.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/

    require_once("../config.php");
    header("content-type: application/json");

    if (!isset($_POST['params'])) {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    $inter_request_time = 250000; // 250ms
    $hostnames = array();
    
    foreach ($_POST['params'] as $request) {
        $hostname = gethostbyaddr($request);
        array_push($hostnames, array("address" => $request, "hostname" => $hostname));
        usleep($inter_request_time);
    }
    unset($request);
    
    $result['hostnames'] = $hostnames;
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>