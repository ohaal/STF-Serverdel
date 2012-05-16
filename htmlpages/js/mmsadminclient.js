var cfAccepted = new ContentFlow('contentFlowAccepted', {reflectionColor: "#008000"});
var cfUndetermined = new ContentFlow('contentFlowUndetermined', {reflectionColor: "#FFFFFF"});
var cfDeclined = new ContentFlow('contentFlowDeclined', {reflectionColor: "#FF0000"});
var Server;

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

	var eUndetermined = document.getElementById('undetermined');
	var eAccepted = document.getElementById('accepted');
	var eDeclined = document.getElementById('declined');

	if(id=='undetermined') {
		eUndetermined.style.display = 'block';
		eAccepted.style.display = 'none';
		eDeclined.style.display = 'none';
	}
	if(id=='accepted'){
		eUndetermined.style.display = 'none';
		eAccepted.style.display = 'block';
		eDeclined.style.display = 'none';
	}
	if(id=='declined'){
		eUndetermined.style.display = 'none';
		eAccepted.style.display = 'none';
		eDeclined.style.display = 'block';
	}

 }


// Send data to server, specific types to trigger specific events
function send( type, data ) {
	Server.send( type, data );
}

function update_userlist( userlist ) {
	$('select#userlist').html('');
	for (user in userlist) {
		$('select#userlist').append('<option value="'+userlist[user]+'">'+userlist[user]+'</option>');
	}
}

function set_nick_and_connect( nick ) {
	var userlist = new Object;
	
	toggle_visibility('undetermined');	
	
	log('Connecting...');
	Server = new FancyWebSocket('ws://192.168.40.190:1337');

	/////////////////////////////////////////////////
	// EVENTS - Catch commands received from server
	/////////////////////////////////////////////////
	
	// Let the user know we're connected and tell the server our nickname
	Server.bind('open', function() {
		log( 'Connected.' );
		send('setnick', new Array( nick ));
	});

	// Disconnection occurred.
	Server.bind('close', function( data ) {
		log( 'Disconnected.' );
	});

	// Put any messages sent from server in the eventlog
	Server.bind('chatmsg', function( data ) {
		// Ouput: <nickname> message
		log( '&#060;'+data.nickname+'&#062; '+data.message );
	});
	
	Server.bind('servmsg', function( data ) {
		log( data.message );
	});
	
	Server.bind('setnick', function( data ) {
		log( data.newnickname+' connected.' );
	});
	
	Server.bind('updateuserlist', function( data ) {
		update_userlist( data.userlist );
	});
	
	// Add MMS to list of unhandled MMS
//	Server.bind('addmms', function( payload ) {
//		log( 'Added MMS' );
//		console.log(payload);
//		// Add MMS to list of unhandled MMS
//		var randid = Math.floor(Math.random()*1500);
//		$('div.hiddenItems').html('<div class="item" id="jimmy'+randid+'"><img class="content" src="mmspics/pic0.png" target="_blank"/><div class="caption">pic0: some stripes</div></div>');
//		var newitem = document.getElementById('jimmy'+randid);
//		cfUndetermined.addItem(newitem, 'end');
//		
//	});

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

$(document).ready(function() {
	// Show only nick input at first
	$('div#splashscreen').show();
	$('div#main').hide();
	
	$("button#setnickbutton").click(function() {
		if ($('input#inputnick').val().length > 0) {
			$('div#splashscreen').hide();
			$('div#main').show();
			set_nick_and_connect($('input#inputnick').val());
		}
		return false;
	});
	
	$('#message').keypress(function(e) {
		if ( e.keyCode == 13 && this.value ) {
			send( 'chatmsg', new Array( this.value ) );

			$(this).val('');
		}
	});
	

//	$('#picture_accept').click(function(e) {
//			log( 'Picture X is requested to be accepted: ' + this.value );
//			alert(cfUndetermined.getActiveItem().id);
//			send( 'picture_accept', this.value );
//
//			$(this).val('');
//		
//	});

//	$('#picture_decline').click(function(e) {
//		log( 'Picture X is requested to be declined: ' + this.value );
//		send( 'picture_decline', this.value );
//
//		$(this).val('');
//	
//	});

//	$('#addmms').keypress(function(e) {
//		if ( e.keyCode == 13 && this.value ) {
//			log( 'MMSYou: ' + this.value );
//			send( 'addmms', this.value );
//
//			$(this).val('');
//		}
//	});
});