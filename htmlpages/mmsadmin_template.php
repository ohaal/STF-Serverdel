<?php
require 'config.php';
require 'mms.php';
$mms = new mmsReaction();
$queued = $mms->getQueued();
$accepted = $mms->getAccepted();
$declined = $mms->getDeclined();
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
    <script type="text/javascript">
    	// PHP arrays (via JSON) to JS object "maps" with msgid as key - used for initial view
		var localQueuedList = <?php print(json_encode($queued)); ?>;
		var localAcceptedList = <?php print(json_encode($accepted)); ?>;
		var localDeclinedList = <?php print(json_encode($declined)); ?>;
		for (var i in localQueuedList) {
			localQueued[localQueuedList[i].msgid] = localQueuedList[i];
		}
		for (var i in localAcceptedList) {
			localAccepted[localAcceptedList[i].msgid] = localAcceptedList[i];
		}
		for (var i in localDeclinedList) {
			localDeclined[localDeclinedList[i].msgid] = localDeclinedList[i];
		}
    </script>
  </head>
  <body>
  <div id="hiddenmetainfo" style="display:none;">
  	<span id="wshost"><?php print($config['ws_ip_wan_bind']); ?></span>
  	<span id="wsport"><?php print($config['ws_port']); ?></span>
  </div>
  <div id="splashscreen">
    <input type="text" id="inputnick" name="inputnick" />
    <button value="setnick" id="setnickbutton">Set nick</button>
  </div>
  <div id="main">
  	<div id="toggle_menu" class="toogleMenu">
  		<a href="#" id="showqueued">Queued (<span class="queuedamount"><?php print(count($queued)); ?></span>)</a> |
  		<a href="#" id="showaccepted">Accepted (<span class="acceptedamount"><?php print(count($accepted)); ?></span>)</a> |
  		<a href="#" id="showdeclined">Declined (<span class="declinedamount"><?php print(count($declined)); ?></span>)</a>
  	</div>
    <div id="contentFlowQueued" class="ContentFlow">
        <!-- should be place before flow so that contained images will be loaded first -->
        <div class="loadIndicator"><div class="indicator"></div></div>

        <div class="flow">
<?php
// Add initial items to flow (__MUCH__ more efficient (and cleaner) than dynamically adding via JavaScript)
foreach ($queued as $mmsItem) {
?>
			<div class="item">
				<img class="content" src="<?php print($mmsItem->imgpath); ?>" id="msgid<?php print($mmsItem->msgid); ?>" target="_blank"/>
				<div class="caption">
					Message: <?php print($mmsItem->text); ?><br/>
					Phonenumber: <?php print($mmsItem->phonenumber); ?><br/>
					Received: <?php print($mmsItem->recvdate); ?>
				</div>
			</div>
<?php
}
?>
        </div>
        <div class="globalCaption"></div>
        <div class="scrollbar">
            <div class="slider"><div class="position"></div></div>
            <div class="preButton"></div>
            <div class="nextButton"></div>
        </div>
    </div>
    
    <div id="contentFlowAccepted" class="ContentFlow">
        <!-- should be place before flow so that contained images will be loaded first -->
        <div class="loadIndicator"><div class="indicator"></div></div>

        <div class="flow">
<?php
// Add initial items to flow (__MUCH__ more efficient (and cleaner) than dynamically adding via JavaScript)
foreach ($accepted as $mmsItem) {
?>
			<div class="item">
				<img class="content" src="<?php print($mmsItem->imgpath); ?>" id="msgid<?php print($mmsItem->msgid); ?>" target="_blank"/>
				<div class="caption">
					Message: <?php print($mmsItem->text); ?><br/>
					Phonenumber: <?php print($mmsItem->phonenumber); ?><br/>
					Received: <?php print($mmsItem->recvdate); ?>
				</div>
			</div>
<?php
}
?>
        </div>
        <div class="globalCaption"></div>
        <div class="scrollbar">
            <div class="slider"><div class="position"></div></div>
            <div class="preButton"></div>
            <div class="nextButton"></div>
        </div>
    </div>
    
    <div id="contentFlowDeclined" class="ContentFlow">
        <!-- should be place before flow so that contained images will be loaded first -->
        <div class="loadIndicator"><div class="indicator"></div></div>

        <div class="flow">
<?php
// Add initial items to flow (__MUCH__ more efficient (and cleaner) than dynamically adding via JavaScript)
foreach ($declined as $mmsItem) {
?>
			<div class="item">
				<img class="content" src="<?php print($mmsItem->imgpath); ?>" id="msgid<?php print($mmsItem->msgid); ?>" target="_blank"/>
				<div class="caption">
					Message: <?php print($mmsItem->text); ?><br/>
					Phonenumber: <?php print($mmsItem->phonenumber); ?><br/>
					Received: <?php print($mmsItem->recvdate); ?>
				</div>
			</div>
<?php
}
?>
        </div>
        <div class="globalCaption"></div>
        <div class="scrollbar">
            <div class="slider"><div class="position"></div></div>
            <div class="preButton"></div>
            <div class="nextButton"></div>
        </div>
    </div>
    
    <button id="picture_accept" name="picture_accept">Accept picture</button>
    <button id="picture_decline" name="picture_decline">Decline picture</button>
	    
    <div id="chat">
      <textarea id="log" name="log" readonly="readonly"></textarea>
      <select id="userlist" name="userlist" multiple>
      </select>
      <input type="text" id="message" name="message" /><br/>
    </div>
  </div>
  </body>
</html>