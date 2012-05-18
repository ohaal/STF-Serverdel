<?php
require 'config.php'; 
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset='UTF-8' />
    <style>
		input, textarea {border:1px solid #CCC;margin:0px;padding:0px}
		#chat {max-width:410px;margin:auto}
		#log {width:300px;height:200px;float:left}
		#userlist {width:100px;height:202px;float:left}
		#message {width:400px;line-height:20px;clear:both;}
    </style>
    <link rel="stylesheet" title="Standard" href="contentflow/styles.css" type="text/css" media="screen" />
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
		google.load("jquery", "1.7.1");
		google.load("jqueryui", "1.8.17");
    </script>
    <script type="text/javascript" src="contentflow/contentflow_src.js"></script>
    <script type="text/javascript" src="js/fancywebsocket.js"></script>
    <script type="text/javascript" src="js/mmsadminclient.js"></script>
  </head>
  <body>
  <div id="hiddenmetainfo" style="display:none;">
  	<span id="wshost"><?php print($config['ws_host']); ?></span>
  	<span id="wsport"><?php print($config['ws_port']); ?></span>
  </div>
  <div id="splashscreen">
    <input type="text" id="inputnick" name="inputnick" />
    <button value="setnick" id="setnickbutton">Set nick</button>
  </div>
  <div id="main">
  	<div id="toggle_menu" class="toogleMenu">
  		<a id="showqueued" href="#" onclick="toggle_visibility('undetermined');">Queue</a> |
  		<a id="showaccepted" href="#" onclick="toggle_visibility('accepted');">Accepted</a> |
  		<a id="showdeclined" href="#" onclick="toggle_visibility('declined');">Declined</a>
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
    
    <button id="picture_accept" name="picture_accept">Accept picture</button>
    <button id="picture_decline" name="picture_decline">Decline picture</button>
	    
    <div id="chat">
      <textarea id="log" name="log" readonly="readonly"></textarea>
      <select id="userlist" name="userlist" multiple>
      </select>
      <input type="text" id="message" name="message" /><br/>
      <input type="text" id="addmms" name="addmms" />
    </div>
  </div>
  </body>
</html>