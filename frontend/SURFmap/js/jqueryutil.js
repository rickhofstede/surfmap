/******************************
 # jqueryutil.js [SURFmap]
 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *******************************/

   /*
	* Prepares a jQuery alert.
	*
	* Parameters:
	*		code - error code
	*/	
	function generateAlert(code) {
		if ($("#dialog").dialog("isOpen")) {
			$("#dialog").dialog("destroy");
		}
		
		switch (code) {
			case 1:		jAlert("The flow filter you provided does not adhere to the expected syntax.<br /><br /> \
								<b>Filter</b>: " + flowFilter + "<br /> \
								<b>Error message</b>: " +  errorMessage + "</br /><br /> \
								Please check <a href='http://nfdump.sourceforge.net/' style='text-decoration:underline;' target='_blank'>http://nfdump.sourceforge.net/</a> for the filter syntax.", "Filter error");
						break;
						
			case 2:		// The first (normal) selected date/time is invalid.
						jAlert("The selected date/time window (" + originalDate1Window + " " + originalTime1Window + ") does not exist.<br /><br /> \
								The last available/valid time window will be selected.", "Error");
						break;
						
			case 3:		// The second (time range) selected date/time is invalid.
						jAlert("The (second) selected date/time window (" + originalDate2Window + " " + originalTime2Window + ") does not exist.<br /><br /> \
								The last available/valid time window will be selected.", "Error");
						break;
						
			case 4:		// The second (time range) selected date/time is invalid.
						jAlert("Both selected date/time windows (" + originalDate1Window + " " + originalTime1Window + " - " + originalDate2Window + " " + originalTime2Window + ") do not exist.<br /><br /> \
								The last available/valid time window will be selected.", "Error");
						break;
								
			case 5:		jAlert("No NetFlow data has been found for the selected profile, source and filter. Please change your settings.", "No data available");
						break;
						
			case 6:		jAlert("You have an error in your configuration. <br /><br /><b>Error message</b>: " +  errorMessage, "Error");
						break;
						
			case 7:		jAlert("The geo filter you provided does not adhere to the expected syntax.<br /><br /> \
								<b>Filter</b>: " + geoFilter + "<br /> \
								<b>Error message</b>: " +  errorMessage + "</br /><br /> \
								Please check the SURFmap manual for the filter syntax.", "Filter error");
						break;
			
			case 8:		jAlert("You have killed your flow query. Please select another query.", "Error");
						break;
						
			case 9:		jAlert("The NfSen session has not been set properly. Please start SURFmap from the 'Plugins' tab in NfSen.", "Error");
						break;
						
			case 999:	 // Error code is client-side-only
						jAlert("You have no source selected, while you should have selected at least one.", "Error");
						break;
						
			default:	jAlert("An unknown error occured.", "Error");
						break;
		}
	}

   /*
	* Prepares a jQuery dialog of the specified type.
	*
	* Parameters:
	*		type - indicates which contents should be shown inside the dialog. The possible
	*				options are:
	*					1. 'about' - shows an about window
	*					2. 'help' - shows the SURFmap help
	*					3. 'license' -
	*					4. 'netflowDetails' -
	*					5. 'processing' -
	*					6. 'configurationCheckerHelp' - the value in the 'text' variable 
	*							should be splitted by "##" to separate title and contents
	*							of the dialog
	*		text - text which should be indicated inside the dialog
	*/			
	function showDialog(type, text) {
		if ($("#dialog").dialog("isOpen")) $("#dialog").dialog("destroy");
		
		if (type == "about") {
			document.getElementById("dialog").setAttribute("title", "About");
			document.getElementById("dialog").innerHTML = " \
					SURFmap has been developed by:<br /><br /> \
					Rick Hofstede<br /> \
					<i>University of Twente, The Netherlands</i><br /><br /><hr /> \
					Third-party software: \
					<ul> \
						<li><a href=\"http://www.erichynds.com/jquery/jquery-ui-multiselect-widget/\" target=\"_blank\" style=\"text-decoration:underline;\">jQuery UI MultiSelect Widget</a> / Eric Hynds</li> \
						<li><a href=\"http://trentrichardson.com/examples/timepicker/\" target=\"_blank\" style=\"text-decoration:underline;\">jQuery UI Datepicker</a> / Trent Richardson</li> \
						<li><a href=\"http://www.abeautifulsite.net/blog/2008/12/jquery-alert-dialogs/\" target=\"_blank\" style=\"text-decoration:underline;\">jQuery Alert Dialogs Plugin</a> / A Beautiful Site</li> \
					</ul><hr /><br /> \
					SURFmap is available on <a href=\"http://sourceforge.net/p/surfmap\" target=\"_blank\" style=\"text-decoration:underline;\">SourceForge</a> \
					and is distributed under the <a href=\"javascript:showDialog('license')\" style=\"text-decoration:underline;\">BSD license</a>.<br /><br /> \
					Special thanks to Pavel Celeda from INVEA-TECH, for his valuable contributions.<br /><br />";

			if (GEOLOCATION_DB == "IP2Location") {
				document.getElementById("dialog").innerHTML += " \
						<table style='width:300px; font-size:80%;'> \
							<tr> \
								<td>You are using the following geolocation service:</td> \
								<td><img src='images/ip2location.gif' alt='IP2Location' style='width:130px;' /></td> \
							</tr> \
						</table><br />";
			} else if (GEOLOCATION_DB == "MaxMind") {
				document.getElementById("dialog").innerHTML += " \
						<table style='width:300px; font-size:80%;'> \
							<tr> \
								<td>You are using the following geolocation service:</td> \
								<td><img src='images/maxmind.png' alt='MaxMind' style='width:130px;' /></td> \
							</tr> \
						</table><br />";
			}
			
			document.getElementById("dialog").innerHTML += "<div style='font-size:80%;text-align:center;'>Application version: " + applicationVersion + "</div>";	
			createDialog(400, "auto", "center", false, true, false, true);
		} else if (type == "help") {
			document.getElementById("dialog").setAttribute("title", "Help");
			document.getElementById("dialog").innerHTML = "Welcome to the SURFmap help. Some main principles of SURFmap are explained here.<br /><br />"
				+ "<table border = '0'>"
					+ "<tr><td width = '100'><b>Marker</b></td><td>Markers represent hosts and show information about them, such as IPv4 addresses and the country, region and city they're located in. A green marker indicates the presence of a flow of which the source and destination are located 'inside' the same marker.<hr /></td></tr>"
					+ "<tr><td><b>Line</b></td><td>Lines represent a flow between two hosts (so between markers) and show information about that flow, like the geographical information of the two end points, the exchanged amount of packets, octets and throughput per flow.<hr /></td></tr>"
					+ "<tr><td><b>Zoom levels table</b></td><td>This tables shows the current zoom level. The four zoom levels are also clickable, so that you can zoom in or out to a particular zoom level directly.<hr /></td></tr>"
					+ "<tr><td><b>NfSen options</b></td><td>The main NfSen options - <i>List Flows</i> or <i>Stat TopN</i> - can be set here. The first option lists the first N flows of the selected time slot (N and the selected time slot will be discussed later). <i>Stat TopN</i> shows top N statistics about the network data in the selected time slot. The value of N can be set in the <i>Limit to</i> field, while the time slot can be set in the <i>Begin</i> and <i>End</i> fields.</td></tr>"
					+ "</table>";
			createDialog(500, "auto", "center", false, true, false, true);				
		} else if (type == "license") {
			document.getElementById("dialog").setAttribute("title", "License");
			$("#dialog").load("license.html #license", function() {
				// Don't show dialog before contents have been loaded
				createDialog("auto", "auto", "center", false, true, false, true);
			});
		} else if (type == "netflowDetails") {
			document.getElementById("dialog").setAttribute("title", "Details");
			document.getElementById("dialog").innerHTML = text;
			
			var tableRows = 0;
			var pos = 0;
			while(pos < text.length && text.indexOf("<tr>", pos) != -1) {
				tableRows++;
				pos = text.indexOf("<tr>", pos) + 4; // "<tr>".length = 4
			}
			tableRows--; // Table header was also counted, and is not a body row
			
			// Using 'height: auto' together width 'maxHeight' does not work properly (jQuery UI bug #4820): http://bugs.jqueryui.com/ticket/4820)
			var headerHeight = 70;
			var rowHeight = 15;
			var dialogHeight = (headerHeight + (tableRows * rowHeight) > 450) ? 450 : headerHeight + (tableRows * rowHeight);

			createDialog("auto", dialogHeight, "center", false, true, false, true);
		} else if (type == "processing") {
			document.getElementById("dialog").innerHTML = " \
					<div id='processing' style='text-align:center; clear:both;'> \
						<img src='images/load.gif' alt='Loading SURFmap'><br /> \
						<div id='processingText' style='font-size:8pt; margin-top:15px;'></div> \
					</div>";
			createDialog(250, 80, "center", true, false, true, false);
			setProcessingText("Loading...");
		} else if (type == "configurationCheckerHelp") {
			var splittedString = text.split("##");
			document.getElementById("dialog").setAttribute("title", splittedString[0]);
			document.getElementById("dialog").innerHTML = splittedString[1];
			createDialog(350, "auto", "center", false, true, false, true);
		}
	}
	
   /*
	* Shows the actual dialog using jQuery.
	*
	* Parameters:
	*		width - Width of the jQuery dialog, can also be 'auto'
	*		height - Height of the jQuery dialog, can also be 'auto'
	*		position - Position of the dialog. Can be 'top', 'bottom', 'left', 'right', 'center'
	*	    hideCloseButton - Hides the close button (X) when the dialog is opened
	*		closeOnEsc - (bool) Enables/disables the 'ESC' button to dismiss the dialog
	*		hideTitle - (bool) Shows/hides the title of the dialog
	*		resizable - (bool) Enables/disables resizability of the dialog
	*/	
	function createDialog(width, height, position, hideCloseButton, closeOnEsc, hideTitle, resizable) {
		// When dialog is closed, set it back to initial state
		$("#dialog").dialog({
			open: function(event, ui) {
				if (hideCloseButton == true) $('.ui-dialog-titlebar-close').hide();
			},
			close: function(event, ui) {
				$("#dialog").dialog("destroy");
			},
			modal: true,
			position: position,
			width: width,
			height: height,
			closeOnEscape: closeOnEsc,
			resizable: resizable,
			dialogClass: hideTitle ? 'noTitleBar' : ''
		});
	}

   /*
	* Adjusts the text shown in the progress indicator.
	*
	* Parameters:
	*		text - text in the progress indicator
	*/
	function setProcessingText(text) {
		$("#processingText").text(text);
	}
