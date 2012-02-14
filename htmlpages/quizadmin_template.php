<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link type="text/css" href="css/custom-theme/jquery-ui-1.8.17.custom.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.17.custom.min.js"></script>
<link rel="stylesheet" href="css/quizstyling.css">
<script type="text/javascript" src="js/quizjavascript.js"></script>

<title>Quiz admin</title>
</head>
<body>
<div id="newquizoverlay" class="dialog">
<input type="text" name="quizname" id="inputquizname" />
<button value="addquiz" id="addquiznamebutton">Add Quiz</button>
</div>
<div id="newquestionoverlay" class="dialog">
<form id="newquestionform">
<div class="header" id="questiontitle">

<input type="text" name="questiontext" id="inputquestiontext" />
<input type="hidden" name="questionid" id="hiddenquestionid" />
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



<select id="quizname">
</select>

<a id="newquiz" href="">New Quiz</a>
<div id="questions"></div>
<div>
<a id="newquestion" href="">New Question</a>
</div>
</body>
</html>