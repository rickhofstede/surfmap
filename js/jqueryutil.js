/*******************************
 * jqueryutil.js [SURFmap]
 * Author: Rick Hofstede
 * University of Twente, The Netherlands
 *******************************/

   /**
	* Hides the accordion element having the specified ID.
	*
	* Parameters:
	*		id - ID of the accordion element, which needs to be hidden
	*/	
    function hideAccordionElement(id) {
		closeAccordionElement(id);
		$("#" + id).hide();
	}
	
   /**
	* Closes the accordion element having the specified ID.
	*
	* Parameters:
	*		id - ID of the accordion element, which needs to be opened
	*/	
	function closeAccordionElement(id) {
		// Remove classes
		$("#" + id).toggleClass("ui-state-focus", false);
		$("#" + id).toggleClass("ui-state-active", false);
		$("#" + id).toggleClass("ui-corner-top", false);

		// Add classes
		$("#" + id).toggleClass("ui-state-default", true);
		$("#" + id).toggleClass("ui-corner-all", true);

		// Change class
		$("#" + id).find("span").toggleClass("ui-icon-triangle-1-s", false);		
		$("#" + id).find("span").toggleClass("ui-icon-triangle-1-e", true);

		// Remove class
		$("#" + id + "_content").toggleClass("ui-accordion-content-active", false);
		$("#" + id + "_content").hide();
	}	
	
   /**
	* Opens the accordion element having the specified ID.
	*
	* Parameters:
	*		id - ID of the accordion element, which needs to be opened
	*/	
	function openAccordionElement(id) {
		// Remove classes
		$("#" + id).toggleClass("ui-state-default", false);
		$("#" + id).toggleClass("ui-corner-all", false);
		
		// Add classes
		$("#" + id).toggleClass("ui-state-focus", true);
		$("#" + id).toggleClass("ui-state-active", true);
		$("#" + id).toggleClass("ui-corner-top", true);
		
		// Change class
		$("#" + id).find("span").toggleClass("ui-icon-triangle-1-e", false);
		$("#" + id).find("span").toggleClass("ui-icon-triangle-1-s", true);
		
		// Add class
		$("#" + id + "_content").toggleClass("ui-accordion-content-active", true);
		$("#" + id + "_content").show();
	}
	
   /**
	* Shows the actual dialog using jQuery.
	*
	* Parameters:
	*		id - ID of the div element, which needs to be converted to a dialog
	*		height - Height of the jQuery dialog, can also be 'auto'
	*		width - Width of the jQuery dialog, can also be 'auto'
	*		position - Position of the dialog. Can be 'top', 'bottom', 'left', 'right', 'center'
	*	    hideCloseButton - Hides the close button (X) when the dialog is opened
	*/	
	function showDialog(id, height, width, position, hideCloseButton) {
		// When dialog is closed, set it back to initial state
		$("#" + id).dialog({
			open: function(event, ui) {
				if(hideCloseButton == true) $('.ui-dialog-titlebar-close').hide();
			},
			close: function(event, ui) {
				$("#dialog").dialog("destroy");
			},
			modal: true,
			position: position,
			width: width,
			height: height
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
		$(".ui-progressbar-value").append("<div id=\"progressBarText\" style=\"color:#797979; margin-top:3px;\">" + initialText + "</div>");
		$(".ui-progressbar-value").css("text-align", "center");
	}

   /**
	* Sets the value of the jQuery progress bar to the specified value.
	*
	* Parameters:
	*		id - ID of the jQuery progress bar
	*		value - Value to which the progress bar should be set
	*		value - Text in the progress bar
	*/
	function setProgressBarValue(id, value, text) {
		$("#" + id).progressbar("option", "value", value);
		$("#progressBarText").text(text);
	}
