var Server;
var cfQueued = new ContentFlow('contentFlowQueued', {
	circularFlow: false,
	loadingTimeout: 60000,
	reflectionHeight: 0,
	startItem: 'first'
});
var cfAccepted = new ContentFlow('contentFlowAccepted', {
	circularFlow: false,
	loadingTimeout: 60000,
	reflectionHeight: 0,
	startItem: 'last'
});
var cfDeclined = new ContentFlow('contentFlowDeclined', {
	circularFlow: false,
	loadingTimeout: 60000,
	reflectionHeight: 0,
	startItem: 'last'
});

// Object used as play-pretend map (storing all locally known MMS items)
var localQueued = {};
var localAccepted = {};
var localDeclined = {};

function log( text ) {
	var date = new Date();
	var hours = date.getHours() < 10 ? '0'+date.getHours() : date.getHours();
	var minutes = date.getMinutes() < 10 ? '0'+date.getMinutes() : date.getMinutes();
	var seconds = date.getSeconds() < 10 ? '0'+date.getSeconds() : date.getSeconds();
	var time = hours+':'+minutes+':'+seconds;
	
	var logelement = $('#log');
	// Add text to log
	logelement.append((logelement.val()?"\n":'')+'['+time+'] '+text);
	// Autoscroll
	logelement[0].scrollTop = logelement[0].scrollHeight - logelement[0].clientHeight;
}

// Send data to server, specific types to trigger specific events
function send( type, data ) {
	Server.send( type, data );
}

// Updating userlist is done very simple by just removing everything in current list and rewriting it
function update_userlist( userlist ) {
	$('select#userlist').html('');
	for (user in userlist) {
		$('select#userlist').append('<option value="'+userlist[user]+'">'+userlist[user]+'</option>');
	}
}

// This function syncs server and client side MMS view (looks for new and (re)moved MMS items)
function update_mmsitems( state, serverList ) {
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
	
	// Update any values showing amount of each type
	var spanNode = $('span.'+state+'amount');
	spanNode.text(serverList.length);
	
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
			add_mms_item_to_cf( serverMap[idx], cfObj );
			localMap[serverMap[idx].msgid] = serverMap[idx];
		}
	}
	
	return true;
}

// Add MMS items to ContentFlow
function add_mms_item_to_cf( mmsItem, cfObj ) {
	var cfItem = $('<div class="item">'+
			'<img class="content" src="'+mmsItem.imgpath+'" id="msgid'+mmsItem.msgid+'" target="_blank"/>'+
			'<div class="caption">'+
			'Message:'+mmsItem.text+'<br/>'+
			'Phonenumber: '+mmsItem.phonenumber+'<br/>'+
			'Received: '+mmsItem.recvdate+'<br/>'+
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
			$('div.globalCaption').html('&nbsp;');
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

function set_nick_and_connect( nick ) {
	var userlist = new Object;
	
	log('Connecting...');
	var host = $('div#hiddenmetainfo span#wshost').text();
	var port = $('div#hiddenmetainfo span#wsport').text();
	Server = new FancyWebSocket('ws://'+host+':'+port);

	/////////////////////////////////////////////////
	// EVENTS - Catch commands received from server
	/////////////////////////////////////////////////
	
	// Let the user know we're connected and tell the server our nickname
	Server.bind('open', function() {
		log( 'Connected.' );
		send('setnick', {newnickname: nick}); // This is where we tell the server our nick, -> after connection
	});

	// Disconnection occurred.
	Server.bind('close', function( data ) {
		log( 'Disconnected.' );
	});

	// Put any chat messages in the eventlog
	Server.bind('chatmsg', function( data ) {
		// Ouput: <nickname> message
		log( '&#060;'+data.nickname+'&#062; '+data.message );
	});
	
	// Put any server messages in the log - with no changes
	Server.bind('servmsg', function( data ) {
		log( data.message );
	});
	
	// Currently, the only way to set your nick is when you connect - message is therefore "nick connected."
	Server.bind('setnick', function( data ) {
		log( data.newnickname+' connected.' );
	});
	
	// Used to sync the userlist with server userlist
	Server.bind('updateuserlist', function( data ) {
		update_userlist( data.userlist );
	});
	
	// Sync local MMS list with server MMS list
	Server.bind('updatemmslist', function( data ) {
		update_mmsitems( 'queued', data.queued );
		update_mmsitems( 'declined', data.declined );
		update_mmsitems( 'accepted', data.accepted );
	});

	// Refresh/Redraw ContentFlows (after initial images are loaded)
	cfQueued.resize();
	cfAccepted.resize();
	cfDeclined.resize();
	toggle_visibility('queued');
	Server.connect();
}

function validate_nickinput() {
	// ?: Nick input is validated OK
	if ($('input#inputnick').val().length > 0) {
		// -> Switch to main screen and connect to server
		$('div#splashscreen').hide();
		$('div#main').show();
		set_nick_and_connect($('input#inputnick').val());
	}
	return false;
}

function toggle_visibility(state) {
	if (state == 'queued') {
		$('#contentFlowAccepted').hide();
		$('#contentFlowDeclined').hide();
		$('#contentFlowQueued').show().focus();		
	}
	else if (state == 'accepted') {
		$('#contentFlowDeclined').hide();
		$('#contentFlowQueued').hide();
		$('#contentFlowAccepted').show().focus();		
	}
	else if (state == 'declined') {
		$('#contentFlowAccepted').hide();
		$('#contentFlowQueued').hide();
		$('#contentFlowDeclined').show().focus();		
	}
}

$(document).ready(function() {
	// Show only nick input at first
	$('div#splashscreen').show();
	$('div#main').hide();
	
	// Hiding and showing correct ContentFlows
	$('a#showqueued').click(function(e) {
		toggle_visibility('queued');
		return false;
	});
	$('a#showaccepted').click(function(e) {
		toggle_visibility('accepted');
		return false;
	});
	$('a#showdeclined').click(function(e) {
		toggle_visibility('declined');
		return false;
	});
	
	$("button#setnickbutton").click(validate_nickinput);
	$('input#inputnick').keypress(function(e) {
		if ( e.keyCode == 13 && this.value ) {
			validate_nickinput();
		}
	});
	
	$('#message').keypress(function(e) {
		if ( e.keyCode == 13 && this.value ) {
			send( 'chatmsg', {chatmsg: this.value} );
			$(this).val(''); // Empty chat field
		}
	});
	
	$('#picture_accept').click(function(e) {
		var msgId = get_id_by_index(cfQueued.getActiveItem().getIndex(), cfQueued);
		send( 'setaccepted', {msgid: msgId} );
	});

	$('#picture_decline').click(function(e) {
		var msgId = get_id_by_index(cfQueued.getActiveItem().getIndex(), cfQueued);
		send( 'setdeclined', {msgid: msgId} );
	});
});	
