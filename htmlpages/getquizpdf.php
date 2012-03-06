<?
if (isset ( $_POST ['quizid'] )) {
	$quizid = $_POST ['quizid'];
	if (! is_numeric ( $quizid )) {
		die ();
	}
} else {
	echo "No quiz id set";
	die ();
}
//TODO: Add validation of these as well
$quizheader = $_POST['header'];
$quizfooter = $_POST['footer'];


//Need FDPF to generate PDF's. Check your local distro for installation
require_once('fpdf.php');
require_once ('quizadmin.php');

$quizadmin = new quizAdmin ();
$questionsArray = $quizadmin->getAllQuestionsForQuiz ( $quizid );

$pdf = new FPDF('P','mm','A4');
foreach ( $questionsArray as $key => $question ) {
	$pdf->AddPage();
	$pdf->SetY(20);
	$pdf->SetFont('Arial','B',24);
	$pdf->MultiCell(0,30,$quizheader,0,'C');
	$pdf->SetFont('Arial','B',18);
	$questionHeader = $_POST['quizheader-'.$question['questionnumber']];
	$pdf->MultiCell(0,22,$questionHeader,0,'C');
	$file = 'quizimage-'.$question['questionnumber'];
	$filetype = $_FILES[$file]['type'];
	if (stripos($filetype, 'jpeg') || stripos($filetype, 'jpg')) {
		$type = 'JPEG';
	} else if (stripos($filetype, 'png')) {
		$type = 'PNG';
	}
	
	//TODO: More validation of the image
	$pdf->Image($_FILES[$file]['tmp_name'],30,null,150,0,$type);
	$pdf->Cell(0,10,'',0,1,'L');
	$text = $question['questiontext'];
	$pdf->SetFont('Arial','B',20);
	$pdf->Cell(10);
	$pdf->Cell(0,22,$text,0,1,'L');
	$pdf->SetFont('Arial','',16);
	if (isset ( $question ['answers'] ) && sizeof ( $question ['answers'] ) > 0) {
		foreach ( $question ['answers'] as $answer ) {
			$pdf->Cell(20);
			$pdf->Cell(0,8,$answer ['answernumber'].".  ".$answer ['answertext'],0,1,'L');
		}
	}
	$bottom = str_replace('$q', $question['questionnumber'],$quizfooter);
	$pdf->SetFont('Arial','B',18);
	$pdf->Cell(0,15,'',0,1,'L');
	$pdf->MultiCell(180,8,$bottom,0,'C');
	
	$file = 'imgbottomleft';
	$filetype = $_FILES[$file]['type'];
	if (stripos($filetype, 'jpeg') || stripos($filetype, 'jpg')) {
		$type = 'JPEG';
	} else if (stripos($filetype, 'png')) {
		$type = 'PNG';
	}
	$pdf->Image($_FILES[$file]['tmp_name'],10,260,25,0,$type);
	
	$file = 'imgbottomright';
	$filetype = $_FILES[$file]['type'];
	if (stripos($filetype, 'jpeg') || stripos($filetype, 'jpg')) {
		$type = 'JPEG';
	} else if (stripos($filetype, 'png')) {
		$type = 'PNG';
	}
	$pdf->Image($_FILES[$file]['tmp_name'],170,260,25,0,$type);
	
	
	/*$bottomleftimg = $_POST['imgbottomleft'];
	 $bottomrightimg = $_POST['imgbottomright'];*/
	
}
$pdf->Output("quizposter.pdf",'I');
?>


