<?php
    /*******************************
	 # extensions.php [SURFmap]
	 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
	 # University of Twente, The Netherlands
	 #
	 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
	 *******************************/
    
    // Available extensions
    $EX_LOC = new Extension('Location-aware exporting', array(
        new ExtensionField('Geolocation method', 'Geo_method', '%location_gm'),
        new ExtensionField('Timestamp', 'Timestamp', '%location_ts'),
        new ExtensionField('Latitude', 'Lat', '%location_lat'),
        new ExtensionField('Longitude', 'Lng', '%location_lng'),
    ));
	
    // Enabled extensions (comma-separated)
	$extensions = array();
    
    // -----
    
    class Extension {
        var $name;
        var $fields; // array
        
        public function __construct ($name, $fields) {
            $this->name = $name;
            $this->fields = $fields;
        }
    }
    
    class ExtensionField {
        var $full_name;
        var $short_name;
        var $nfdump_short;
        
        public function __construct ($full_name, $short_name, $nfdump_short) {
            $this->full_name = $full_name;
            $this->short_name = $short_name;
            $this->nfdump_short = $nfdump_short;
        }
    }
	
?>