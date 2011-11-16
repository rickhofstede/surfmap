SURFmap -- A Network Monitoring Tool Based on the Google Maps API

Please check the manual for installation instructions. Run the ConfigurationChecker module (which can be found in the 'setup' folder), in order to verify your SURFmap configuration.

---

Release notes [v2.1 dev (20111116)]:
- [FEATURE] Added support for source selection in user interface
- [FEATURE] Added support for NfSen shadow profiles
- [FEATURE] Improved alerting
- [FEATURE] Simplified SURFmap setup
- [FEATURE] Added an option to set the default zoom level
- [BUGFIX] Fixed a bug causing incorrect warning messages to be shown
- [BUGFIX] Fixed a bug causing status in the progress bar to be incorrectly wrapped
- [BUGFIX] Fixed a timing bug, caused by accessing incompletely initialized JS objects
- [BUGFIX] Improved compatibility with Internet Explorer 8
- [BUGFIX] Improved stability when handling NfSen shadow profiles
- [BUGFIX] Improved layout on Linux systems
- [BUGFIX] Numerous bugfixes and improvements

Release notes [20111024]:

WARNING: please check the manual for installation instructions (Chapter 2.1).

- [FEATURE] It is now possible to geolocate NATed setups with static location information
- [FEATURE] Added session support, in order to maintain settings when reloading pages
- [FEATURE] Added an option to force SURFmap to use HTTPS for downloading maps from Google
- [FEATURE] Migrated SQLite connectivity to PHP PDO. SURFmap supports both SQLite2 and SQLite3
- [FEATURE] Enhanced layout, in order to have a bigger map
- [FEATURE] Enhanced browser support
- [FEATURE] Greatly improved geocoding process
- [FEATURE] Improved handling of characters with accents
- [FEATURE] Improved logging functionality
- [FEATURE] Simplified configuration
- [FEATURE] Added support for enabling/disabling auto-refresh
- [FEATURE] Marker information window show flow details now as well
- [FEATURE] Updated MaxMind plugin to v1.9
- [BUGFIX] Greatly improved overall stability in case of empty data sets, erroneous filter, etc.
- [BUGFIX] Many bugfixes and improvements

Release notes [20110929]:
- [BUGFIX] Fixed an issue in the ConfigurationChecker, which caused checking procedures to fail when the PHP module 'mysql' was not available on the system
- [BUGFIX] Fixed an issue which caused problems in the geocoding process when no offline geocoding database was enabled

Release notes [20110923]:
- [FEATURE] Information windows are now moving to the line end points after jumping there
- [BUGFIX] Improved stability when a flow count of 0 is selected or when the data set consists of zero flow records
- [BUGFIX] Fixed an issue which caused a problem when jumping to line ends
- [BUGFIX] Fixed an issue which caused a Javascript alert to appear after certain zoom actions
- [BUGFIX] Fixed an issue which caused the information windows of lines to show incorrect data at the HOST zoom level
- [BUGFIX] Fixed an issue which caused the zooming functions of the information windows not to work properly

Release notes [20110916]:
- [FEATURE] Updated jQuery to v1.6.2
- [FEATURE] Updated jQuery UI to v1.8.16
- [BUGFIX] Minor bugfixes and improvements

Release notes [20110603]:
- [FEATURE] Migrated the geocoded cache DB from MySQL to SQLite. Please run the ConfigurationChecker module to verify your settings, before running SURFmap
- [FEATURE] Migrated Google Maps API to v3
- [FEATURE] Updated jQuery UI to v1.8.13
- [BUGFIX] Minor bugfixes and improvements

Release notes [20110508]:
- [FEATURE] Made the default amount of flow records to be selected configurable ($DEFAULT_FLOW_RECORD_COUNT)
- [FEATURE] The ConfigurationChecker module is now also verifying installed PHP modules
- [FEATURE] Improved support for dynamic map sizes. If you change the 'SCREEN_WIDTH' setting, make sure to adapt the iFrame sizes in SURFmap.php as well (see Chapter 2.1.2 of the manual)
- [FEATURE] Updated jQuery UI to v1.8.12
- [BUGFIX] Fixed an issue in the ConfigurationChecker module, which caused the verification of nfcapd source file existence not to complete successfully
- [BUGFIX] Minor bugfixes and improvements

