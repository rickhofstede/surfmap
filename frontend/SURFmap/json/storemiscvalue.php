<?php
/******************************************************
 # storemiscvalue.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/
    
    require_once("../constants.php");
    header("content-type: application/json");

    $result = array();
    
    if (!isset($_POST['params'])) {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    $db = new PDO("sqlite:../".$constants['cache_db']);
    foreach ($_POST['params'] as $key => $value) {
        try {
            $query = "SELECT * FROM misc WHERE key = :key";
            $stmnt = $db->prepare($query);
            $stmnt->bindParam(":key", $key);
            $stmnt->execute();
            $query_result = $stmnt->fetch(PDO::FETCH_ASSOC);
            
            if ($query_result === false) { // No entry in DB
                $query = "INSERT INTO misc (key, value) VALUES (:key, :value)";
            } else {
                $query = "UPDATE misc SET value = :value WHERE key = :key";
            }
            
            unset($stmnt, $query_result);
            
            $stmnt = $db->prepare($query);
            $stmnt->bindParam(":key", $key);
            $stmnt->bindParam(":value", $value);
            $query_result = $stmnt->execute();
        
            if (!$query_result) {
                $error_info = $stmnt->errorInfo();
                switch ($error_info[1]) {
                    case 8:     $result['status_message'] = "No write permissions for the database.";
                                break;
                            
                    default:    $result['status_message'] = "Data could not be written to the database (SQLite error: ".$error_info[1].").";
                                break;
                }
            
                $result['status'] = 1;
                echo json_encode($result);
                die();
            }
        } catch(PDOException $e) {
            $result['status'] = 1;
            $result['status_message'] = "Data could not be written to the database.";
            echo json_encode($result);
            die();
        }
    }
    unset($key, $value);
    
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>