<?php
	
	function SURFmap_ParseInput( $plugin_id ) {
		$_SESSION['refresh'] = 0;
	}

	function SURFmap_Run( $plugin_id ) {
		echo "<iframe id='surfmapParentIFrame' src='plugins/SURFmap/index.php' frameborder='0' style='width:100%; height:100%'>Your browser does not support iframes.</iframe>";	
	}

?>