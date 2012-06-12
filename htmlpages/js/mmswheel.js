// Requires mmsclient.js
// Global from mmsclient.js: Server
var cfAccepted = new ContentFlow('contentFlowAccepted', {
	circularFlow: true,
	loadingTimeout: 60000,
	onMakeActive: function(obj) {
		var self = this;
		// This is where we decide how long we want to wait between changing picture
		setTimeout(function() {
			self.moveTo('next');
		}, 5000);
	}
});

// Object used as play-pretend map (storing all locally known MMS items)
var localAccepted = {};

function initialize_and_connect() {
	console.log('Connecting...');
	prepare_connection();

	/////////////////////////////////////////////////
	// EVENTS - Catch commands received from server
	/////////////////////////////////////////////////
	
	Server.bind('open', function() {
		console.log( 'Connected.' );
	});
	Server.bind('close', function( data ) {
		console.log( 'Disconnected.' );
	});
	// Sync local MMS list with server MMS list
	Server.bind('updatemmslist', function( data ) {
		update_mmsitems( 'accepted', data.accepted, false );
		console.log( 'Received updated MMS list' );
	});

	// Refresh/Redraw ContentFlows (after initial images are loaded)
	Server.connect();
}

$(document).ready(function() {
	initialize_and_connect();
});	
