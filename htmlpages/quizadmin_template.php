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

<title>Quiz admin</title>
</head>
<body>
<div id="quizadmin" class="mainbody">
<div class="header center ui-corner-all"><h1 class="center">Quiz administration</h1></div>

<div id="dialog-confirm" class="dialog" title="Delete question?">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>This question will be deleted forever. Are you sure?</p>
</div>

<div id="newquizoverlay" class="dialog">
<input type="text" name="quizname" id="inputquizname" />
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
<button value="submitnewquestion" id="submitnewquestionbutton">OK</button>
</form>
</div>


<div id="quizselect">
<?
 //TODO: Need to either have a codeword set for a quiz or have a default quiz. 
?>
	<select id="quizname"></select>
	<a id="newquiz" href="#">New Quiz</a>
</div>
<div id="questions"></div>
<div id="newquestiondiv">
<a id="newquestion" href="#">New Question</a>
</div>
</div>
</body>
</html>