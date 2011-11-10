<?php
	/*******************************
	 * servertransaction.php [SURFmap]
	 * Author: Rick Hofstede
	 * University of Twente, The Netherlands
	 *******************************/
	
	require_once("config.php");
	session_start();
	
	if($_GET['transactionType'] == "geocoder" && isset($_GET['location']) && isset($_GET['lat']) && isset($_GET['lng'])) {
		$location = str_replace("_", " ", $_GET['location']);
		
		try {
			$PDODrivers = PDO::getAvailableDrivers();
			if(in_array("sqlite", $PDODrivers)) {
				$db = new PDO("sqlite:$GEOCODER_DB_SQLITE3");
			} else if(in_array("sqlite2", $PDODrivers)) {
				$db = new PDO("sqlite2:$GEOCODER_DB_SQLITE2");
			} else {}
		} catch(PDOException $e) {}
		
		if(isset($db)) {
			$query = "INSERT INTO geocoder VALUES (".$db->quote($location).", ".$_GET['lat'].", ".$_GET['lng'].")";
			$queryResult = $db->exec($query);
			
			if($queryResult >= 0 && $queryResult !== false) {
				echo "OK##geocoder##$location";
			} else {
				echo "ERROR##geocoder##$location";
			}
		}
	} else if($_GET['transactionType'] == "log" && isset($_GET['logType']) && isset($_GET['message'])) {
		if(!($LOG_ERRORS_ONLY && $_GET['logType'] != "ERROR")) {
			error_log("[SURFmap | ".$_GET['logType']."] ".str_replace("_", " ", $_GET['message']));
		}
		
		echo "OK##log";
	} else if($_GET['transactionType'] == "session" && isset($_GET['type']) && isset($_GET['value'])) {
		if($_GET['type'] == "mapCenter") {
			$_SESSION['SURFmap']['mapCenter'] = $_GET['value'];
		} else if($_GET['type'] == "zoomLevel") {
			$_SESSION['SURFmap']['zoomLevel'] = $_GET['value'];
		} else if($_GET['type'] == "refresh") {
			$_SESSION['SURFmap']['refresh'] = intval($_GET['value']);
		}
		
		echo "OK##session";
	} else {
		echo "ERROR";
	}

?>