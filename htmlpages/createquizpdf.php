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

<title>Generate Quiz PDF</title>
</head>
<body>
<?
if (isset ( $_GET ['quizid'] )) {
	$quizid = $_GET ['quizid'];
	if (! is_numeric ( $quizid )) {
		die ();
	}
} else {
	echo "No quiz id set";
	die ();
}
require_once ('quizadmin.php');
$quizadmin = new quizAdmin ();
$questionsArray = $quizadmin->getAllQuestionsForQuiz ( $quizid );
?>
<form name="createpdf" action="getquizpdf.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="quizid" value="<?=$quizid?>" />
Header: <input type="text" name="header" /><br/>
Footer: <input type="text" name="footer" value='Send SMS med "STF blahquizid $q &lt;svar&gt;" til 2000'/> TODO: Fiks teksten her :-)
<br/>
<br/>
<?
foreach ( $questionsArray as $key => $question ) {
	$string = $question['questionnumber'] .".  ".$question['questiontext'];
	echo $string;

	echo '<input type="text" name="quizheader-'.$question['questionnumber'].'" />';
	echo '<input type="file" name="quizimage-'.$question['questionnumber'].'" />';
	
	echo '<br/>';
}
?>

Image bottom left: <input type="file" name="imgbottomleft" /><br/>
Image bottom right: <input type="file" name="imgbottomright" />
<input type="submit" value="OK" />
</form>
</body>
</html>