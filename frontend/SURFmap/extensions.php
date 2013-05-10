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
        new ExtensionField('Geolocation method','Geo_method',   '%loc_method'),
        new ExtensionField('Timestamp',         'Timestamp',    '%loc_timestamp'),
        new ExtensionField('Latitude (int)',    'Lat (int)',    '%loc_lat_int'),
        new ExtensionField('Latitude (dec)',    'Lat (lng)',    '%loc_lat_dec'),
        new ExtensionField('Longitude (int)',   'Lng (int)',    '%loc_lng_int'),
        new ExtensionField('Longitude (dec)',   'Lng (lng)',    '%loc_lng_dec')
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