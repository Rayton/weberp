<?php
/*PDF page header for price list report */
if ($PageNumber>1){
	$pdf->newPage();
}

if (@$_SESSION['LogoFile']) {
	$pdf->addJpegFromFile($_SESSION['LogoFile'] ,$FormDesign->logo->x,$Page_Height-110,$FormDesign->logo->width,$FormDesign->logo->height);	
}

if (@$_SESSION['LogoFile2']) {
	$pdf->addJpegFromFile($_SESSION['LogoFile2'] ,500,$Page_Height-110,$FormDesign->logo->width,$FormDesign->logo->height);
}

$company = array (
	'lgfontSize' =>  20,
	'x' => 325,
	'y' => 30,
	'details' =>$_SESSION['CompanyRecord']
);

$pdf->SetFontSize(20);
$pdf->Cell( $w=0, $h = 0, $txt = $company['details']['coyname'] , $border = 0, $ln = 1, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->SetFontSize(10);

if (@ $company['details']['location_1'] && @$company['details']['location_2']) {
	$pdf->setX(110);
	$pdf->Cell( $w=250, $h = 11, $txt = $company['details']['location_1'] , $border = 0, $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(330);
	$pdf->Cell( $w=250, $h = 11, $txt = $company['details']['location_2'] , $border = 0, $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
}else {
	$pdf->Cell( $w=0, $h = 11, $txt = $company['details']['location_1'] , $border = 0, $ln = 1, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
}

if (@$company['details']['office_1'] && @$company['details']['regoffice2']) {
	$pdf->setX(110);
	$pdf->Cell( $w=250, $h = 11, $txt = $company['details']['office_1'] , $border = 0, $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(330);
	$pdf->Cell( $w=250, $h = 11, $txt = $company['details']['regoffice2'] , $border = 0, $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );	
}else {
	$pdf->Cell( $w=0, $h = 11, $txt = $company['details']['office_1'] , $border = 0, $ln = 1, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
}


if (@$company['details']['regoffice1'] && @$company['details']['regoffice6']) {
	$pdf->setX(110);
	$pdf->Cell( $w=250, $h = 11, $txt = $company['details']['regoffice1'] , $border = 0, $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(330);
	$pdf->Cell( $w=250, $h = 11, $txt = $company['details']['regoffice6'] , $border = 0, $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
}else {
	$pdf->Cell( $w=0, $h = 11, $txt = $company['details']['regoffice1'] , $border = 0, $ln = 1, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
}


if (@$company['details']['regoffice5']) {
	$pdf->setX(110);
	$pdf->Cell( $w=250, $h = 11, $txt = $company['details']['regoffice5'].', '. $company['details']['regoffice6'] , $border = 0, $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(330);
	$pdf->Cell( $w=250, $h = 11, $txt ='' , $border = 0, $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );	
}else {
	$pdf->Cell( $w=0, $h = 11, $txt = $company['details']['regoffice5'].', '. $company['details']['regoffice6'] , $border = 0, $ln = 1, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );	
}


if (@ $company['details']['telephone'] && @$company['details']['telephone_2']) {
	$pdf->setX(110);
	$pdf->Cell( $w=250, $h = 11, $txt = "Phone: ". $company['details']['telephone'].' Fax: '. $company['details']['fax'] , $border = 0, $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(330);
	$pdf->Cell( $w=250, $h = 11, $txt ='Tel '. $company['details']['telephone_2'] . " Fax" . $company['details']['fax_2']  , $border = 0, $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );	
}else {
	$pdf->Cell( $w=0, $h = 11, $txt = "Phone: ". $company['details']['telephone'].' Fax: '. $company['details']['fax'] , $border = 0, $ln = 1, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
}


$pdf->setX(110);
$pdf->Cell( $w=250, $h = 11, $txt = "E-mail: ". $company['details']['email'] , $border = 0, $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(330);
$pdf->Cell( $w=250, $h = 11, $txt =$company['details']['website'] , $border = 0, $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->ln(10);

$pdf->SetFontSize(18);

$pdf->setX(190);
$pdf->Cell( $w=220, $h = 20, $txt = "GOODS RECEIVED NOTE" , $border = "B", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->ln(5);

$pdf->SetFontSize(12);

$pdf->setX(190);
$pdf->Cell( $w=200, $h = 11, $txt = "GRN Number:" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(210);
$pdf->Cell( $w=200, $h = 11, $txt = $GRNNo , $border = "", $ln = 1, $align = 'R', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );

$pdf->setX(190);
$pdf->Cell( $w=200, $h = 11, $txt = "Printed Date:" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(210);
$pdf->Cell( $w=200, $h = 11, $txt = Date($_SESSION['DefaultDateFormat']) , $border = "", $ln = 1, $align = 'R', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->ln(5);

$pdf->Cell( $w=30, $h = 11, $txt = "Supplied By:" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(100);
$pdf->SetFontSize(10);
$pdf->Cell( $w=200, $h = 11, $txt = $SuppRow['suppname'] , $border = "", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'B' );
$pdf->ln(5);

$pdf->SetFontSize(9);

$pdf->setX(50);
$pdf->Cell( $w="", $h = 11, $txt = $SuppRow['address1'] , $border = "", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->ln(2);

$pdf->setX(50);
$pdf->Cell( $w="", $h = 11, $txt = $SuppRow['address2'] , $border = "", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->ln(2);

$pdf->setX(50);
$pdf->Cell( $w="", $h = 11, $txt = $SuppRow['address3'] . " " . $SuppRow['address4'] , $border = "", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->ln(2);

$pdf->setX(50);
$pdf->Cell( $w="", $h = 11, $txt = $SuppRow['address5'] , $border = "", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->ln(2);


/*Draw a rectangle to put the headings in     */
 // echo "<pre>"; print_r($FormDesign->HeaderRectangle);echo "</pre>";die;
$pdf->Rectangle(30, 580, 540, 45);

$pdf->SetFontSize(12);
$pdf->Cell( $w=150, $h = 11, $txt = "P.O Number" , $border = "", $ln = 0, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(150);
$pdf->Cell( $w=150, $h = 11, $txt = "Order Date" , $border = "", $ln = 0, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(250);
$pdf->Cell( $w=300, $h = 11, $txt = "Invoice/Cash Sale/Delivery Note No" , $border = "", $ln = 0, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(530);
$pdf->Cell( $w=300, $h = 11, $txt = $SuppRow['currcode'] , $border = "", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->ln(1);

$pdf->SetFontSize(12);
$pdf->Cell( $w=150, $h = 11, $txt =  $_GET['PONo'] , $border = "", $ln = 0, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(150);
// order date
$pdf->Cell( $w=150, $h = 11, $txt = "" , $border = "", $ln = 0, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(250);
// invoice number
$pdf->Cell( $w=300, $h = 11, $txt = "" , $border = "", $ln = 0, $align = 'C', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(530);
$pdf->Cell( $w=300, $h = 11, $txt = "" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->ln(35);

$pdf->Rectangle(30, 525, 540, 20);

$pdf->SetFontSize(10);
$pdf->Cell( $w=100, $h = 11, $txt = "Product Code" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(100);
$pdf->Cell( $w=150, $h = 11, $txt = "Description" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(250);
$pdf->Cell( $w=100, $h = 11, $txt = "Date Recd" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
$pdf->setX(300);
$pdf->Cell( $w=100, $h = 11, $txt = "Qty Ord" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );

$pdf->setX(350);
$pdf->Cell( $w=100, $h = 11, $txt = "Qty Rcvd" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );

$pdf->setX(400);
$pdf->Cell( $w=100, $h = 11, $txt = "Bal to recv" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );

$pdf->setX(460);
$pdf->Cell( $w=100, $h = 11, $txt = "Unit Price" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );

$pdf->setX(520);
$pdf->Cell( $w=100, $h = 11, $txt = "Value" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );




$pdf->ln(2);


// /*set up the headings */
// $LeftOvers = $pdf->addText($FormDesign->Headings->Column1->x,$Page_Height - $FormDesign->Headings->Column1->y, $FormDesign->Headings->Column1->FontSize,  _('Item Number'));
// $LeftOvers = $pdf->addText($FormDesign->Headings->Column2->x,$Page_Height - $FormDesign->Headings->Column2->y, $FormDesign->Headings->Column2->FontSize,  _('Description'));
// /*resmart mods*/
// $LeftOvers = $pdf->addText($FormDesign->Headings->Column3->x,$Page_Height - $FormDesign->Headings->Column3->y, $FormDesign->Headings->Column3->FontSize,  str_pad(_('Date Recd'),22,' ',STR_PAD_LEFT));
// //$LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column3->x,$Page_Height - $FormDesign->Headings->Column3->y, $FormDesign->Headings->Column4->Length, $FormDesign->Headings->Column3->FontSize,  _('Date Recd'), 'right');
// /*resmart ends*/
// $LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column4->x,$Page_Height - $FormDesign->Headings->Column4->y, $FormDesign->Headings->Column4->Length, $FormDesign->Headings->Column4->FontSize,  _('Qty in Suppliers UOM'), 'right');
// $LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column5->x,$Page_Height - $FormDesign->Headings->Column5->y, $FormDesign->Headings->Column5->Length, $FormDesign->Headings->Column5->FontSize,  _('Qty in Stock UOM'), 'right');

/*Draw a rectangle to put the data in     */
// $pdf->Rectangle($FormDesign->DataRectangle->x, $Page_Height - $FormDesign->DataRectangle->y, $FormDesign->DataRectangle->width,$FormDesign->DataRectangle->height);

// $pdf->Line($FormDesign->LineAboveFooter->startx, $Page_Height - $FormDesign->LineAboveFooter->starty, $FormDesign->LineAboveFooter->endx,$Page_Height - $FormDesign->LineAboveFooter->endy);

// $pdf->Line($FormDesign->Column1->startx, $Page_Height - $FormDesign->Column1->starty, $FormDesign->Column1->endx,$Page_Height - $FormDesign->Column1->endy);
// $pdf->Line($FormDesign->Column3->startx, $Page_Height - $FormDesign->Column3->starty, $FormDesign->Column3->endx,$Page_Height - $FormDesign->Column3->endy);
// $pdf->Line($FormDesign->Column4->startx, $Page_Height - $FormDesign->Column4->starty, $FormDesign->Column4->endx,$Page_Height - $FormDesign->Column4->endy);
// $pdf->Line($FormDesign->Column5->startx, $Page_Height - $FormDesign->Column5->starty, $FormDesign->Column5->endx,$Page_Height - $FormDesign->Column5->endy);

$PageNumber++;
?>
