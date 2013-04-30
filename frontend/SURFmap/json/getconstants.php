<?php
/******************************************************
 # getconstants.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/

    require_once("../constants.php");
    header("content-type: application/json");

    $result = array();

    if (!isset($constants)) {
        $result['status'] = 1;
        $result['status_message'] = "Could not find configuration file (constants.php)";
        echo json_encode($result);
        die();   
    }

    $result['constants'] = $constants;
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>