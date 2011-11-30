<?php
/*******************************
 * loghandler.php [SURFmap]
 * Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 * University of Twente, The Netherlands
 *
 * LICENSE TERMS: outlined in BSD-license.html
 *******************************/
	
	class LogHandler {
		
		/**
		 * Constructs a new LogHandler object.
		 */
		function __construct() {
			$this->infoQueue = "";
			$this->errorQueue = "";
		}
		
		function getInfo() {
			return $this->infoQueue;
		}
		
		function writeInfo($info) {
			$this->infoQueue .= (strlen($this->infoQueue) == 0) ? $info : "##".$info;
		}
		
		function getError() {
			return $this->errorQueue;
		}
		
		function writeError($error) {
			$this->errorQueue .= (strlen($this->errorQueue) == 0) ? $error : "##".$error;
		}
		
	}
	
	function ReportLog($message) {
		// dummy function to avoid PHP errors
	}

?>