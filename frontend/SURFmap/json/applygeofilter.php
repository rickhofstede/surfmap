<?php
/******************************************************
 # applygeofilter.php
 # Author:      Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/

    header("content-type: application/json");

    $result = array();

    if (!isset($_POST['params'])) {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    require_once("../geofilter.php");
    
    $geo_filter = $_POST['params']['geo_filter'];
    $flow_record_count = $_POST['params']['flow_record_count'];
    $flow_data = $_POST['params']['flow_data'];
    
    $result['removed_record_indices'] = array();
    
    if (!empty($geo_filter)) {
        for ($i = 0; $i < $flow_record_count; $i++) {
            try {
                if (!eval_geo_filter($flow_data[$i], $geo_filter)) {
                    array_push($result['removed_record_indices'], $i);
                }
            } catch (GeoFilterException $ex) {
                $result['status'] = 1;
                $result['status_message'] = "Geo filter syntax error: ".$ex->errorMessage();
                echo json_encode($result);
                die();
            }
        }
    }
    
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>