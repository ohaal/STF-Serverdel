<?php
include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
$quiznamesArray = $quizadmin->getAllQuizNames ();

$ret = "";
$ret .= "[ ";
foreach ($quiznamesArray as $key => $value) {
	$ret.= '{"quizid":'.$key.', "quizname": "'.$value[0].'", "state":'.$value[1].', "keyword":"'.$value[2].'"},';
}
$ret = substr($ret, 0, -1); //remove last ","
$ret .= "]";

print($ret);
?>