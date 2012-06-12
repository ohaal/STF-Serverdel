<?php
require 'config.php';
require 'mms.php';
$mms = new mmsReaction();
$accepted = $mms->getAccepted();
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
    <script type="text/javascript" src="js/mmsclient.js"></script>
    <script type="text/javascript" src="js/mmswheel.js"></script>
    <script type="text/javascript">
    	// PHP arrays (via JSON) to JS object "maps" with msgid as key - used for initial view
		var localAcceptedList = <?php print(json_encode($accepted)); ?>;
		for (var i in localAcceptedList) {
			localAccepted[localAcceptedList[i].msgid] = localAcceptedList[i];
		}
    </script>
  </head>
  <body>
  <div id="hiddenmetainfo" style="display:none;">
  	<span id="wshost"><?php print($config['ws_ip_wan_bind']); ?></span>
  	<span id="wsport"><?php print($config['ws_port']); ?></span>
  </div>
  <div id="main">
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
					<?php print(!empty($mmsItem->text) ? $mmsItem->text : ''); ?>
				</div>
			</div>
<?php
}
?>
        </div>
        <div class="globalCaption"></div>
    </div>
  </div>
  </body>
</html>