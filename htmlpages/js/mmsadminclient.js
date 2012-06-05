var cfOpts = {
	reflectionColor: "#FFFFFF",
	circularFlow: false
}
var cfAccepted = new ContentFlow('contentFlowAccepted', cfOpts);
var cfQueued = new ContentFlow('contentFlowQueued', cfOpts);
var cfDeclined = new ContentFlow('contentFlowDeclined', cfOpts);
var Server;

// Objects used as play-pretend maps
var localAccepted = {};
var localDeclined = {};
var localQueued = {};

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

function toggle_visibility(id) {
	if (id == 'queued') {
		$('#queued').show();
		$('#accepted').hide();
		$('#declined').hide();
	}
	else if (id == 'accepted') {
		$('#queued').hide();
		$('#accepted').show();
		$('#declined').hide();
	}
	else if (id == 'declined') {
		$('#queued').hide();
		$('#accepted').hide();
		$('#declined').show();
	}
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
	var localMap;
	var spanObj;
	var cfObj;
	if (state == 'queued') {
		localMap = localQueued; // Use Queued map
		spanObj = $('span.queuedamount');
		cfObj = cfQueued; // ContentFlow
	}
	else if (state == 'accepted') {
		localMap = localAccepted; // Use Accepted map
		spanObj = $('span.acceptedamount');
		cfObj = cfAccepted; // ContentFlow
	}
	else if (state == 'declined') {
		localMap = localDeclined; // Use Declined map
		spanObj = $('span.declinedamount');
		cfObj = cfDeclined; // ContentFlow
	}
	else {
		return false;
	}
	
	console.log('#### START UPDATE ####');
	console.log('state: '+state);
	
	console.log('cfObj:');
	console.log(cfObj);

	// Update value in parenthesis on tab links
	spanObj.text(serverList.length);
	
	var serverMap = {};
	// Convert serverList to object ("map") with msgid as keys
	for (var idx in serverList) {
		serverMap[serverList[idx].msgid] = serverList[idx];
	}
	console.log('serverMap:');
	console.log(serverMap);
	
	// Compare serverMap and localMap
	// Remove objects from localMap which are not in serverMap
	for (var idx in localMap) {
		// ?: serverMap with same key as localMap 
		if (typeof serverMap[localMap[idx].msgid] == 'undefined') {
			// Do removal
			delete_dom_mms_item_by_id( localMap[idx].msgid, cfObj );
			delete localMap[idx];
		}
	}
	// Add objects which are in serverMap (but not in localMap) to localMap
	for (var idx in serverMap) {
		// ?: localMap with same key as serverMap is not set
		if (typeof localMap[serverMap[idx].msgid] == 'undefined') {
			// -> Do write item
			add_dom_mms_item( serverMap[idx], cfObj );
			localMap[serverMap[idx].msgid] = serverMap[idx];
		}
	}
	
	console.log('localMap:');
	console.log(localMap);
	
	console.log('#### END UPDATE ####');
	return true;
}

// Add MMS content to DOM
function add_dom_mms_item( mmsItem, cfObj ) {
	var cfItem = $('<div class="item">'+
			'<img class="content" src="'+mmsItem.imgpath+'" id="msgid'+mmsItem.msgid+'" target="_blank"/>'+
			'<div class="caption">'+
			mmsItem.text+'<br/>'+
			'Phonenumber: '+mmsItem.phonenumber+'<br/>'+
			'Received: '+mmsItem.recvdate+
			'</div></div>');
	console.log('-> Adding id: '+mmsItem.msgid+' to:');
	console.log(cfObj);
	return cfObj.addItem(cfItem.get(0), 'end');
}

// Delete MMS content from DOM
function delete_dom_mms_item_by_id( msgId, cfObj ) {
	var foundItem = false;
	var mmsItemId;
	// Loop through all contentflow items looking for one with the ID we are looking for
	// Reason for this is because getItem returns the index of a picture, not the id we are after
	for (var idx = 0; idx < cfObj.getNumberOfItems(); idx++) {
		mmsItemId = get_msgid_by_index( idx, cfObj );
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

function get_msgid_by_index( idx, cfObj ) {
	if (typeof cfObj.getItem(idx).canvas.id != 'undefined')	{
		// Slight regex to grab the id number at the end of the string id
		return parseInt(cfObj.getItem(idx).canvas.id.match(/(\d+)$/)[0], 10);
	}
	return -1;
}

function set_nick_and_connect( nick ) {
	var userlist = new Object;
	
	toggle_visibility('queued'); // Start off showing the ones in queue
	
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
	
	Server.bind('updatemmslist', function( data ) {
		log('Received updated MMS list');
//		console.log(data);
		update_mmsitems( 'queued', data.queued );
		update_mmsitems( 'declined', data.declined );
		update_mmsitems( 'accepted', data.accepted );
	});
	
	// Accept picture
//	Server.bind('picture_accept', function( payload ) {
//		log( payload );
//		console.log(payload);
//		log("Accepted picture...");
//	});

	// Decline picture
//	Server.bind('picture_decline', function( payload ) {
//		log( payload );
//		console.log(payload);
//		log("Declined picture...");
//	});


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

$(document).ready(function() {
	// Show only nick input at first
	$('div#splashscreen').show();
	$('div#main').hide();
	
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
		var msgid = get_msgid_by_index(cfQueued.getActiveItem().getIndex(), cfQueued);
		send( 'setaccepted', {msgid: msgid} );
	});

	$('#picture_decline').click(function(e) {
		var msgid = get_msgid_by_index(cfQueued.getActiveItem().getIndex(), cfQueued);
		send( 'setdeclined', {msgid: msgid} );
	});
});	
