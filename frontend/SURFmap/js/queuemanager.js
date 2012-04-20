/*******************************
 # queuemanager.js [SURFmap]
 # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: BSD-license.html
 *******************************/

	function QueueManager() {
		// Queue priorities
		this.queueTypes = {
			DNS: "DNS",
			SESSION: "SESSION",
			INFO: "INFO",
			ERROR: "ERROR",
			DEBUG: "DEBUG",
			GEOCODING: "GEOCODING",
			STAT: "STAT"
		}
	
		this.DNS_queue = [];
		this.SESSION_queue = [];
		this.INFO_logQueue = [];
		this.ERROR_logQueue = [];
		this.DEBUG_logQueue = [];
		this.GEOCODING_queue = [];
		this.STAT_queue = [];
	
		/*
		 * Adds the specified element to the queue of the specified type.
		 * Parameters:
		 *		type - queue type
		 *		element - element to be added to the queue
		 * Return:
		 *		Queue size after adding the specified element. Note that 0 will therefore
		 *		be returned if element pushing to the queue was not successful.
		 */		
		this.addElement = function (type, element) {
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
		this.getElement = function (type) {
			var element;
			
			if (type == this.queueTypes.DNS && this.DNS_queue.length > 0) {
				element = this.DNS_queue.shift();
			} else if (type == this.queueTypes.SESSION && this.SESSION_queue.length > 0) {
				element = this.SESSION_queue.shift();
			} else if (type == this.queueTypes.INFO && this.INFO_logQueue.length > 0) {
				element = this.INFO_logQueue.shift();
			} else if (type == this.queueTypes.ERROR && this.ERROR_logQueue.length > 0) {
				element = this.ERROR_logQueue.shift();
			} else if (type == this.queueTypes.DEBUG && this.DEBUG_logQueue.length > 0) {
				element = this.DEBUG_logQueue.shift();
			} else if (type == this.queueTypes.GEOCODING && this.GEOCODING_queue.length > 0) {
				element = this.GEOCODING_queue.shift();
			} else if (type == this.queueTypes.STAT && this.STAT_queue.length > 0) {
				element = this.STAT_queue.shift();
			}

			return (element == undefined) ? {type: null, element: null} : {type: type, element: element};
		}
	
		/*
		 * Returns the first element in the queue with the highest current priority.
		 * Return:
		 *		First element of the queue with the highest priority, or null if it 
		 *		does not exist
		 */		
		this.getElementPrio = function () {
			var element, type;
			var me = this;
		
			$.each(me.queueTypes, function (key, value) {
			    if (key == me.queueTypes.DNS && me.DNS_queue.length > 0) {
					type = key;
					element = me.DNS_queue.shift();
					return false;
				}	
				if (key == me.queueTypes.SESSION && me.SESSION_queue.length > 0) {
					type = key;
					element = me.SESSION_queue.shift();
					return false;
				} 
				if (key == me.queueTypes.INFO && me.INFO_logQueue.length > 0) {
					type = key;
					element = me.INFO_logQueue.shift();
					return false;
				} 
				if (key == me.queueTypes.ERROR && me.ERROR_logQueue.length > 0) {
					type = key;
					element = me.ERROR_logQueue.shift();				
					return false;
				} 
				if (key == me.queueTypes.DEBUG && me.DEBUG_logQueue.length > 0) {
					type = key;
					element = me.DEBUG_logQueue.shift();
					return false;
				} 
				if (key == me.queueTypes.GEOCODING && me.GEOCODING_queue.length > 0) {
					type = key;
					element = me.GEOCODING_queue.shift();
					return false;
				} 
				if (key == me.queueTypes.STAT && me.STAT_queue.length > 0) {
					type = key;
					element = me.STAT_queue.shift();
					return false;
				}
			});

			return (element == undefined) ? {type: null, element: null} : {type: type, element: element};
		}
		
		/*
		 * Returns the size of the specified queue.
		 * Parameters:
		 *		type - queue type
		 * Return:
		 *		Queue size as integer
		 */		
		this.getQueueSize = function (type) {
			var size = -1;
			
			if (type == this.queueTypes.DNS) {
				size = this.DNS_queue.length;
			} else if (type == this.queueTypes.SESSION) {
				size = this.SESSION_queue.length;
			} else if (type == this.queueTypes.INFO) {
				size = this.INFO_logQueue.length;
			} else if (type == this.queueTypes.ERROR) {
				size = this.ERROR_logQueue.length;
			} else if (type == this.queueTypes.DEBUG) {
				size = this.DEBUG_logQueue.length;
			} else if (type == this.queueTypes.GEOCODING) {
				size = this.GEOCODING_queue.length;
			} else if (type == this.queueTypes.STAT) {
				size = this.STAT_queue.length;
			}
			
			return size;
		}
	
		/*
		 * Returns the combined size of all queues.
		 * Return:
		 *		Total queue size as integer
		 */		
		this.getTotalQueueSize = function () {
			var totalSize = this.DNS_queue.length + this.SESSION_queue.length 
					+ this.INFO_logQueue.length	+ this.ERROR_logQueue.length 
					+ this.DEBUG_logQueue.length + this.GEOCODING_queue.length
					+ this.STAT_queue.length;
			return totalSize;
		}

	}
