<?php
	/*******************************
	 * configurationchecker.php [SURFmap]
	 * Author: Rick Hofstede
	 * University of Twente, The Netherlands
	 *******************************/
	require_once("../config.php");
	
	function dir_tree($dir) {
		$path = '';
	   	$stack[] = $dir;
	   	while ($stack) {
	    	$thisdir = array_pop($stack);
	       	if ($dircont = scandir($thisdir)) {
	        	$i=0;
	           	while (isset($dircont[$i])) {
	               	if ($dircont[$i] !== '.' && $dircont[$i] !== '..') {
	                	$current_file = "{$thisdir}/{$dircont[$i]}";
	                   	if (is_file($current_file)) {
	                    	$path[] = "{$thisdir}/{$dircont[$i]}";
	                   	} elseif (is_dir($current_file)) {
	                        $path[] = "{$thisdir}/{$dircont[$i]}";
	                       	$stack[] = $current_file;
	                   	}
	               	}
	               	$i++;
	           	}
	       	}
	   	}
		return $path;
	}
	
	// 1. Check NfSen socket
	if(@file_exists($COMMSOCKET)) $nfsenSocketOK = 1;
	else $nfsenSocketOK = 0;
	
	// 2. Check NfSen source directory
	if(@file_exists($NFSEN_SOURCE_DIR)) $nfsenSourceDirOK = 1;
	else $nfsenSourceDirOK = 0;
	
	// 3. Check NfSen source file existance
	$nfsenSourceFiles = $NFSEN_SOURCE_DIR.substr($NFSEN_SOURCE_FILE_NAMING, 0, strpos($NFSEN_SOURCE_FILE_NAMING, ".") + 1);
	$nfsenSourceFiles = str_replace("[yyyy]", date("Y"), $nfsenSourceFiles);
	$nfsenSourceFiles = str_replace("[MM]", date("m"), $nfsenSourceFiles);
	$nfsenSourceFiles = str_replace("[dd]", date("d"), $nfsenSourceFiles);
	$hours = intval(date("H"));
	$hours--;
	if(strlen($hours) == 1) {
		$hours = "0".$hours;
	}
	$nfsenSourceFiles = str_replace("[hh]", $hours, $nfsenSourceFiles);
	$files = glob($nfsenSourceFiles."*");
	
	// Use 'count($files) > 1' because in some setups only 'nfcapd.current.*' is present
	if(count($files) > 1 && @file_exists($files[0])) $nfsenSourceFileExistanceOK = 1;
	else $nfsenSourceFileExistanceOK = 0;
	
	try {
		// 4. Check Geocoder database connection
		$PDODrivers = PDO::getAvailableDrivers();
		if(in_array("sqlite", $PDODrivers)) {
			$geocoderDBFile = "sqlite:../$GEOCODER_DB_SQLITE3";
			$geocoderDBConnection = new PDO($geocoderDBFile);
		} else if(in_array("sqlite2", $PDODrivers)) {
			$geocoderDBFile = "sqlite:../$GEOCODER_DB_SQLITE2";
			$geocoderDBConnection = new PDO($geocoderDBFile);
		} else {
		}
		
		if(isset($geocoderDBConnection)) {
			$geocoderDatabaseConnectionOK = 1;

			// 5. Check Geocoder database writability
			$geocoderDBConnection->exec("INSERT INTO geocoder VALUES (".$geocoderDBConnection->quote("_TEST_").", 1.0, -1.0)");
			$queryResult = $geocoderDBConnection->query("SELECT * FROM geocoder WHERE location = ".$geocoderDBConnection->quote("_TEST_"));
			$row = $queryResult->fetch(PDO::FETCH_ASSOC);

			if($row) {
				$geocoderDatabaseOK = 1;
			} else {
				$geocoderDatabaseOK = 0;
			}
		
			$geocoderDBConnection->exec("DELETE FROM geocoder WHERE location = ".$geocoderDBConnection->quote("_TEST_"));
		} else {
			$geocoderDatabaseConnectionOK = 0;
			$geocoderDatabaseOK = 0;
		}
	} catch (PDOException $e) {
		$geocoderDatabaseConnectionOK = 0;
		$geocoderDatabaseOK = 0;
	}
	
	// 6. Check IP2Location DB path
	if(@file_exists($IP2LOCATION_PATH)) $ip2LocationDBPathOK = 1;
	else $ip2LocationDBPathOK = 0;
	
	// 7. Check MaxMind DB path
	if(@file_exists($MAXMIND_PATH)) $maxmindDBPathOK = 1;
	else $maxmindDBPathOK = 0;
	
	// 8. Check file permissions
	$dir = dir_tree("..");
	$filePermOK = 1;
	$filesWithWrongPerm = "";
	foreach($dir as $entry) {
		$permIntVal = intval(substr(sprintf('%o', @fileperms($entry)), -4));
		
		if((strpos($entry, "../geocoder/") === 0 && !is_Writable($entry)) || !is_readable($entry))  {
			if(strpos($entry, "../") == 0) {
				$entry = substr($entry, 3);
			}
			
			if($filesWithWrongPerm == "") {
				$filesWithWrongPerm = $entry."__".$permIntVal;
			} else {
				$filesWithWrongPerm .= "___".$entry."__".$permIntVal;
			}	
			$filePermOK = 0;
		}
	}
	
	// 9. Check additional NetFlow source selector syntax
	$additionalSrcSelectorExploded = explode(";", $NFSEN_ADDITIONAL_SRC_SELECTORS);
	if($NFSEN_ADDITIONAL_SRC_SELECTORS == "" ||
			(substr_count($NFSEN_ADDITIONAL_SRC_SELECTORS, ",") == 0 && substr_count($NFSEN_ADDITIONAL_SRC_SELECTORS, ":") == 0 &&
			sizeof($additionalSrcSelectorExploded) == substr_count($NFSEN_ADDITIONAL_SRC_SELECTORS, ";") + 1) && !in_array("", $additionalSrcSelectorExploded)) {
		$additionalSrcSelectorSyntaxOK = 1;
	} else {
		$additionalSrcSelectorSyntaxOK = 0;
	}
	
	// 10. Check map center coordinates syntax
	if(substr_count($MAP_CENTER, ",") == 1 && substr_count($MAP_CENTER, ".") <= 2) $mapCenterSyntaxOK = 1;
	else $mapCenterSyntaxOK = 0;
	
	// 11. Check internal domain syntax
	$internalDomainExploded = explode(";", $INTERNAL_DOMAINS);
	$internalDomainSyntaxOK = 1;
	if(sizeof($internalDomainExploded) > 0) {
		foreach($internalDomainExploded as $domain) {
			if(substr_count($domain, ".") < 1 || substr_count($domain, "/") != 1) {
				$internalDomainSyntaxOK = 0;
				break;
			}
		}
	}
	
	// 12. Check availability of 'mbstring' PHP module (for MaxMind API)
	if(extension_loaded("mbstring")) {
		$mbstringModuleOK = 1;
	} else {
		$mbstringModuleOK = 0;
	}
	
