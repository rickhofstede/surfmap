/*******************************
 * maputil.js [SURFmap]
 * Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 * University of Twente, The Netherlands
 *
 * LICENSE TERMS: BSD-license.html
 *******************************/

 	/**
	* Fires a 'click' event on a randomly selected line at the specified zoom level.
	* Parameters:
	*	level - the SURFmap zoom level of which the lines should be added to the map; can be 0, 1, 2 or 3
	*/		
	function clickRandomLine(level) {
		var randomNumber = Math.floor(Math.random() * lineProperties[level].length);
		
		var recordFound = -1;
		for (var i = 0; i < lineProperties[level][randomNumber].lineRecords.length; i++) {
			if (lineProperties[level][randomNumber].lineRecords[i].srcName == "NETHERLANDS") {
				recordFound = 0;
				break;
			} else if (lineProperties[level][randomNumber].lineRecords[i].dstName == "NETHERLANDS") {
				recordFound = 1;
				break;
			}
		}
		
		// If the source of the flow/line is "The Netherlands" (UT), the destination end point should be clicked
		if (recordFound == 0) {
			google.maps.event.trigger(lines[level][randomNumber], "click", new google.maps.LatLng(lineProperties[level][randomNumber].lat2, lineProperties[level][randomNumber].lng2));	
		} else {
			google.maps.event.trigger(lines[level][randomNumber], "click", new google.maps.LatLng(lineProperties[level][randomNumber].lat1, lineProperties[level][randomNumber].lng1));	
		}
	}

   /**
	* Returns the SURFmap zoom level of the specified Google Maps zoom level.
	* Parameters:
	*	gmZoomLevel - the Google Maps zoom level that has to be converted to a SURFmap zoom level
	*/
	function getSurfmapZoomLevel(gmZoomLevel) {
		if (gmZoomLevel <= 4) return 0;								// Country: 2-4
		else if (gmZoomLevel >= 5 && gmZoomLevel <= 7) return 1;		// Region: 5-7
		else if (gmZoomLevel >= 8 && gmZoomLevel <= 10) return 2;	// City: 8-10
		else return 3;													// Host: 11-13
	}

   /**
	* Returns the Google Maps zoom level of the specified SURFmap zoom level.
	* Parameters:
	*	smZoomLevel - the SURFmap zoom level that has to be converted to a Google Maps zoom level
	*/			
	function getGoogleMapsZoomLevel(smZoomLevel) {
		if (smZoomLevel == 0) return 2;
		else if (smZoomLevel == 1) return 5;
		else if (smZoomLevel == 2) return 8;
		else return 11;
	}

   /**
	* Checks whether a gray map area is visible.
	*/
	function isGrayMapAreaVisible() {
		if (map.getBounds().getNorthEast().lat() > 85.0 || map.getBounds().getSouthWest().lat() < -85.0) {
			return true;
		} else {
			return false;
		}
	}

   /**
	* Hides a gray map area by changing the map's center and return the map center after algorithm
	* completion (i.e. map center without visible gray areas).
	*/
	function hideGrayMapAreas() {
		if (map.getBounds().getNorthEast().lat() > 85.0) {
			while(map.getBounds().getNorthEast().lat() > 85.0) {
				map.setCenter(new google.maps.LatLng(map.getCenter().lat() - 0.5, map.getCenter().lng()));
			}
		} else if (map.getBounds().getSouthWest().lat() < -85.0) {
			while(map.getBounds().getNorthEast().lat() > 85.0) {
				map.setCenter(new google.maps.LatLng(map.getCenter().lat() + 0.5, map.getCenter().lng()));
			}
		} else {
		}
		
		return map.getCenter();
	}

   /**
    * Either 1) creates a new map in case it didn't exist before,
	* or 2) returns the map object.
    * Parameters:
	*		mapCenter - center of the map (google.maps.LatLng)
	*		initialZoomLevel - (Google Maps) zoom level for initialization
	*		minZoomLevel - minimum (Google Maps) zoom level for initialization
	*		maxZoomLevel - maximum (Google Maps) zoom level for initialization
    */			
	function initializeMap(mapCenter, initialZoomLevel, minZoomLevel, maxZoomLevel) {
		var mapOptions = {
			zoom: initialZoomLevel,
			minZoom: minZoomLevel,
			maxZoom: maxZoomLevel,
			center: mapCenter,
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			mapTypeControl: false
		}
		return new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
	}
	
   /**
	* This function adds arrays with GMarkers to the GMarkerManager, which puts
	* the markers on the map.
	*/			
	function addMarkersToMarkerManager() {
		markerManager.addMarkers(markers[0], 2, 4);
		markerManager.addMarkers(markers[1], 5, 7);
		markerManager.addMarkers(markers[2], 8, 10);
		markerManager.addMarkers(markers[3], 11, 13);
		markerManager.refresh();
	}	
	
   /**
	* Checks whether a particular line already exists at the given coordinates.
	* Parameters:
	*	level - a SURFmap zoom level
	*	srcLat - latitude value of one end point that has to be checked
	*	srcLng - longitude value of one end point that has to be checked
	*	dstLat - latitude value of other end point that has to be checked
	*	dstLng - longitude value of other end point that has to be checked
	*/			
	function lineExists(level, lat1, lng1, lat2, lng2) {
		var lineIndex = -1;
		for (var i = 0; i < lineProperties[level].length; i++) {
			if ((lineProperties[level][i].lat1 == lat1 && lineProperties[level][i].lng1 == lng1 && lineProperties[level][i].lat2 == lat2 && lineProperties[level][i].lng2 == lng2) || (lineProperties[level][i].lat1 == lat2 && lineProperties[level][i].lng1 == lng2 && lineProperties[level][i].lat2 == lat1 && lineProperties[level][i].lng2 == lng1)) {
				lineIndex = i;
			}
		}
		return lineIndex;
	}	
	
   /**
	* This function checks whether or not a particular marker already exists
	* at the given coordinates.
	* Parameters:
	*	level - a SURFmap zoom level
	*	lat - latitude coordinate of the marker that has to be checked
	*	lng - longitude coordinate of the marker that has to be checked
	*/			
	function markerExists(level, lat, lng) {
		var markerIndex = -1;
		
		for (var i = 0; i < markerProperties[level].length; i++) {
			if (markerProperties[level][i].lat == lat && markerProperties[level][i].lng == lng) {
				markerIndex = i;
				break;
			}
		}
		return markerIndex;
	}	
	
   /**
	* This function adds lines as an overlay to the map.
	* Parameters:
	*	level - the SURFmap zoom level of which the lines should be added to the map; can be 0, 1, 2 or 3
	*/			
	function refreshLineOverlays(level) {
		while(lineOverlays.length > 0) {
			lineOverlays[0].setMap(null);
			lineOverlays.splice(0, 1);
		}

		for (var i = 0; i < lines[level].length; i++) {
			lines[level][i].setMap(map);
			lineOverlays.push(lines[level][i]);
		}

		if (demoMode == 1) clickRandomLine(level);
	}	
	
   /**
	* This function zooms the SURFmap map to a defined zoom level.
	* Parameters:
	* 	mode - can be either 0 (quick; zoom depending on SURFmap zoom levels) or 1 (normal; zoom depending on Google Maps zoom levels).
	* 	direction - can be either 0 (in) or 1 (out)
	* 	level - the destination zoom level (only in case of 'normal' mode zoom, otherwise 'null'.)
	*/			
	function zoom(mode, direction, level) {
		if (mode === 0) {
			var current_zoom_level = getSurfmapZoomLevel(map.getZoom());
			if (direction === 0 && current_zoom_level < 3) {
				map.setZoom(5 + (current_zoom_level * 3));
			} else if (direction == 1 && current_zoom_level > 0) {
				map.setZoom(2 + ((current_zoom_level - 1) * 3));
			}
		} else {
			if (direction === 0) {
				if (level === null) {
					map.setZoom(map.getZoom() + 1);
				} else {
					map.setZoom(level);
				}
			} else {
				if (level === null) {
					map.setZoom(map.getZoom() - 1);
				} else {
					map.setZoom(level);
				}
			}
		}
	}	
