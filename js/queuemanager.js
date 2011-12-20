/*******************************
 * queuemanager.js [SURFmap]
 * Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 * University of Twente, The Netherlands
 *
 * LICENSE TERMS: BSD-license.html
 *******************************/

	function QueueManager() {
		// Queue priorities
		this.queueTypes = {
			DNS: 0,
			SESSION: 1,
			INFO: 2,
			ERROR: 3,
			DEBUG: 4,
			GEOCODING: 5,
			STAT: 6
		}
		
		this.DNS_queue = [];
		this.SESSION_queue = [];
		this.INFO_logQueue = [];
		this.ERROR_logQueue = [];
		this.DEBUG_logQueue = [];
		this.GEOCODING_queue = [];
		this.STAT_queue = [];
		
		this.addElement = addElementPriv;
		this.getElement = getElementPriv;
		this.getElementPrio = getElementPrioPriv;
	}
	
	/*
	 * Adds the specified element to the queue of the specified type.
	 * Parameters:
	 *		type - queue type
	 *		element - element to be added to the queue
	 * Return:
	 *		Queue size after adding the specified element. Note that 0 will therefore
	 *		be returned if element pushing to the queue was not successful.
	 */
	function addElementPriv(type, element) {
		if (type == this.queueTypes.DNS) {
			this.DNS_queue.push(element);
			return this.DNS_queue.length;
		} else if (type == this.queueTypes.SESSION) {
			this.SESSION_queue.push(element);
			return this.SESSION_queue.length;
		} else if (type == this.queueTypes.INFO) {
			this.INFO_logQueue.push(element);
			return this.INFO_logQueue.length;
		} else if (type == this.queueTypes.ERROR) {
			this.ERROR_logQueue.push(element);
			return this.ERROR_logQueue.length;
		} else if (type == this.queueTypes.DEBUG) {
			this.DEBUG_logQueue.push(element);
			return this.DEBUG_logQueue.length;
		} else if (type == this.queueTypes.GEOCODING) {
			this.GEOCODING_queue.push(element);
			return this.GEOCODING_queue.length;
		} else if (type == this.queueTypes.STAT) {
			this.STAT_queue.push(element);
			return this.STAT_queue.length;
		} else {
			return 0;
		}
	}	
	
	/*
	 * Returns the first element in the queue of the specified type.
	 * Parameters:
	 *		type - queue type
	 * Return:
	 *		First element of the specified queue, or null if it does not exist
	 */
	function getElementPriv(type) {
		if (type == this.queueTypes.DNS && this.DNS_queue.length > 0) {
			return this.DNS_queue.shift();
		} else if (type == this.queueTypes.SESSION && this.SESSION_queue.length > 0) {
			return this.SESSION_queue.shift();
		} else if (type == this.queueTypes.INFO && this.INFO_logQueue.length > 0) {
			return this.INFO_logQueue.shift();
		} else if (type == this.queueTypes.ERROR && this.ERROR_logQueue.length > 0) {
			return this.ERROR_logQueue.shift();
		} else if (type == this.queueTypes.DEBUG && this.DEBUG_logQueue.length > 0) {
			return this.DEBUG_logQueue.shift();
		} else if (type == this.queueTypes.GEOCODING && this.GEOCODING_queue.length > 0) {
			return this.GEOCODING_queue.shift();
		} else if (type == this.queueTypes.STAT && this.STAT_queue.length > 0) {
			return this.STAT_queue.shift();
		} else {
			return null;
		}
	}
	
	/*
	 * Returns the first element in the queue with the highest current priority.
	 * Return:
	 *		First element of the queue with the highest priority, or null if it 
	 *		does not exist
	 */
	function getElementPrioPriv() {
		var element;
		$.each(this.queueTypes, function(key, value) {
		    if (key == this.queueTypes.DNS && this.DNS_queue.length > 0) {
				element = this.DNS_queue.shift();
				return false;
			} 
			if (key == this.queueTypes.SESSION && this.SESSION_queue.length > 0) {
				element = this.SESSION_queue.shift();
				return false;
			} 
			if (key == this.queueTypes.INFO && this.INFO_logQueue.length > 0) {
				element = this.INFO_logQueue.shift();
				return false;
			} 
			if (key == this.queueTypes.ERROR && this.ERROR_logQueue.length > 0) {
				element = this.ERROR_logQueue.shift();
				return false;
			} 
			if (key == this.queueTypes.DEBUG && this.DEBUG_logQueue.length > 0) {
				element = this.DEBUG_logQueue.shift();
				return false;
			} 
			if (key == this.queueTypes.GEOCODING && this.GEOCODING_queue.length > 0) {
				element = this.GEOCODING_queue.shift();
				return false;
			} 
			if (key == this.queueTypes.STAT && this.STAT_queue.length > 0) {
				element = this.STAT_queue.shift();
				return false;
			}
		});
		
		return (element == undefined) ? null : element;
	}	
