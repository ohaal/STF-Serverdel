<?php
include_once 'ajaxheader.php';
require_once ('../quizadmin.php');
$quizadmin = new quizAdmin ();
$quiznamesArray = $quizadmin->getAllQuizNames ();


$ret = "";
$ret .= "[ ";
foreach ($quiznamesArray as $key => $value) {
	$ret.= '{"quizid":'.$key.', "quizname": "'.$value.'"},';
}
$ret = substr($ret, 0, -1); //remove last ","
$ret .= "]";

print($ret);
?>