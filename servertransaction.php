<?php
/*******************************
 * servertransaction.php [SURFmap]
 * Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 * University of Twente, The Netherlands
 *
 * LICENSE TERMS: outlined in BSD-license.html
 *******************************/
	
	require_once("config.php");
	session_start();
	
	if (isset($_GET['transactionType'])) {
		if ($_GET['transactionType'] == "geocoder" && isset($_GET['location']) && isset($_GET['lat']) && isset($_GET['lng'])) {
			if (storeGeocodedLocation(str_replace("_", " ", $_GET['location']), $_GET['lat'], $_GET['lng'])) {
				echo "OK##geocoder##".$_GET['location'];
			} else {
				echo "ERROR##geocoder##".$_GET['location'];
			}
		} else if ($_GET['transactionType'] == "log" && isset($_GET['logType']) && isset($_GET['message'])) {
			if (!($LOG_ERRORS_ONLY && $_GET['logType'] != "ERROR")) {
				error_log("[SURFmap | ".$_GET['logType']."] ".str_replace("_", " ", $_GET['message']));
			}

			echo "OK##log";
		} else if ($_GET['transactionType'] == "session" && isset($_GET['type']) && isset($_GET['value'])) {
			if ($_GET['type'] == "mapCenter") {
				$_SESSION['SURFmap']['mapCenter'] = $_GET['value'];
			} else if ($_GET['type'] == "zoomLevel") {
				$_SESSION['SURFmap']['zoomLevel'] = $_GET['value'];
			} else if ($_GET['type'] == "refresh") {
				$_SESSION['SURFmap']['refresh'] = intval($_GET['value']);
			}

			echo "OK##session";
		} else if ($_GET['transactionType'] == "stat" && isset($_GET['type']) && isset($_GET['value'])) {
			if ($_GET['type'] == "totalGeocodingRequests") {
				storeGeocodingStat($_GET['value']);
			}

			echo "OK##stat";
		}
	}
	
    /**
	 * Writes a geocoded location to the GeoCoder DB (SQLite).
	 * Parameters:
	 *		location - name of the place that was geocoded
	 *		lat - latitude coordinate of the geocoded place
	 *		lng - longitude coordinate of the geocoded place
	 * Return:
	 *		true - on success
	 *		false - on failure
	 */	
	function storeGeocodedLocation($location, $lat, $lng) {
		global $GEOCODER_DB_SQLITE2, $GEOCODER_DB_SQLITE3;
		
		try {
			$PDODrivers = PDO::getAvailableDrivers();
			if (in_array("sqlite", $PDODrivers)) {
				$db = new PDO("sqlite:$GEOCODER_DB_SQLITE3");
			} else if (in_array("sqlite2", $PDODrivers)) {
				$db = new PDO("sqlite2:$GEOCODER_DB_SQLITE2");
			} else {}
		} catch(PDOException $e) {}
		
		$success = false;
		if (isset($db)) {
			$query = "INSERT INTO geocoder VALUES (".$db->quote($location).", ".$lat.", ".$lng.")";
			$queryResult = $db->exec($query);
			
			if ($queryResult >= 0 && $queryResult !== false) {
				$success = true;
			}
		}
		
		return $success;
	}

    /**
	 * Writes the number of completed geocoding requests to 
	 * the GeoCoder DB (SQLite).
	 * Parameters:
	 *		totalGeocodingRequests - number of completed geocoding requests
	 * Return:
	 *		true - on success
	 *		false - on failure
	 */
	function storeGeocodingStat($totalGeocodingRequests) {
		global $GEOCODER_DB_SQLITE2, $GEOCODER_DB_SQLITE3;
		
		try {
			$PDODrivers = PDO::getAvailableDrivers();
			if (in_array("sqlite", $PDODrivers)) {
				$db = new PDO("sqlite:$GEOCODER_DB_SQLITE3");
			} else if (in_array("sqlite2", $PDODrivers)) {
				$db = new PDO("sqlite2:$GEOCODER_DB_SQLITE2");
			} else {}
		} catch(PDOException $e) {}
		
		$success = false;
		if (isset($db)) {
			$date = date("Y-m-d");
			$queryResult = $db->query("SELECT * FROM history WHERE date = ".$db->quote($date));
			$row = $queryResult->fetch(PDO::FETCH_ASSOC);
			unset($queryResult);
			
			if ($row === false) { // No entry in DB
				$queryResult = $db->exec("INSERT INTO history VALUES (".$db->quote($date).", ".$totalGeocodingRequests.")");
			} else if ($totalGeocodingRequests > 0) {
				$newRequests = $row['requests'] + intval($totalGeocodingRequests);
				$queryResult = $db->exec("UPDATE history SET requests = $newRequests WHERE date = ".$db->quote($date));
			}

			if (isset($queryResult) && $queryResult >= 0 && $queryResult !== false) {
				$success = true;
			}
		}
		
		return $success;
	}
	
?>