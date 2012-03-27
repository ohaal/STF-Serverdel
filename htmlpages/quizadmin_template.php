<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
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
	    <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>You cannot make changes to the quiz once it is activated. Are you sure?</p>
      </div>
      
      <div id="confirm-end" class="dialog" title="Deactivate and end quiz?">
	    <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>This will end the quiz and answers will no longer be accepted. There is no going back from this. Are you sure?</p>
      </div>

      <div id="createpdfoverlay" class="dialog">
        <form name="createpdf" action="getquizpdf.php" method="post" enctype="multipart/form-data">
          <input class="quizidvalue" type="hidden" name="quizid" value="" />
          Header: <input type="text" name="header" /><br/>
          Ingress: <textarea name="ingress"></textarea><br/>
          <hr />
          <div id="pdfquestions"></div>
          Footer: <input type="text" name="footer" value='Send SMS med "STF $qnum &lt;riktig svarnummer&gt;" til 2000'/><br/>
          <b>$qnum</b> will be replaced with the question number.
          <hr />
          Image bottom left: <input type="file" name="imgbottomleft" /><br/>
          Image bottom right: <input type="file" name="imgbottomright" /><br />
          <input type="submit" value="Create PDF" onclick="this.form.target='_blank';return true;"/>
        </form>
      </div>

      <div id="newquizoverlay" class="dialog">
        Name: <input type="text" name="quizname" class="inputquiz" id="inputquizname" />
        Keyword: <input type="text" name="quizkeyword" class="inputquiz" id="inputquizkeyword" />
        <button value="addquiz" id="addquiznamebutton">Add Quiz</button>
      </div>

      <div id="newquestionoverlay" class="dialog">
        <form id="newquestionform">
          <div class="header" id="questiontitle">

            <input type="text" name="questiontext" id="inputquestiontext" />
            <input type="hidden" name="quizid" id="hiddenquizid" />
            <input type="hidden" name="questionnumber" id="hiddenquestionnumber" />
          </div>
<?
for ($i=1;$i<6;$i++) {
	print('<div class="answer" id="answer'.$i.'">');
	print('<input type="text" class="newanswer" name="answer'.$i.'" />');
	print('<input type="radio" name="correctanswer" value='.$i.' />');
	print('</div>');
}
?>
          <button value="submitnewquestion" id="submitnewquestionbutton">Add question</button>
        </form>
      </div>

      <div id="newquizdiv">
	    <a id="newquiz" href="#">New Quiz</a>
      </div>
      <div id="quizselect" class="hideifnoquiz">
	    <select id="quizname"></select><br />
	    <a id="createpdf" href="#">Create PDF</a><br />
	    <a id="highscorelink" href="#" target="_blank">Show scores</a><br />
	    <a id="changequizstate" href="#"></a><br />
        <a id="newquestion" href="#">New Question</a>
      </div>
      <div id="questions"></div>
    </div>
  </body>
</html>