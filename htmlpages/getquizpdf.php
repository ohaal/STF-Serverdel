<?php
set_time_limit(0); // We don't want it to timeout when uploading data and generating PDF
if (!empty( $_POST['quizid'] )) {
	$quizid = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $_POST['quizid']);
	if (!is_numeric( $quizid )) {
		die();
	}
} else {
	echo "No quiz id set";
	die();
}
if (!empty( $_POST['header'] )) {
	$quizheader = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $_POST['header']);
} else {
	echo 'No header set';
	die();
}
if (!empty( $_POST['footer'] )) {
	$quizfooter = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $_POST['footer']);
} else {
	echo 'No footer set';
	die();
}
if (!empty( $_POST['ingress'] )) {
	$quizingress = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $_POST['ingress']);
} else {
	$quizingress = '';
}

if (strlen($quizheader) > 200) {
	echo 'Quiz header too long, max 200 characters';
	die();
}
if (strlen($quizfooter) > 200) {
	echo 'Quiz footer too long, max 200 characters';
	die();
}
if (strlen($quizingress) > 1000) {
	echo 'Quiz ingress too long, max 1000 characters';
	die();
}

// Add quiz PDF values to database
require_once ('quizadmin.php');
$quizadmin = new quizAdmin();
// Make sure not to send ISO-8859-1, but UTF-8 into DB
$quizadmin->setQuizPDFData( $_POST['quizid'], $_POST['header'], $_POST['ingress'], $_POST['footer'] );

//Need FDPF to generate PDF's. Check your local distro for installation
require_once ('fpdf.php');
$questionsArray = $quizadmin->getAllQuestionsForQuiz( $quizid );

// Create a new PDF with Portrait orientation, mm as unit and in A4 format
$pdf = new FPDF( 'P', 'mm', 'A4' );

// Width and height of an A4 page in mm
define( "MAXWIDTH", 210 );
define( "MAXHEIGHT", 297 );

// Assuming 300 dpi (default for printing)
// Source: http://www.indigorose.com/forums/archive/index.php/t-13334.html
function estimate300DpiPixelToA4mm($pixels) {
	return round( $pixels * (21 / 248) );
}
function estimateCenterForObjectBasedOnLength($length, $maxLength) {
	return round( ($maxLength - $length) / 2 );
}

