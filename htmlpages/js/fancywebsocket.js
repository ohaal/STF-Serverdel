var FancyWebSocket = function(url)
{
	var callbacks = {};
	var ws_url = url;
	var conn;

	this.bind = function(event_name, callback){
		callbacks[event_name] = callbacks[event_name] || [];
		callbacks[event_name].push(callback);
		return this;// chainable
	};

	// Send to server
	this.send = function(event_name, event_data){
		this.conn.send( '{"type":' + JSON.stringify(event_name) + ', "data":' + JSON.stringify(event_data) + '}' );
		return this;
	};

	this.connect = function() {
		if ( typeof(MozWebSocket) == 'function' )
			this.conn = new MozWebSocket(url);
		else
			this.conn = new WebSocket(url);

		// dispatch to the right handlers
		this.conn.onmessage = function(evt){
			var data = evt.data.split(' ');
			// Split limit 2 does not seem to work in JS, so we do a slight hack
			// data.shift() is basically data[0], and data.join(' ') is data[1:end]
			// First parameter is event(/command) type and second is data related to it
			dispatch(data.shift(), data.join(' '));
		};
		this.conn.onclose = function(){dispatch('close',null)}
		this.conn.onopen = function(){dispatch('open',null)}
	};

	this.disconnect = function() {
		this.conn.close();
	};

	// Receive from server and pass to events (callback functions)
	var dispatch = function(event_name, message){
		var chain = callbacks[event_name];
		var obj = JSON && JSON.parse(message) || $.parseJSON(message);
		if(typeof chain == 'undefined') return; // no callbacks for this event
		for(var i = 0; i < chain.length; i++){
			chain[i]( obj );
		}
	}
};