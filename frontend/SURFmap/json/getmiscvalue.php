<?php
/******************************************************
 # getmiscvalue.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/
    
    require_once("../constants.php");
    header("content-type: application/json");

    $result = array();
    
    if (isset($_POST['params'])) {
        $keys = $_POST['params'];
    } else {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    try {
        $db = new PDO("sqlite:../".$constants['cache_db']);
        $result['values'] = array();
    
        foreach ($keys as $key) {
    		$query = "SELECT value FROM misc WHERE key = :key";
            $stmnt = $db->prepare($query);
            $stmnt->bindParam(":key", $key);
            $stmnt->execute();
            $query_result = $stmnt->fetch(PDO::FETCH_ASSOC);
            $value = $query_result['value'];
        
            array_push($result['values'], array('key' => $key, 'value' => $value));
        }
        unset($key);
    } catch(PDOException $e) {
        $result['status'] = 1;
        $result['status_message'] = "A PHP PDO driver has occurred";
        echo json_encode($result);
        die();
    }
    
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>