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
		if ($_GET['transactionType'] == "GEOCODING" && isset($_GET['location']) && isset($_GET['lat']) && isset($_GET['lng'])) {
			// if (storeGeocodedLocation(str_replace("_", " ", $_GET['location']), $_GET['lat'], $_GET['lng'])) {
			if (storeGeocodedLocation($_GET['location'], $_GET['lat'], $_GET['lng'])) {
				echo "GEOCODING##OK##".$_GET['location'];
			} else {
				echo "GEOCODING##ERROR##".$_GET['location'];
			}
		} else if ($_GET['transactionType'] == "LOG" && isset($_GET['logType']) && isset($_GET['message'])) {
			if (!($LOG_ERRORS_ONLY && $_GET['logType'] != "ERROR")) {
				error_log("[SURFmap | ".$_GET['logType']."] ".str_replace("_", " ", $_GET['message']));
			}

			echo "LOG##OK";
		} else if ($_GET['transactionType'] == "SESSION" && isset($_GET['type']) && isset($_GET['value'])) {
			if ($_GET['type'] == "mapCenter") {
				$_SESSION['SURFmap']['mapCenter'] = $_GET['value'];
			} else if ($_GET['type'] == "zoomLevel") {
				$_SESSION['SURFmap']['zoomLevel'] = $_GET['value'];
			} else if ($_GET['type'] == "refresh") {
				$_SESSION['SURFmap']['refresh'] = intval($_GET['value']);
			}

			echo "SESSION##OK";
		} else if ($_GET['transactionType'] == "STAT" && isset($_GET['type']) && isset($_GET['value'])) {
			if (storeGeocodingStat($_GET['type'], $_GET['value'])) {
				echo "STAT##OK##".$_GET['type'];
			} else {
				echo "STAT##ERROR##".$_GET['type'];
			}
		} else if ($_GET['transactionType'] == "DNS" && isset($_GET['value'])) {
			try {
				$dnsName = gethostbyaddr($_GET['value']);
				echo "DNS##OK##".$_GET['value']."##".$dnsName;
			} catch (Exception $e) {
				echo "DNS##ERROR##".$_GET['value'];
			}
		}
	} else {
		echo "ERROR";
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
	 *		statType - statistics type, can have the following values:
	 *			0: successful geocoding requests
	 *			1: erroneous geocoding requests
	 *			2: skipped geocoding requests
	 * 			3: blocked geocoding requests
	 *			'geocoderRequestsSuccess': successful geocoding requests
	 *			'geocoderRequestsError': rroneous geocoding requests
	 *			'geocoderRequestsSkip': skipped geocoding requests
	 *			'geocoderRequestsBlock': blocked geocoding requests
	 *		count - amount of requests to be added to the DB for the specified type
	 * Return:
	 *		true - on success
	 *		false - on failure
	 */
	function storeGeocodingStat($statType, $count) {
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
			
			if(is_int($statType)) {
				switch($statType) {
					case 0:
						$type = "requestsSuccess";
						break;
					case 1:
						$type = "requestsError";
						break;
					case 2:
						$type = "requestsSkip";
						break;
					case 3:
						$type = "requestsBlock";
						break;
					default:
						return false;
				}
			} else {
				if($statType === "geocoderRequestsSuccess") {
					$type = "requestsSuccess";
				} else if($statType === "geocoderRequestsError") {
					$type = "requestsError";
				} else if($statType === "geocoderRequestsSkip") {
					$type = "requestsSkip";
				} else if($statType === "geocoderRequestsBlock") {
					$type = "requestsBlock";
				} else {
					return false;
				}
			}

			$queryResult = $db->query("SELECT $type FROM history WHERE date = ".$db->quote($date));
			$row = $queryResult->fetch(PDO::FETCH_ASSOC);
			unset($queryResult);
			
			if ($row === false) { // No entry in DB
				$queryResult = $db->exec("INSERT INTO history (date, $type) VALUES (".$db->quote($date).", $count)");
			} else if ($count > 0 || !isset($row[$type])) {
				$newRequests = $row[$type] + intval($count);
				$queryResult = $db->exec("UPDATE history SET $type = $newRequests WHERE date = ".$db->quote($date));
			}

			if (isset($queryResult) && $queryResult >= 0 && $queryResult !== false) {
				$success = true;
			}
		}
		
		return $success;
	}
	
?>