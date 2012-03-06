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

//Need FDPF to generate PDF's. Check your local distro for installation
require_once('fpdf.php');
require_once ('quizadmin.php');

$quizadmin = new quizAdmin ();
$questionsArray = $quizadmin->getAllQuestionsForQuiz ( $quizid );

$pdf = new FPDF('P','mm','A4');
foreach ( $questionsArray as $key => $question ) {
	$pdf->AddPage();
	$pdf->SetY(30);
	$pdf->SetFont('Arial','B',24);
	$string = $question['questionnumber'] .".  ".$question['questiontext'];
	$pdf->Cell(0,30,$string,0,1,'C');
	$pdf->SetFont('Arial','',16);
	if (isset ( $question ['answers'] ) && sizeof ( $question ['answers'] ) > 0) {
		foreach ( $question ['answers'] as $answer ) {
			$pdf->Cell(0,8,$answer ['answernumber'].".  ".$answer ['answertext'],0,1,'L');
		}
	}
}
$pdf->Output();
?>