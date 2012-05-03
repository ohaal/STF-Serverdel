<!DOCTYPE html>
<html>
  <head>
    <meta charset='UTF-8' />
    <style>
		input, textarea {border:1px solid #CCC;margin:0px;padding:0px}
		#chat {max-width:300px;margin:auto}
		#log {width:100%;height:200px}
		#message {width:100%;line-height:20px}
    </style>
    <link rel="stylesheet" title="Standard" href="contentflow/styles.css" type="text/css" media="screen" />
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
		google.load("jquery", "1.7.1");
		google.load("jqueryui", "1.8.17");
    </script>
    <script type="text/javascript" src="contentflow/contentflow_src.js"></script>
    <script type="text/javascript" src="js/fancywebsocket.js"></script>
    <script>
    	var cfAccepted = new ContentFlow('contentFlowAccepted', {reflectionColor: "#008000"});
    	var cfUndetermined = new ContentFlow('contentFlowUndetermined', {reflectionColor: "#FFFFFF"});
    	var cfDeclined = new ContentFlow('contentFlowDeclined', {reflectionColor: "#FF0000"});
		var Server;

		function log( text ) {
			logelement = $('#log');
			// Add text to log
			logelement.append((logelement.val()?"\n":'')+text);
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
			Server.send( type, type+' '+data );
		}
	
		$(document).ready(function() {

			toggle_visibility('undetermined');
			
			log('Connecting...');
			Server = new FancyWebSocket('ws://10.4.52.76:1337');

			$('#message').keypress(function(e) {
				if ( e.keyCode == 13 && this.value ) {
					log( 'You: ' + this.value );
					send( 'chatmsg', this.value );
	
					$(this).val('');
				}
			});

			$('#addmms').keypress(function(e) {
				if ( e.keyCode == 13 && this.value ) {
					log( 'MMSYou: ' + this.value );
					send( 'addmms', this.value );
	
					$(this).val('');
				}
			});

			// What to do when receiving from server
			// Let the user know we're connected
			Server.bind('open', function() {
				log( "Connected." );
			});

			// Disconnection occurred.
			Server.bind('close', function( data ) {
				log( "Disconnected." );
			});

			// Log any messages sent from server
			Server.bind('chatmsg', function( payload ) {
				log( payload );
				console.log(payload);
			});

			// Add MMS to list of unhandled MMS
			Server.bind('addmms', function( payload ) {
				log( 'Added MMS' );
				console.log(payload);
				// Add MMS to list of unhandled MMS
				var randid = Math.floor(Math.random()*1500);
				$('div.hiddenItems').html('<div class="item" id="jimmy'+randid+'"><img class="content" src="mmspics/pic0.png" target="_blank"/><div class="caption">pic0: some stripes</div></div>');
				var newitem = document.getElementById('jimmy'+randid);
				cfUndetermined.addItem(newitem, 'end');
				
			});

			// Accept picture
			Server.bind('picture_accept', function( payload ) {
				log( payload );
				console.log(payload);
				log("Accepted picture...");
			});

			// Decline picture
			Server.bind('picture_decline', function( payload ) {
				log( payload );
				console.log(payload);
				log("Declined picture...");
			});

			$('#picture_accept').click(function(e) {
					log( 'Picture X is requested to be accepted: ' + this.value );
					alert(cfUndetermined.getActiveItem().id);
					send( 'picture_accept', this.value );
	
					$(this).val('');
				
			});

			$('#picture_decline').click(function(e) {
				log( 'Picture X is requested to be declined: ' + this.value );
				send( 'picture_decline', this.value );

				$(this).val('');
			
		});

			Server.connect();
		});
    </script>
  </head>
  <body>
  	<div id="toggle_menu" class="toogleMenu">
  		<a href="#" onclick="toggle_visibility('undetermined');">Queue</a> |
  		<a href="#" onclick="toggle_visibility('accepted');">Accepted</a> |
  		<a href="#" onclick="toggle_visibility('declined');">Declined</a>
  	</div>

  	<div id="accepted">
	    <div id="contentFlowAccepted" class="ContentFlow">
	        <!-- should be place before flow so that contained images will be loaded first -->
	        <div class="loadIndicator"><div class="indicator"></div></div>
	
	        <div class="flow">
	
	        </div>
	        <div class="globalCaption"></div>
	        <div class="scrollbar">
	            <div class="slider"><div class="position"></div></div>
	        </div>
	        <div class="hiddenItems" style="display:none;">
	        </div>
     	</div>
    </div> 
     
     <div id="undetermined">   
	    <div id="contentFlowUndetermined" class="ContentFlow">
	        <!-- should be place before flow so that contained images will be loaded first -->
	        <div class="loadIndicator"><div class="indicator"></div></div>
	
	        <div class="flow">
	
	        </div>
	        <div class="globalCaption"></div>
	        <div class="scrollbar">
	            <div class="slider"><div class="position"></div></div>
	        </div>
	        <div class="hiddenItems" style="display:none;">
	        </div>
	    </div>
	    
		    <div id="chat">
		      <textarea id="log" name="log" readonly="readonly"></textarea><br/>
		      <label for="message">Write message</label>
		      <input type="text" id="message" name="message" /><br/>
		      <input type="text" id="addmms" name="addmms" />
		      <button id="picture_accept" name="picture_accept">Accept picture</button>
		      <button id="picture_decline" name="picture_decline">Decline picture</button>
		    </div>
	 </div>
    
    <div id="declined">
	    <div id="contentFlowDeclined" class="ContentFlow">
	        <!-- should be place before flow so that contained images will be loaded first -->
	        <div class="loadIndicator"><div class="indicator"></div></div>
	
	        <div class="flow">
	
	        </div>
	        <div class="globalCaption"></div>
	        <div class="scrollbar">
	            <div class="slider"><div class="position"></div></div>
	        </div>
	        <div class="hiddenItems" style="display:none;">
	        </div>
	    </div>
    </div>
  </body>
</html>