?>

<!DOCtype html PUBLIC "-//W3C//Dtd XHTML 1.0 Strict//EN"
    "http://www.w3.org/tr/xhtml1/Dtd/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
	<head>
		<link rel="stylesheet" type="text/css" href="configurationchecker.css" />
    	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<title>SURFmap Configuration Checker</title>
		
	</head>
	<body>
		<h1>SURFmap Configuration Checker</h1>
		
		<p >This application checks most of the main configuration options of the SURFmap application. For each 
			of these options the currently configured value (between brackets) and the status are shown. Three 
			status options are possible:</p>
		<ol>
			<li>Green (OK). The configuration seems to be set correctly.</li>
			<li>Red (Failure). An error was found. The mentioned option is required for your setup. 
				Please try another configuration.</li>
			<li>Blue (Skip). The configuration option was not set correctly, but it is not required 
				for your setup. You can therefore ignore this.</li>
		</ol>
		
		<p>In order to help you to fix your configuration problem, some hints are provided with each of
			the configuration options below. To find these hints, please click on the corresponding option
			and a help window will appear.</p>
		
		<div id="checkitem1" class="checkitem" onclick="showHidePopup('checkitem1')">1. NfSen communication socket (<?php echo $COMMSOCKET; ?>) available.</div>
		<div id="checkitem2" class="checkitem" onclick="showHidePopup('checkitem2')">2. NfSen source directory (<?php echo $NFSEN_SOURCE_DIR; ?>) available.</div>
		<div id="checkitem3" class="checkitem" onclick="showHidePopup('checkitem3')">3. NfSen source file existance (<?php echo $nfsenSourceFiles; ?>*) available.</div>
		<div id="checkitem4" class="checkitem" onclick="showHidePopup('checkitem4')">4. GeoCoder database connection (<?php echo $geocoderDBFile ?>) available.</div>
		<div id="checkitem5" class="checkitem" onclick="showHidePopup('checkitem5')">5. GeoCoder database writability OK ('<?php echo $geocoderDBFile ?>' should be writable).</div>
		<div id="checkitem6" class="checkitem" onclick="showHidePopup('checkitem6')">6. IP2Location database (<?php echo $IP2LOCATION_PATH; ?>) available.</div>
		<div id="checkitem7" class="checkitem" onclick="showHidePopup('checkitem7')">7. MaxMind database (<?php echo $MAXMIND_PATH; ?>) available.</div>
		<div id="checkitem8" class="checkitem" onclick="showHidePopup('checkitem8')">8. Permissions of all directory contents OK.</div>
		<div id="checkitem9" class="checkitem" onclick="showHidePopup('checkitem10')">9. Additional NetFlow source selector (<?php echo $NFSEN_ADDITIONAL_SRC_SELECTORS; ?>) syntax OK.</div>
		<div id="checkitem10" class="checkitem" onclick="showHidePopup('checkitem11')">10. Map center coordinates (<?php echo $MAP_CENTER; ?>) syntax OK.</div>
		<div id="checkitem11" class="checkitem" onclick="showHidePopup('checkitem12')">11. Internal domain (<?php echo $INTERNAL_DOMAINS; ?>) syntax OK.</div>
		<div id="checkitem12" class="checkitem" onclick="showHidePopup('checkitem13')">12. PHP 'mbstring' module available.</div>
		
		<div id="popupOverlay" class="popupOverlay" style="display: none;"></div>
		<div id="popupContainer" class="popupContainer" style="display: none;">
			      <div id="popupTitle" class="popupTitle"></div>
				  <div id="popupBody" class="popupBody"></div>
		
		<script type="text/javascript">
			var useGeocoderDB = <?php echo $USE_GEOCODER_DB; ?>;
			var geolocationDB = "<?php echo $GEOLOCATION_DB; ?>";
		
			var nfsenSocketOK = <?php echo $nfsenSocketOK; ?>;
			var nfsenSourceDirOK = <?php echo $nfsenSourceDirOK; ?>;
			var nfsenSourceFileExistanceOK = <?php echo $nfsenSourceFileExistanceOK; ?>;
			var geocoderDatabaseConnectionOK = <?php echo $geocoderDatabaseConnectionOK; ?>;
			var geocoderDatabaseOK = <?php echo $geocoderDatabaseOK; ?>;
			var ip2LocationDatabaseOK = <?php echo $ip2LocationDBPathOK; ?>;
			var maxmindDatabaseOK = <?php echo $maxmindDBPathOK; ?>;
		
			var filePermissionsOK = <?php echo $filePermOK; ?>;
			var filesWithWrongPerm = "<?php echo $filesWithWrongPerm; ?>".split("___");
			
			var additionalSrcSelectorSyntaxOK = <?php echo $additionalSrcSelectorSyntaxOK; ?>;
			var mapCenterSyntaxOK = <?php echo $mapCenterSyntaxOK; ?>;
			var internalDomainSyntaxOK = <?php echo $internalDomainSyntaxOK; ?>;
			
			var mbstringModuleOK = <?php echo $mbstringModuleOK; ?>;
		
			// 1. Check NfSen socket
			if(nfsenSocketOK == 1) {
				document.getElementById("checkitem1").className += " checkitem_success";
			} else {
				document.getElementById("checkitem1").className += " checkitem_skip";
			}
			
			// 2. Check NfSen source directory
			if(nfsenSourceDirOK == 1) {
				document.getElementById("checkitem2").className += " checkitem_success";
			} else if((mode == 1 || mode == -1) && nfsenSourceDirOK == 0) {
				document.getElementById("checkitem2").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem2").className += " checkitem_skip";
			}
			
			// 3. Check NfSen source file existance
			if(nfsenSourceFileExistanceOK == 1) {
				document.getElementById("checkitem3").className += " checkitem_success";
			} else if((mode == 1 || mode == -1) && nfsenSourceFileExistanceOK == 0) {
				document.getElementById("checkitem3").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem3").className += " checkitem_skip";
			}
			
			// 4. Check Geocoder database connection
			if(geocoderDatabaseConnectionOK == 1) {
				document.getElementById("checkitem4").className += " checkitem_success";
			} else if(useGeocoderDB == 1 && geocoderDatabaseConnectionOK == 0) {
				document.getElementById("checkitem4").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem4").className += " checkitem_skip";
			}
			
			// 5. Check Geocoder database writability
			if(geocoderDatabaseOK == 1) {
				document.getElementById("checkitem5").className += " checkitem_success";
			} else if(useGeocoderDB == 1 && geocoderDatabaseOK == 0) {
				document.getElementById("checkitem5").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem5").className += " checkitem_skip";
			}
			
			// 6. Check IP2Location DB path
			if(ip2LocationDatabaseOK == 1) {
				document.getElementById("checkitem6").className += " checkitem_success";
			} else if(geolocationDB == "IP2Location" && ip2LocationDatabaseOK == 0) {
				document.getElementById("checkitem6").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem6").className += " checkitem_skip";
			}
			
			// 7. Check MaxMind DB path
			if(maxmindDatabaseOK == 1) {
				document.getElementById("checkitem7").className += " checkitem_success";
			} else if(geolocationDB == "MaxMind" && maxmindDatabaseOK == 0) {
				document.getElementById("checkitem7").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem7").className += " checkitem_skip";
			}
			
			// 8. Check file permissions
			if(filePermissionsOK == 1) {
				document.getElementById("checkitem8").className += " checkitem_success";
			} else {
				document.getElementById("checkitem8").className += " checkitem_failure";
				document.getElementById("checkitem8").innerHTML += "<br><br>Files with wrong permissions:<br>---<br>";
				for(i in filesWithWrongPerm) {
					// Delimiter between tuples: '___'
					// Delimiter inside tuple (between file name/path and permissions): '__'
					currentTuple = filesWithWrongPerm[i].split("__");
					document.getElementById("checkitem8").innerHTML += currentTuple[0] + " (" + currentTuple[1] + ")<br>";
				}
			}
			
			// 9. Check additional NetFlow source selector syntax
			if(additionalSrcSelectorSyntaxOK == 1) {
				document.getElementById("checkitem9").className += " checkitem_success";
			} else {
				document.getElementById("checkitem9").className += " checkitem_failure";
			}
			
			// 10. Check map center coordinates syntax
			if(mapCenterSyntaxOK == 1) {
				document.getElementById("checkitem10").className += " checkitem_success";
			} else {
				document.getElementById("checkitem10").className += " checkitem_failure";
			}
			
			// 11. Check internal domain syntax
			if(internalDomainSyntaxOK == 1) {
				document.getElementById("checkitem11").className += " checkitem_success";
			} else {
				document.getElementById("checkitem11").className += " checkitem_failure";
			}
			
			// 12. Check availability of 'mbstring' PHP module (for MaxMind API)
			if(mbstringModuleOK == 1) {
				document.getElementById("checkitem12").className += " checkitem_success";
			} else if(geolocationDB == "MaxMind" && mbstringModuleOK == 0) {
				document.getElementById("checkitem12").className += " checkitem_failure";
			} else {
				document.getElementById("checkitem12").className += " checkitem_skip";
			}
			
		   /**
			* Changes the color of the button (represented by its ID) in case of an onMouseOver event.
			*/
			function changeButtonOnMouseOver(buttonID) {
				var button = document.getElementById(buttonID);
				button.style.background = "#ffffff";
				button.style.color = "#736F6E";
				button.style.border = "1px solid #FFFFFF";
			}

		   /**
			* Changes the color of the button (represented by its ID) in case of an onMouseOut event.
			*/			
			function changeButtonOnMouseOut(buttonID) {
				var button = document.getElementById(buttonID);
				button.style.background = "";
				button.style.color = "";
				button.style.border = "";
			}			
			
		   /**
			* This function shows the popup overlay in case it is hidden, or hides it is case it is
			* shown.
			* Parameters:
			*		type - indicates which contents should be shown inside the popup overlay. The possible
			*				options are:
			*					1. 'about' - shows an about window
			*					2. 'help' - shows the SURFmap help
			*					3. 'invalidWindow' - shows an error message in case something is wrong with
			*					   			the window settings
			*/			
			function showHidePopup(type) {
				var popupOverlay = document.getElementById('popupOverlay');
				var popupContainer = document.getElementById('popupContainer');
				var popupTitle = document.getElementById('popupTitle');
				var popupTitleButtons = document.getElementById('popupTitleButtons');
				var popupBody = document.getElementById('popupBody');

				if(popupOverlay.style.display == "none" || popupContainer.style.display == "none") {
					popupOverlay.style.display = "";
					popupContainer.style.display = "";
					popupContainer.style.top = "200px";
					popupContainer.style.minWidth = "400px";
					popupContainer.style.maxWidth = "400px";
					
					var closeButton = "<span id='popupTitleButtonsCLOSE' class='popupTitleButtons' onclick='showHidePopup()' onmouseover='changeButtonOnMouseOver(\"popupTitleButtonsCLOSE\")' onmouseout='changeButtonOnMouseOut(\"popupTitleButtonsCLOSE\")'>Close</span>";
					
					if(type == "checkitem1") {
						popupTitle.innerHTML = "NfSen communication socket " + closeButton;
						popupBody.innerHTML = "The path to the NfSen communication socket can normally be found in NfSen's [conf.php] file, which is located in the NfSen's main directory. The default variable name is '$COMMSOCKET'. The path to NfSen's communication socket should be exactly the same as in the mentioned NfSen configuration file.";	
					} else if(type == "checkitem2") {
						popupTitle.innerHTML = "NfSen source directory " + closeButton;
						popupBody.innerHTML = "This path should lead to NfSen's network data capture folder. How this path should be configured, can be partially found in NfSen's [nfsen.conf] configuration file. The first part of this path is stored in the '$PROFILEDATADIR' variable. After that, you need to add the profile name and the source name.<br><br>Example:<br><b>$PROFILEDATADIR</b>: /vserver/nfsen/NFSENPROFILE<br><b>Profile name</b>: live<br><b>Source name</b>:institution<br><b>Resulting configuration</b>: /vserver/nfsen/NFSENPROFILE/live/institution";
					} else if(type == "checkitem3") {
						popupTitle.innerHTML = "NfSen source file existance " + closeButton;
						popupBody.innerHTML = "This is the (prefix of the) main file name, without a date and time indication.";
					} else if(type == "checkitem4") {
						popupTitle.innerHTML = "GeoCoder database connection " + closeButton;
						popupBody.innerHTML = "In case a GeoCoder (caching) database is selected to be used, a proper database connection should be configured. Please verify the host name and port number of the database server.";
					} else if(type == "checkitem5") {
						popupTitle.innerHTML = "GeoCoder database writability " + closeButton;
						popupBody.innerHTML = "The (SQLite) database file should have the correct permissions in order to be writable.";
					} else if(type == "checkitem6") {
						popupTitle.innerHTML = "IP2Location database " + closeButton;
						popupBody.innerHTML = "In case you selected 'IP2Location' as your geolocation database, please verify the path to the database file. Please note that this should be the absolute path to the database's binary file.";
					} else if(type == "checkitem7") {
						popupTitle.innerHTML = "MaxMind database " + closeButton;
						popupBody.innerHTML = "In case you selected 'MaxMind' as your geolocation database, please verify the path to the database file. Please note that this should be the absolute path to the database's binary file.";
					} else if(type == "checkitem8") {
						popupTitle.innerHTML = "File permissions " + closeButton;
						popupBody.innerHTML = "All files need to be (at least) readable by your Web server. Otherwise the PHP engine will not be able to successfully execute and process the SURFmap source files. Please make sure that all permissions are set correctly. Please note that some Web server configurations require PHP files to be executable in order to be processed.";
					} else if(type == "checkitem9") {
						popupTitle.innerHTML = "Additional Netflow source selector syntax " + closeButton;
						popupBody.innerHTML = "This setting should consist of an empty String in case only the primary source needs to be used (so no additional source). If more than one additional source is used, they should be separated by a semi-colon (;).";
					} else if(type == "checkitem10") {
						popupTitle.innerHTML = "Map center syntax " + closeButton;
						popupBody.innerHTML = "This setting should consist consist of two (decimal) values, separated by a comma (,). The first value represents the latitude coordinate, the second value represents the longitude coordinate. The latitude coordinate can have a value on the interval <-90.0, 90.0>, the longitude coordinate can have a value on the interval <-180.0, 180.0>.";
					} else if(type == "checkitem11") {
						popupTitle.innerHTML = "Internal domain syntax " + closeButton;
						popupBody.innerHTML = "This setting contains the internal domain of the NetFlow exporter. Multiple domains need to be separated by a semi-colon (;). To be certain that the syntax is OK (the Configuration Checker only checks a few possible errors), please check it in the 'Filter' field of NfSen's \"Details\" page.";
					} else if(type == "checkitem12") {
						popupTitle.innerHTML = "PHP 'mbstring' module available" + closeButton;
						popupBody.innerHTML = "In case you selected 'MaxMind' as your geolocation database, you should have PHP's 'mbstring' module installed, in order to get the MaxMind API to work.";
					}
				} else {
					popupOverlay.style.display = "none";
					popupContainer.style.display = "none";
				}
			}
		</script>
	</body>
</html>