Release notes [20110402]:
- [FEATURE] Added support for the MaxMind GeoLite geolocation service. Please check the SURFmap manual for more details
- [FEATURE] Improved support for special characters in location names
- [BUGFIX] Fixed a bug which could cause incorrect or missing drawings of lines and markers
- [BUGFIX] Minor bugfixes and improvements 

Release notes [20110328]:
- [FEATURE] Added more lines to debug logging
- [BUGFIX] Fixed an issue, which could cause SURFmap to get into an infinite loop when a place could not be geocoded and GeoPlugin was used as the geolocation service

Release notes [20110322]:
- [FEATURE] Refactored the client-side application core, resulting in significantly improved performance
- [FEATURE] It is now possible to sort by columns in information windows (of markers and lines)
- [FEATURE] Updated jQuery to v1.5.1
- [FEATURE] Updated jQuery UI to v1.8.11
- [BUGFIX] Fixed a bug in which the legend did not automatically adapt to the default SURFmap zoom level
- [BUGFIX] Fixed an issue in the ConfigurationChecker module, which caused an error when multiple source files had incorrect permissions set
- [BUGFIX] Fixed an issue, which caused the settings panels (to the right of the Google Maps map) not to open and close correctly
- [BUGFIX] Fixed an incorrect multicast IP address pre-filter, related to the SHOW_MULTICAST_TRAFFIC setting

Release notes [20110314]:
- [FEATURE] If the APP_MODE parameter is set to -1 and no 'mode' HTTP GET parameter is provided, APP_MODE is set to 1 automatically
- [BUGFIX] Fixed several PHP warning messages when using PHP 5.3.x
- [BUGFIX] Fixed an issue which caused the zoom level table not to show the current zoom level anymore
- [BUGFIX] Fixed an issue which made it impossible to switch from NfSen's 'List Flows' option to NfSen's 'StatTopN' option
- [BUGFIX] Made several stability improvements for the ConfigurationChecker module
- [BUGFIX] Minor bugfixes and improvements

Release notes [20110306]:
- [FEATURE] Added an option to change the default Google Maps map's center position
- [FEATURE] Added an option to change the initial SURFmap zoom level
- [FEATURE] Added the green marker to the distribution (instead of relying on an external source)
- [FEATURE] Added more checks to the ConfigurationChecker module
- [FEATURE] Instead of writing to a special log file (SURFmap.log), log entries are now written to the Apache log file
- [FEATURE] Updated jQuery UI to v1.8.10
- [FEATURE] Improved layout of NetFlow details table
- [FEATURE] Improved AJAX handling
- [BUGFIX] Fixed a problem with SURFmap's advanced mode when using MySQL as the NetFlow data information source
- [BUGFIX] Removed the 'REFRESH_INTERVAL' setting until a better solution has been found
- [BUGFIX] Minor bugfixes and improvements

Release notes [20110204]:
- [FEATURE] Added support for the definition of multiple internal domains
- [FEATURE] Added support for multiple NetFlow collectors
- [FEATURE] Updated IP2Location library to v4.11
- [BUGFIX] Fixed two issues which caused Apache Web server to log an error about an unused variable
- [BUGFIX] Fixed a small cosmetic issue in the ConfigurationChecker module

Release notes [20110113]:
- [FEATURE] Updated nfsenutil.php to v1.3.5
- [FEATURE] Updated jQuery to v1.4.4
- [FEATURE] Updated jQuery UI to v1.8.7
- [BUGFIX] Fixed a bug which replicated the static private address filter

Release notes [20101127]:
- [FEATURE] More steps involved in progress bar indication
- [FEATURE] Improved logic of some ConfigurationChecker modules
- [FEATURE] Improved versioning support
- [FEATURE] Enhanced debug logging
- [FEATURE] Added support for IP2Location databases without latitude/longitude data
- [BUGFIX] The private IP address domain (192.168/16) is now added as a static filter for nfdump queries, since they will never be resolvable
- [BUGFIX] Various minor bug fixes

Release notes [20101121]:
- [FEATURE] nfcapd file storage structure definition in SURFmap is more flexible now
- [FEATURE] Updated jQuery UI to v1.8.6
- [FEATURE] Added an additional check to the ConfigurationChecker module
- [FEATURE] NfSen profiles and source selectors are configurable on SURFmap's configuration file now
- [BUGFIX] Removed a hardcoded reference to a machine of the University of Twente
- [BUGFIX] Corrected a faulty dummy nfdump filter
- [BUGFIX] Fixed an issue in which failed geocodings caused incorrect logging in SURFmap's log file
- [BUGFIX] Various minor bug fixes