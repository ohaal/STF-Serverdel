// Requires mmsclient.js
// Global from mmsclient.js: Server 
var visibleTab;
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

// Updating userlist is done very simple by just removing everything in current list and rewriting it
function update_userlist( userlist ) {
	$('select#userlist').html('');
	for (user in userlist) {
		$('select#userlist').append('<option value="'+userlist[user]+'">'+userlist[user]+'</option>');
	}
}

function update_amount(state, amount) {
	// Update any values showing amount of each type
	var spanNode = $('span.'+state+'amount');
	spanNode.text(amount);
}

function set_nick_and_connect( nick ) {
	log('Connecting...');
	prepare_connection();

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
		update_mmsitems( 'queued', data.queued, true );
		update_amount('queued', data.queued.length );
		update_mmsitems( 'declined', data.declined, true );
		update_amount('declined', data.declined.length );
		update_mmsitems( 'accepted', data.accepted, true );
		update_amount('accepted', data.accepted.length );
	});

	// Refresh/Redraw ContentFlows (after initial images are loaded)
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
		cfQueued.resize();
	}
	else if (state == 'accepted') {
		$('#contentFlowDeclined').hide();
		$('#contentFlowQueued').hide();
		$('#contentFlowAccepted').show().focus();
		cfAccepted.resize();
	}
	else if (state == 'declined') {
		$('#contentFlowAccepted').hide();
		$('#contentFlowQueued').hide();
		$('#contentFlowDeclined').show().focus();
		cfDeclined.resize();
	}
	else {
		return false;
	}
	visibleTab = state;
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
		var cfObj;
		if (visibleTab == 'queued') cfObj = cfQueued;
		else if (visibleTab == 'declined') cfObj = cfDeclined;
		else return false;
		
		// Accept currently shown picture only if there is a shown picture
		if (typeof cfObj.getActiveItem() != 'undefined') {
			var msgId = get_id_by_index(cfObj.getActiveItem().getIndex(), cfObj);
			send( 'setaccepted', {msgid: msgId} );
		}
		else return false;
	});

	$('#picture_decline').click(function(e) {
		var cfObj;
		if (visibleTab == 'queued') cfObj = cfQueued;
		else if (visibleTab == 'accepted') cfObj = cfAccepted;
		else return false;
		
		// Decline currently shown picture only if there is a shown picture
		if (typeof cfObj.getActiveItem() != 'undefined') {
			var msgId = get_id_by_index(cfObj.getActiveItem().getIndex(), cfObj);
			send( 'setdeclined', {msgid: msgId} );
		}
		else return false;
	});
});	
