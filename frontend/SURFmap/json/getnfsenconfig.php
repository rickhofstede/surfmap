<?php
/******************************************************
 # getnfsenconfig.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/

    require_once("../config.php");
    header("content-type: application/json");
    
	$nfsen_config = array();
	$comment = "#";
    
	if (@$fp = fopen($config['nfsen_config'], "r")) {
		while (!feof($fp)) {
			$line = trim(fgets($fp));
			if ($line && !preg_match("/^$comment/", $line) && strpos($line, "=") && strpos($line, ";")) {
		    	$optionTuple = explode("=", $line);
				$option = substr(trim($optionTuple[0]), 1);
				$value = trim($optionTuple[1]);
				$value = substr($value, 0, strlen($value) - 1); // remove ';'

				$subVarPos = strpos($value, "\${");
				if ($subVarPos) {
					$subVarPos = $subVarPos;
					$subVar = substr($value, $subVarPos, strpos($value, "}", $subVarPos) - $subVarPos + 1);
					$value = str_replace($subVar, $nfsen_config[substr($subVar, 2, strlen($subVar) - 3)], $value); // remove '${' and '}'
				}
				$value = str_replace("\"", "", $value);
				$value = str_replace("'", "", $value);
				$value = str_replace("//", "/", $value);
					
				if (substr($value, strlen($value) - 1) == "/") {
					$value = substr($value, 0, strlen($value) - 1);
				}
		    	$nfsen_config[$option] = $value;
		 	}
		}
		fclose($fp);
	} else {
        $result['status'] = 1;
        $result['status_message'] = "Could not open the NfSen configuration file (nfsen.conf). Please check your settings to make sure the correct path is provided.";
        echo json_encode($result);
        die();
	}

    $result = array();
    $result['config'] = $nfsen_config;
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>