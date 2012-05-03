<!DOCTYPE html>
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
      <div class="header center ui-corner-all">
        <h1 class="center">Quiz scores</h1>
      </div>
      <div id="choosewinneroverlay" class="dialog">
        Minimum amount of correct answers required to win: <select id="correctanswersneeded"></select><br/>
        Amount of winners: <select id="amountofwinners"></select><br/>
        <input type="checkbox" checked id="prioritizemostcorrect"/> Rank teams with most correct answers higher<br/>
        <input class="quizidvalue" type="hidden" name="quizid" value="" />
        <button id="choosewinnerbutton">Generate table of winners</button><br/>
        <span class="errorlist" id="choosewinnerinfo"></span>
      </div>
      <div id="quizselect">
	    <a id="choosewinner" href="#">Find winners</a>
	    <select id="quizname"></select>
      </div>
      <div id="highscoretable_div"></div>
	  <div id="teamanswers_div"></div>
	  <br/>
      *Click on a team to specifically view their answers to each question.
    </div>
  </body>
</html>