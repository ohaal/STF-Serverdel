<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link type="text/css" href="css/custom-theme/jquery-ui-1.8.17.custom.css" rel="stylesheet" />

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
	google.load("jquery", "1.7.1");
	google.load("jqueryui", "1.8.17");
	google.load('visualization', '1.0', {'packages':['corechart']});
</script>

<link rel="stylesheet" href="css/quizstyling.css">
<script type="text/javascript" src="js/quizjavascript.js"></script>

<title>Quiz scores</title>
</head>
<body>
<div id="quizscore" class="mainbody">
<div class="header center ui-corner-all"><h1 class="center">Quiz scores</h1></div>
<div id="quizselect">
	<select id="quizname"></select>
</div>
<div id="quizscores">

<div id="highscoretable_div"></div>

</div>
</div>
</body>
</html>