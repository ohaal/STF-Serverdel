<!DOCTYPE html>
<?php
require_once ('config.php');
?>
<html>
  <head>
    <meta charset="UTF-8" />
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
		google.load("jquery", "1.7.1");
		google.load("jqueryui", "1.8.17");
    </script>
    <link rel="stylesheet" href="css/quizstyling.css">
    <link type="text/css" href="css/custom-theme/jquery-ui-1.8.17.custom.css" rel="stylesheet" />
    <script type="text/javascript" src="js/quizjavascript.js"></script>
    <title>
      Quiz admin
    </title>
  </head>
  <body>
    <div id="quizadmin" class="mainbody">
      <div class="header center ui-corner-all">
        <h1 class="center">Quiz administration</h1>
      </div>

      <div id="dialog-confirm" class="dialog" title="Delete question?">
	    <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>This question will be deleted forever. Are you sure?</p>
      </div>
      
      <div id="confirm-activate" class="dialog" title="Activate and lock quiz?">
	    <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>You can not make changes to the quiz once it is activated. Are you sure?</p>
      </div>
      
      <div id="error-alreadyactivekeyword" class="dialog" title="Error: Keyword already active">
	    <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>You can not activate this quiz because there is another quiz active with the same keyword. End the active quiz or change the keyword.</p>
      </div>
      
      <div id="confirm-end" class="dialog" title="Deactivate and end quiz?">
	    <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>This will end the quiz and answers will no longer be accepted. There is no going back from this. Are you sure?</p>
      </div>

      <div id="createpdfoverlay" class="dialog">
        <form name="createpdf" action="getquizpdf.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
          <!--  A simple hack to force UTF-8 in IE, send UTF-8 only character -->
          <input name="iehack" type="hidden" value="&#9760;" />
          <!-- /IE Hack -->
          <input class="quizidvalue" type="hidden" name="quizid" value="" />
          Quiz Header: <input type="text" name="header" style="width: 440px;" />*<br/>
          Quiz Ingress: <textarea name="ingress"></textarea><br/>
          <hr />
          <div id="pdfquestions"></div>
          Quiz Footer: <input type="text" name="footer" style="width: 440px;" />*<br/>
          <b>$qnum</b> will be replaced with the question number.
          <hr />
          Image bottom left: <input type="file" name="imgbottomleft" /><br/>
          Image bottom right: <input type="file" name="imgbottomright" /><br />
          <hr />
          <b>Important:</b> If you experience problems with images not being displayed,
          this might be because large images may have trouble being displayed in the PDF,
          consider trying this online service to reduce the size of your images:
          <a href="http://www.shrinkpictures.com/" target="_blank">www.shrinkpictures.com</a><br />
          <hr />
          <input type="submit" value="Create PDF" id="createpdfsubmit"/>
          <span class="errorlist" id="createpdferror"></span>
        </form>
      </div>

      <div id="newquizoverlay" class="dialog">
        Name: <input type="text" name="quizname" class="inputquiz" id="inputquizname" />
        <?php
        $extraparam = '';
        if (!$config["keywords_enabled"]) {
        	$extraparam = ' value="'.$config["keywords_default"].'" style="display:none;"';
        }
        else {
        	print('Keyword*:');
        }
        ?>
		<input type="text" name="quizkeyword" class="inputquiz" id="inputquizkeyword"<?php print($extraparam); ?>/>
        <button value="addquiz" id="addquiznamebutton">Add Quiz</button>
		<?php
		if ($config["keywords_enabled"]) {
			print('<br/>* Keyword is the word used for identifying the quiz when sending answers in via SMS');
		}
		?>
        <span class="errorlist" id="newquizerror"></span>
      </div>

      <div id="newquestionoverlay" class="dialog">
        <form id="newquestionform">
          <div class="header" id="questiontitle">

            <input type="text" name="questiontext" id="inputquestiontext" />
            <input type="hidden" name="quizid" id="hiddenquizid" />
            <input type="hidden" name="questionnumber" id="hiddenquestionnumber" />
          </div>
<?php
for ($i=1;$i<=5;$i++) {
	print('<div class="answer" id="answer'.$i.'">');
	print('<input type="text" class="newanswer" name="answer'.$i.'" />');
	print('<input type="radio" name="correctanswer" value='.$i.' />');
	print('</div>');
}
?>
          Remember to select the correct answer before you add the question. (Click the circle to the right)
          <br/>
          <button value="submitnewquestion" id="submitnewquestionbutton">Add question</button>
        </form>
        <span class="errorlist" id="newquestionerror"></span>
      </div>

      <div id="newquizdiv">
	    <a id="newquiz" href="#">New Quiz</a>
      </div>
      <div id="quizselect" class="hideifnoquiz">
	    <select id="quizname"></select><br />
	    <a id="createpdf" href="#">Create PDF<br /></a>
	    <a id="highscorelink" href="#" target="_blank">Show scores<br /></a>
        <a id="newquestion" href="#">New Question<br /></a>
	    <a id="changequizstate" href="#"></a>
      </div>
      <div id="questions"></div>
    </div>
  </body>
</html>