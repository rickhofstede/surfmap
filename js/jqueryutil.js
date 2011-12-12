/*******************************
 * jqueryutil.js [SURFmap]
 * Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 * University of Twente, The Netherlands
 *
 * LICENSE TERMS: BSD-license.html
 *******************************/

   /**
	* Prepares a jQuery alert.
	*
	* Parameters:
	*		type - indicates which contents should be shown inside the dialog. The possible
	*				options are:
	*					1. 'filterError'
	*/	
	function generateAlert(type) {
		if ($("#dialog").dialog("isOpen")) {
			$("#dialog").dialog("destroy");
		}
		
		if (type == "filterError") {
			jAlert("The filter you provided does not adhere to the expected syntax.<br /><br /><b>Filter</b>: " + nfsenFilter + "<br /><b>Error message</b>: " +  getErrorMessage() + "</br /><br />Please check <a href='http://nfdump.sourceforge.net/' style='text-decoration:underline;' target='_blank'>http://nfdump.sourceforge.net/</a> for the filter syntax.", "Filter error");
		} else if (type == "noDataError") {
			jAlert("No NetFlow data has been found for the selected profile, source and filter. Please change your settings.", "No data available");
		} else if (type == "profileError") {
			jAlert("You have an error in your configuration. <br /><br /><b>Error message</b>: " +  getErrorMessage(), "Error");
		} else if (type == "invalidWindow") {
			if (getErrorCode() == 2) {
				// The first (normal) selected date/time is invalid.
				jAlert("The selected date/time window (" + originalDate1Window + " " + originalTime1Window + ") does not exist.<br /><br />The last available/valid time window will be selected.", "Error");
			} else if (getErrorCode() == 3) {
				// The second (time range) selected date/time is invalid.
				jAlert("The (second) selected date/time window (" + originalDate2Window + " " + originalTime2Window + ") does not exist.<br /><br />The last available/valid time window will be selected.", "Error");
			} else if (getErrorCode() == 4) {
				// The second (time range) selected date/time is invalid.
				jAlert("Both selected date/time windows (" + originalDate1Window + " " + originalTime1Window + " - " + originalDate2Window + " " + originalTime2Window + ") do not exist.<br /><br />The last available/valid time window will be selected.", "Error");
			}
		} else if (type = "noSourcesSelectedError") {
			jAlert("You have no source selected, while you should have selected at least one.", "Error");
		} else {
			jAlert("An unknown error occured.", "Error");
		}
	}

   /**
	* Prepares a jQuery dialog of the specified type.
	*
	* Parameters:
	*		type - indicates which contents should be shown inside the dialog. The possible
	*				options are:
	*					1. 'about' - shows an about window
	*					2. 'help' - shows the SURFmap help
	*					3. 'license' -
	*					4. 'netflowDetails' -
	*					5. 'progressBar' -
	*					6. 'configurationCheckerHelp' - the value in the 'text' variable 
	*							should be splitted by "##" to separate title and contents
	*							of the dialog
	*		text - text which should be indicated inside the dialog
	*/			
	function generateDialog(type, text) {
		if ($("#dialog").dialog("isOpen")) {
			$("#dialog").dialog("destroy");
		}
		
		if (type == "about") {
			document.getElementById("dialog").setAttribute("title", "About");
			document.getElementById("dialog").innerHTML = "SURFmap has been developed by:<br /><br />Rick Hofstede<br />Anna Sperotto, Tiago Fioreze<br /><br /><i>University of Twente, The Netherlands</i><br /><br />SURFmap is available on <a href=\"http://sourceforge.net/p/surfmap\" target=\"_blank\" style=\"text-decoration:underline;\">SourceForge</a> and is distributed under the <a href=\"javascript:generateDialog('license')\" style=\"text-decoration:underline;\">BSD license</a>.<br /><br />Special thanks to Pavel Celeda from INVEA-TECH, for his valuable contributions.<br /><br />";

			if (GEOLOCATION_DB == "IP2Location") document.getElementById("dialog").innerHTML += "<table style='width:300px; font-size:80%;'><tr><td>You are using the following geolocation service:</td><td><img src='images/ip2location.gif' alt='IP2Location' style='width:130px;' /></td></tr></table><br />";
			else if (GEOLOCATION_DB == "MaxMind") document.getElementById("dialog").innerHTML += "<table style='width:300px; font-size:80%;'><tr><td>You are using the following geolocation service:</td><td><img src='images/maxmind.png' alt='MaxMind' style='width:130px;' /></td></tr></table><br />";
			else if (GEOLOCATION_DB == "geoPlugin") document.getElementById("dialog").innerHTML += "<table style='width:300px; font-size:80%;'><tr><td>You are using the following geolocation service:</td><td><img src='images/geoplugin.jpg' alt='geoPlugin' style='width:130px;' /></td></tr></table><br />";
			
			document.getElementById("dialog").innerHTML += "<div style='font-size:80%;'>Application version: " + applicationVersion + "</div>";	
			showDialog("auto", 350, "center", false, true);
		} else if (type == "help") {
			document.getElementById("dialog").setAttribute("title", "Help");
			document.getElementById("dialog").innerHTML = "Welcome to the SURFmap help. Some main principles of SURFmap are explained here.<br /><br />"
				+ "<table border = '0'>"
					+ "<tr><td width = '100'><b>Marker</b></td><td>Markers represent hosts and show information about them, such as IPv4 addresses and the country, region and city they're located in. A green marker indicates the presence of a flow of which the source and destination are located 'inside' the same marker.<hr /></td></tr>"
					+ "<tr><td><b>Line</b></td><td>Lines represent a flow between two hosts (so between markers) and show information about that flow, like the geographical information of the two end points, the exchanged amount of packets, octets and throughput per flow.<hr /></td></tr>"
					+ "<tr><td><b>Zoom levels table</b></td><td>This tables shows the current zoom level. The four zoom levels are also clickable, so that you can zoom in or out to a particular zoom level directly.<hr /></td></tr>"
					+ "<tr><td><b>NfSen options</b></td><td>The main NfSen options - <i>List Flows</i> or <i>Stat TopN</i> - can be set here. The first option lists the first N flows of the selected time slot (N and the selected time slot will be discussed later). <i>Stat TopN</i> shows top N statistics about the network data in the selected time slot. The value of N can be set in the <i>Limit to</i> field, while the time slot can be set in the <i>Begin</i> and <i>End</i> fields.</td></tr>"
					+ "</table>";
			showDialog("auto", 500, "center", false, true);				
		} else if (type == "license") {
			document.getElementById("dialog").setAttribute("title", "SURFmap license");
			document.getElementById("dialog").innerHTML = "The SURFmap project is distributed under the BSD license:<br>"
				+ "<br>"
				+ "Copyright (c) 2011, Rick Hofstede (University of Twente, The Netherlands)<br>"
				+ "All rights reserved.<br>"
				+ "<br>"
				+ "Redistribution and use in source and binary forms, with or without <br>"
				+ "modification, are permitted provided that the following conditions are met:<br>"
				+ "<br>"
				+ "&nbsp; * Redistributions of source code must retain the above copyright notice, <br>"
				+ "&nbsp;&nbsp;&nbsp;&nbsp; this list of conditions and the following disclaimer.<br>"
				+ "&nbsp; * Redistributions in binary form must reproduce the above copyright notice, <br>"
				+ "&nbsp;&nbsp;&nbsp;&nbsp; this list of conditions and the following disclaimer in the documentation <br>"
				+ "&nbsp;&nbsp;&nbsp;&nbsp; and/or other materials provided with the distribution.<br>"
				+ "&nbsp; * Neither the name of Rick Hofstede, nor the name of the University of Twente, <br>"
				+ "&nbsp;&nbsp;&nbsp;&nbsp; nor the names of its contributors may be <br>"
				+ "&nbsp;&nbsp;&nbsp;&nbsp; used to endorse or promote products derived from this software without <br>"
				+ "&nbsp;&nbsp;&nbsp;&nbsp; specific prior written permission.<br>"
				+ "<br>"
				+ "THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS \"AS IS\" <br>"
				+ "AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE <br>"
				+ "IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE <br>"
				+ "ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE <br>"
				+ "LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR <br>"
				+ "CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF <br>"
				+ "SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS <br>"
				+ "INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN <br>"
				+ "CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) <br>"
				+ "ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE <br>"
				+ "POSSIBILITY OF SUCH DAMAGE.<br>";
			showDialog("auto", "auto", "center", false, true);
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

			if (nfsenOption == 1) {
				showDialog(dialogHeight, "auto", "center", false, true);
			} else {
				showDialog(dialogHeight, "auto", "center", false, true);
			}
		} else if (type == "progressBar") {
			document.getElementById("dialog").setAttribute("title", "Loading...");
			document.getElementById("dialog").innerHTML = "<div style='margin-top: 6px; width:400px;' id='progressbar'></div>";
			showDialog(80, 450, "center", true, false);
			showProgressBar("progressbar", 0, "");
		} else if (type == "configurationCheckerHelp") {
			var splittedString = text.split("##");
			document.getElementById("dialog").setAttribute("title", splittedString[0]);
			document.getElementById("dialog").innerHTML = splittedString[1];
			showDialog("auto", 350, "center", false, true);
		}
	}
	
   /**
	* Shows the actual dialog using jQuery.
	*
	* Parameters:
	*		height - Height of the jQuery dialog, can also be 'auto'
	*		width - Width of the jQuery dialog, can also be 'auto'
	*		position - Position of the dialog. Can be 'top', 'bottom', 'left', 'right', 'center'
	*	    hideCloseButton - Hides the close button (X) when the dialog is opened
	*		closeOnEsc - (bool) Enables/disables the 'ESC' button to dismiss the dialog
	*/	
	function showDialog(height, width, position, hideCloseButton, closeOnEsc) {
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
			closeOnEscape: closeOnEsc
		});
	}

   /**
	* Shows a progress bar using jQuery.
	*
	* Parameters:
	*		id - ID of the div element, which needs to be converted to a progress bar
	*		initialValue - Initial value of the progress bar
	*		initialText - Initial text in the progress bar
	*/	
	function showProgressBar(id, initialValue, initialText) {
		$("#" + id).progressbar({
			value: initialValue
		});
		$("<div id=\"progressbartext\" style=\"color:#797979; font-weight:bold; margin-top:3px; margin-left:10px; float:left;\">" + initialText + "</div>").insertBefore(".ui-progressbar-value");
		$(".ui-progressbar-value").css("text-align", "center");
		$("#progressbartext").css("margin-left", "140px");
	}

   /**
	* Gets the value of the jQuery progress bar.
	*/
	function getProgressBarValue() {
		return $("#progressbar").progressbar("value");
	}

   /**
	* Sets the value of the jQuery progress bar to the specified value.
	*
	* Parameters:
	*		value - Value to which the progress bar should be set
	*		text - Text in the progress bar
	*/
	function setProgressBarValue(value, text) {
		$("#progressbar").progressbar("option", "value", value);
		$("#progressbartext").text(text);
	}
