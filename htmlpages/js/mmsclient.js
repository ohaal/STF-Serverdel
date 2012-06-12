var Server;

// Send data to server, specific types to trigger specific events
function send( type, data ) {
	Server.send( type, data );
}

// This function syncs server and client side MMS view (looks for new and (re)moved MMS items)
function update_mmsitems( state, serverList, adminView ) {
	var localMap, cfObj;
	if (state == 'queued') {
		localMap = localQueued;
		cfObj = cfQueued;
	}
	else if (state == 'accepted') {
		localMap = localAccepted;
		cfObj = cfAccepted;
	}
	else if (state == 'declined') {
		localMap = localDeclined;
		cfObj = cfDeclined;
	}
	else {
		return false;
	}
	
	var serverMap = {};
	// Convert serverList to object ("map") with msgid as keys for simple comparison
	for (var idx in serverList) {
		serverMap[serverList[idx].msgid] = serverList[idx];
	}
	
	// Compare serverMap and localMap - serverMap is always correct
	// Remove objects from localMap which are not in serverMap
	for (var idx in localMap) {
		// ?: serverMap with same key (msgid) as localMap is not set 
		if (typeof serverMap[localMap[idx].msgid] == 'undefined') {
			// -> Remove item from localMap
			delete_mms_item_by_id_from_cf( localMap[idx].msgid, cfObj );
			delete localMap[idx];
		}
	}
	// Add objects which are in serverMap (but not in localMap) to localMap
	for (var idx in serverMap) {
		// ?: localMap with same key (msgid) as serverMap is not set
		if (typeof localMap[serverMap[idx].msgid] == 'undefined') {
			// -> Add item to localMap
			add_mms_item_to_cf( serverMap[idx], cfObj, adminView );
			localMap[serverMap[idx].msgid] = serverMap[idx];
		}
	}
	
	return true;
}

// Add MMS items to ContentFlow
function add_mms_item_to_cf( mmsItem, cfObj, adminView ) {
	var message, extra;
	if (adminView === true) {
		message = (mmsItem.text === '') ? 'No message! <a class="addmmsmsg" href="#">Add a message</a>' : 'Message: '+mmsItem.text;
		extra = 'Phonenumber: '+mmsItem.phonenumber+'<br/>'+
				'Received: '+mmsItem.recvdate;
	}
	else {
		message = (mmsItem.text === '') ? '' : mmsItem.text;
		extra = '';
	}
	var cfItem = $('<div class="item">'+
			'<img class="content" src="'+mmsItem.imgpath+'" id="msgid'+mmsItem.msgid+'" target="_blank"/>'+
			'<div class="caption">'+
			message+'<br/>'+
			extra+
			'</div></div>');
	var addedItemIndex = cfObj.addItem(cfItem.get(0), 'end');
	return addedItemIndex;
}

// Delete MMS items from ContentFlow
function delete_mms_item_by_id_from_cf( msgId, cfObj ) {
	console.log('delete'+msgId);
	var foundItem = false;
	var mmsItemId;
	// Loop through all contentflow items by index looking for one with the ID we are looking for
	// Reason for this is because getItem returns the index of a picture, not the msgid we are after
	for (var idx = 0; idx < cfObj.getNumberOfItems(); idx++) {
		mmsItemId = get_id_by_index( idx, cfObj );
		if (mmsItemId == msgId) {
			foundItem = true;
			break;
		}
	}
	
	if (foundItem) {
		// Remember previous position
		var prevPos = cfObj.getActiveItem().getIndex();
		
		// Remove the item we found
		cfObj.rmItem(idx);
		
		// Go to previous position if it was not the one removed
		if (prevPos != idx) cfObj.moveTo(cfObj.getItem(prevPos));
		
		// Empty global caption manually if ContentFlow is empty - for some reason this isn't done automatically
		if (cfObj.getNumberOfItems() == 0) {
			$('div.globalCaption').html('');
			// Alternatively hide the content flow when it is empty, and reshow when it is not empty?
		}
	}
	return foundItem;
}

function get_id_by_index( idx, cfObj ) {
	if (typeof cfObj.getItem(idx).content.id != 'undefined')	{
		// Simple regex to grab the id number at the end of the string id - only one match possible (in [0])
		return parseInt(cfObj.getItem(idx).content.id.match(/(\d+)$/)[0], 10);
	}
	return -1;
}

function prepare_connection() {
	var host = $('div#hiddenmetainfo span#wshost').text();
	var port = $('div#hiddenmetainfo span#wsport').text();
	Server = new FancyWebSocket('ws://'+host+':'+port);
}