// Create a page for every question in the quiz
foreach ($questionsArray as $key => $question) {
	$pdf->AddPage();
	
	//////////////////////////////////////////////////////////////
	// Border around entire content of page
	//////////////////////////////////////////////////////////////
	$pdf->SetLineWidth( 1 );
	$pdf->SetDrawColor( 230, 11, 0 ); // Red
	$pdf->SetFillColor( 255, 255, 255 ); // White
	$pdf->Rect( 5, 5, MAXWIDTH - 10, MAXHEIGHT - 10 );
	
	//////////////////////////////////////////////////////////////
	// Header
	//////////////////////////////////////////////////////////////
	$pdf->SetY( 10 );
	$pdf->SetFont( 'Arial', 'B', 24 );
	$pdf->MultiCell( 0, 20, $quizheader, 0, 'C' );
	
	//////////////////////////////////////////////////////////////
	// Ingress
	//////////////////////////////////////////////////////////////
	if (!empty( $quizingress )) {
		$pdf->SetFont( 'Arial', null, 14 );
		$pdf->MultiCell( 0, 6, $quizingress, 0, 'C' );
	}
	$pdf->Cell( 0, 3, '', 0, 1, 'L' );
	
	//////////////////////////////////////////////////////////////
	// Question image
	//////////////////////////////////////////////////////////////
	$file = 'questionimage-' . $question['questionnumber'];
	if (!empty( $_FILES[$file]['tmp_name'] )) {
		// Attempt to convert image pixels to mm
		$imgSize = getimagesize( $_FILES[$file]['tmp_name'] );
		$imgW = estimate300DpiPixelToA4mm( $imgSize[0] );
		$imgH = estimate300DpiPixelToA4mm( $imgSize[1] );
		
		// We want width to never exceed 160mm
		$reqW = 160;
		// We want height to never exceed 110mm
		$reqH = 110;
		
		// Choose if width or height should decide size of image based on proportions of required vs real
		if (($reqW / $imgW) > ($reqH / $imgH)) {
			// Width is the largest, use height
			$imgW_force = null;
			$imgH_force = $reqH;
			
			// Find X that centers the image based on width of image and maxwidth
			$calcW = $imgW * ($reqH / $imgH);
			$imgX = estimateCenterForObjectBasedOnLength( $calcW, MAXWIDTH );
		} else {
			// Height is the largest, use width
			$imgW_force = $reqW;
			$imgH_force = null;
			
			// Find X that centers the image based on width of image and maxwidth
			$imgX = estimateCenterForObjectBasedOnLength( $reqW, MAXWIDTH );
		}
		
		// Detect filetype
		$filetype = $_FILES[$file]['type'];
		if (stripos( $filetype, 'jpeg' ) || stripos( $filetype, 'jpg' )) {
			$type = 'JPEG';
		} else if (stripos( $filetype, 'png' )) {
			$type = 'PNG';
		}
		// Place the actual image - null on Y value, for it to "float" in HTML terms
		$pdf->Image( $_FILES[$file]['tmp_name'], $imgX, null, $imgW_force, $imgH_force, $type );
	
	}

	//////////////////////////////////////////////////////////////
	// Question heading
	//////////////////////////////////////////////////////////////
	$pdf->Ln(4);
	$pdf->SetFont( 'Arial', 'B', 18 );
	$questionHeading = $_POST['questionheading-' . $question['questionnumber']];
	$pdf->MultiCell( 0, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $questionHeading), 0, 'C' );
	
	//////////////////////////////////////////////////////////////
	// Question ingress
	//////////////////////////////////////////////////////////////
	$pdf->SetFont( 'Arial', '', 16 );
	$questionIngress = $_POST['questioningress-' . $question['questionnumber']];
	$pdf->MultiCell( 0, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $questionIngress), 0, 'C' );
	
	// Add question PDF values to database, make sure not to send ISO-8859-1, but UTF-8 into DB
	$quizadmin->setQuestionPDFData($quizid, $question['questionnumber'], $questionHeading, $questionIngress);
	
	//////////////////////////////////////////////////////////////
	// Actual question
	//////////////////////////////////////////////////////////////
	$pdf->Ln(4);
	$pdf->Cell(8);
	$text = $question['questionnumber'].'. '.iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $question['questiontext']); // FPDF does not support UTF-8
	$pdf->SetFont( 'Arial', 'B', 20 );
	$pdf->MultiCell( 0, 8, $text, 0, 1, 'L' );
	
	//////////////////////////////////////////////////////////////
	// Answers
	//////////////////////////////////////////////////////////////
	$pdf->SetFont( 'Arial', '', 16 );
	if (isset( $question['answers'] ) && sizeof( $question['answers'] ) > 0) {
		foreach ($question['answers'] as $answer) {
			$pdf->Cell( 20 );
			// Simple fix for request about using letters instead of numbers as answer alternatives @replacealphawithnumber
			$search  = array('1','2','3','4','5');
			$replace = array('A','B','C','D','E'); 
			$answernumber = str_replace($search, $replace, $answer['answernumber']);
			$pdf->Cell( 0, 8, $answernumber . ".  " . iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $answer['answertext']), 0, 1, 'L' );// FPDF does not support UTF-8
		}
	}
	
	//////////////////////////////////////////////////////////////
	// Bottom left image
	//////////////////////////////////////////////////////////////
	$file = 'imgbottomleft';
	if (!empty( $_FILES[$file]['tmp_name'] )) {
		// We want width to never exceed 50mm
		$reqW = 50;
		// We want height to never exceed 20mm
		$reqH = 20;
		
		// Attempt to convert image pixels to mm 
		$imgSize = getimagesize( $_FILES[$file]['tmp_name'] );
		$imgW = estimate300DpiPixelToA4mm( $imgSize[0] );
		$imgH = estimate300DpiPixelToA4mm( $imgSize[1] );
		
		// Choose if width or height should decide size of image based on proportions of required vs real
		if (($reqW / $imgW) > ($reqH / $imgH)) {
			$imgW_force = null;
			$imgH_force = $reqH; // We choose that height should decide image size

			// Bottom of image hits absolute bottom of page
			$imgY = MAXHEIGHT - $reqH;
		} else {
			// Height is the largest, use width
			$imgW_force = $reqW; // We choose that width should decide image size
			$imgH_force = null;
			
			// Bottom of image hits absolute bottom of page
			// We use the proportions of required width and actual width multiplied by height
			$calcH = ($reqW / $imgW) * $imgH;
			$imgY = MAXHEIGHT - $calcH;
		}
		
		// Bottom of image is 10mm from bottom
		$imgY -= 10;
		// Image is 10mm from left
		$imgX = 10;
		
		// Value used to decide how far footer will be placed from bottom
		$minY = $imgY;
		
		// Detect filetype
		$filetype = $_FILES[$file]['type'];
		if (stripos( $filetype, 'jpeg' ) || stripos( $filetype, 'jpg' )) {
			$type = 'JPEG';
		} else if (stripos( $filetype, 'png' )) {
			$type = 'PNG';
		}
		$pdf->Image( $_FILES[$file]['tmp_name'], $imgX, $imgY, $imgW_force, $imgH_force, $type );
	}
	
	//////////////////////////////////////////////////////////////
	// Bottom right image
	//////////////////////////////////////////////////////////////
	$file = 'imgbottomright';
	if (!empty( $_FILES[$file]['tmp_name'] )) {
		// We want width to never exceed 50mm
		$reqW = 50;
		// We want height to never exceed 20mm
		$reqH = 20;
		
		// Attempt to convert image pixels to mm 
		$imgSize = getimagesize( $_FILES[$file]['tmp_name'] );
		$imgW = estimate300DpiPixelToA4mm( $imgSize[0] );
		$imgH = estimate300DpiPixelToA4mm( $imgSize[1] );
		
		// Choose if width or height should decide size of image based on proportions of required vs real
		if (($reqW / $imgW) > ($reqH / $imgH)) {
			$imgW_force = null;
			$imgH_force = $reqH; // We choose that height should decide image size
			

			// Bottom of image hits absolute bottom of page
			$imgY = MAXHEIGHT - $reqH;
			
			// We use the proportions of required height and actual height multiplied by width
			$calcW = ($reqH / $imgH) * $imgW;
			// Right of image hits absolute right of the page
			$imgX = MAXWIDTH - $calcW;
		} else {
			// Height is the largest, use width
			$imgW_force = $reqW; // We choose that width should decide image size
			$imgH_force = null;
			
			// We use the proportions of required width and actual width multiplied by height
			$calcH = ($reqW / $imgW) * $imgH;
			// Bottom of image hits absolute bottom of page
			$imgY = MAXHEIGHT - $calcH;
			
			// Right of image hits absolute right of the page
			$imgX = MAXWIDTH - $reqW;
		}
		
		// Bottom of image is 10mm from bottom
		$imgY -= 10;
		// Right of image is 10mm from the right
		$imgX -= 10;
		
		// Value used to decide how far footer will be placed from bottom
		if (!isset($minY) || $imgY < $minY) {
			$minY = $imgY;
		}
		
		// Detect filetype
		$filetype = $_FILES[$file]['type'];
		if (stripos( $filetype, 'jpeg' ) || stripos( $filetype, 'jpg' )) {
			$type = 'JPEG';
		} else if (stripos( $filetype, 'png' )) {
			$type = 'PNG';
		}
		$pdf->Image( $_FILES[$file]['tmp_name'], $imgX, $imgY, $imgW_force, $imgH_force, $type );
	}
	
	//////////////////////////////////////////////////////////////
	// Footer
	//////////////////////////////////////////////////////////////
	if (!isset($minY)) {
		$minY = MAXHEIGHT - 20;
	}
	$pdf->SetFont( 'Arial', 'B', 16 );
	//	$pdf->Cell(0,15,'',0,1,'L');
	// Allow usage of question number in footer by using key '$qnum' in text
	$bottom = str_replace( '$qnum', $question['questionnumber'], $quizfooter );
	$pdf->SetY( $minY - 10 ); // Place footer 10mm above tallest bottom image
	$pdf->MultiCell( null, 8, $bottom, 0, 'C' );
}
if (count($questionsArray) > 0) {
	$pdf->Output( "quizposter.pdf", 'I' );
}
else {
	echo 'No questions in quiz!';
}
?>
