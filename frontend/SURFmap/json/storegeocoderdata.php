<?php
/******************************************************
 # storegeocoderdata.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/
    
    require_once("../constants.php");
    header("content-type: application/json");
    
    if (!isset($_POST['params'])) {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    $db = new PDO("sqlite:../".$constants['cache_db']);
    foreach ($_POST['params'] as $data) {
        try {
            $query = "SELECT * FROM geocoder_cache WHERE location = :location";
            $stmnt = $db->prepare($query);
            $stmnt->bindParam(":location", $data['request']);
            $stmnt->execute();
            $query_result = $stmnt->fetch(PDO::FETCH_ASSOC);
            
            if ($query_result === false) { // No entry in DB
                $query = "INSERT INTO geocoder_cache (location, latitude, longitude) VALUES (:location, :lat, :lng)";
            } else {
                $query = "UPDATE geocoder_cache SET latitude = :lat, longitude = :lng WHERE location = :location";
            }
            
            unset($stmnt, $query_result);
            
            $stmnt = $db->prepare($query);
            $stmnt->bindParam(":location", $data['request']);
            $stmnt->bindParam(":lat", $data['lat']);
            $stmnt->bindParam(":lng", $data['lng']);
            $query_result = $stmnt->execute();
            
            if (!$query_result) {
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
        } catch(PDOException $e) {
            $result['status'] = 1;
            $result['status_message'] = "Data could not be written to geocoder cache DB.";
            echo json_encode($result);
            die();
        }
    }
    unset($data);
    
    $result = array();
    $result['status'] = 0;
    
    echo json_encode($result);
    die();

?>