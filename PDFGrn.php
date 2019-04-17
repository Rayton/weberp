<?php


include('includes/session.php');

if (isset($_GET['GRNNo'])) {
	$GRNNo=$_GET['GRNNo'];
} else {
	$GRNNo='';
}

$FormDesign = simplexml_load_file($PathPrefix.'companies/'.$_SESSION['DatabaseName'].'/FormDesigns/GoodsReceived.xml');

// Set the paper size/orintation
$FormDesign->PaperSize = "A4";
$PaperSize = $FormDesign->PaperSize;
$line_height=$FormDesign->LineHeight;
include('includes/PDFStarter.php');
$PageNumber=1;
$pdf->addInfo('Title', _('Goods Received Note') );

if ($GRNNo == 'Preview'){
	$myrow['itemcode'] = str_pad('', 15,'x');
	$myrow['deliverydate'] = '0000-00-00';
	$myrow['itemdescription'] =  str_pad('', 30,'x');
	$myrow['qtyrecd'] = 99999999.99;
	$myrow['decimalplaces'] =2;
	$myrow['conversionfactor']=1;
	$myrow['supplierid'] = str_pad('', 10,'x');
	$myrow['suppliersunit'] = str_pad('', 10,'x');
	$myrow['units'] = str_pad('', 10,'x');

	$SuppRow['suppname'] = str_pad('', 30,'x');
	$SuppRow['address1'] = str_pad('', 30,'x');
	$SuppRow['address2'] = str_pad('', 30,'x');
	$SuppRow['address3'] = str_pad('', 30,'x');
	$SuppRow['address4'] = str_pad('', 20,'x');
	$SuppRow['address5'] = str_pad('', 10,'x');
	$SuppRow['address6'] = str_pad('', 10,'x');
	$NoOfGRNs =1;
} else { //NOT PREVIEW

	$sql="SELECT grns.itemcode,
				grns.grnno,
				grns.deliverydate,
				grns.itemdescription,
				grns.qtyrecd,
				grns.supplierid,
				grns.supplierref,
				purchorderdetails.suppliersunit,
				purchorderdetails.conversionfactor,
				purchorderdetails.unitprice,
				purchorderdetails.quantityrecd,
				purchorderdetails.quantityord,
				stockmaster.units,
				stockmaster.decimalplaces
			FROM grns INNER JOIN purchorderdetails
			ON grns.podetailitem=purchorderdetails.podetailitem
			INNER JOIN purchorders on purchorders.orderno = purchorderdetails.orderno
			INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			LEFT JOIN stockmaster
			ON grns.itemcode=stockmaster.stockid
			WHERE grnbatch='". $GRNNo ."'";

	$GRNResult=DB_query($sql);
	$NoOfGRNs = DB_num_rows($GRNResult);
	if($NoOfGRNs>0) { //there are GRNs to print

		$sql = "SELECT suppliers.suppname,
						suppliers.address1,
						suppliers.address2 ,
						suppliers.address3,
						suppliers.address4,
						suppliers.address5,
						suppliers.address6,
						suppliers.currcode
				FROM grns INNER JOIN suppliers
				ON grns.supplierid=suppliers.supplierid
				WHERE grnbatch='". $GRNNo ."'";
		$SuppResult = DB_query($sql,_('Could not get the supplier of the selected GRN'));
		$SuppRow = DB_fetch_array($SuppResult);
	}
} // get data to print
if ($NoOfGRNs >0){
	$SupplierRef = DB_fetch_array($GRNResult);
	$SupplierRef = $SupplierRef['supplierref'];
	DB_data_seek($GRNResult,0);
	include ('includes/PDFGrnHeader.inc'); //head up the page

	$FooterPrintedInPage= 0;
	$FormDesign->Data->y = 350;
	$YPos=$FormDesign->Data->y;
	$total = 0;

	for ($i=1;$i<=$NoOfGRNs;$i++) {


		if ($GRNNo!='Preview'){
			$myrow = DB_fetch_array($GRNResult);
		}
		if (is_numeric($myrow['decimalplaces'])){
			$DecimalPlaces=$myrow['decimalplaces'];
		} else {
			$DecimalPlaces=2;
		}
		if (is_numeric($myrow['conversionfactor']) AND $myrow['conversionfactor'] !=0){
			$SuppliersQuantity=locale_number_format($myrow['qtyrecd']/$myrow['conversionfactor'],$DecimalPlaces);
		} else {
			$SuppliersQuantity=locale_number_format($myrow['qtyrecd'],$DecimalPlaces);
		}
		$OurUnitsQuantity=locale_number_format($myrow['qtyrecd'],$DecimalPlaces);
		$DeliveryDate = ConvertSQLDate($myrow['deliverydate']);

		$LeftOvers = $pdf->addTextWrap(32,$Page_Height-$YPos,$FormDesign->Data->Column1->Length,'9.3', $myrow['itemcode']);
		$LeftOvers = $pdf->addTextWrap(100,$Page_Height-$YPos,$FormDesign->Data->Column2->Length,'9.3', $myrow['itemdescription']);
		/*$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column3->x,$Page_Height-$YPos,$FormDesign->Data->Column3->Length,$FormDesign->Data->Column3->FontSize, $DeliveryDate);*/
		$LeftOvers = $pdf->addTextWrap(250,$Page_Height-$YPos,$FormDesign->Data->Column3->Length,'9.3', $DeliveryDate, 'left');
		$LeftOvers = $pdf->addTextWrap(300,$Page_Height-$YPos,$FormDesign->Data->Column4->Length,'9.3', $myrow['quantityord'], 'center');
		$LeftOvers = $pdf->addTextWrap(350,$Page_Height-$YPos,$FormDesign->Data->Column5->Length,'9.3', $myrow['qtyrecd'], 'center');
		$LeftOvers = $pdf->addTextWrap(400,$Page_Height-$YPos,$FormDesign->Data->Column6->Length,'9.3', number_format($myrow['quantityord'] - $myrow['quantityrecd'], 2), 'center');
		$LeftOvers = $pdf->addTextWrap(460,$Page_Height-$YPos, 40,'9.3', number_format($myrow['unitprice'], 2), 'right');
		$rowTotal = $myrow['unitprice'] * $myrow['qtyrecd'];
		$total += $rowTotal;

		$LeftOvers = $pdf->addTextWrap(520,$Page_Height-$YPos, 45,'9.3',number_format( $rowTotal, 2) , 'right');
		$YPos += $line_height;



		/* move to after serial print
		if($FooterPrintedInPage == 0){
			$LeftOvers = $pdf->addText($FormDesign->ReceiptDate->x,$Page_Height-$FormDesign->ReceiptDate->y,$FormDesign->ReceiptDate->FontSize, _('Date of Receipt: ') . $DeliveryDate);
			$LeftOvers = $pdf->addText($FormDesign->SignedFor->x,$Page_Height-$FormDesign->SignedFor->y,$FormDesign->SignedFor->FontSize, _('Signed for ').'______________________');
			$FooterPrintedInPage= 1;
		}
		*/

		if ($YPos >= $FormDesign->LineAboveFooter->starty){
			/* We reached the end of the page so finsih off the page and start a newy */
			//$PageNumber++;	// $PageNumber++ available in PDFGrnHeader.inc
			$FooterPrintedInPage= 0;	//Set FooterPrintedInPage value zero print footer in new page
			$YPos=$FormDesign->Data->y;
			include ('includes/PDFGrnHeader.inc');
		} //end if need a new page headed up

		$SQL = "SELECT stockmaster.controlled
			    FROM stockmaster WHERE stockid ='" . $myrow['itemcode'] . "'";
		$CheckControlledResult = DB_query($SQL,'<br />' . _('Could not determine if the item was controlled or not because') . ' ');
		$ControlledRow = DB_fetch_row($CheckControlledResult);

		if ($ControlledRow[0]==1) { /*Then its a controlled item */
			$SQL = "SELECT stockserialmoves.serialno,
					stockserialmoves.moveqty
					FROM stockmoves INNER JOIN stockserialmoves
					ON stockmoves.stkmoveno= stockserialmoves.stockmoveno
					WHERE stockmoves.stockid='" . $myrow['itemcode'] . "'
					AND stockmoves.type =25
					AND stockmoves.transno='" . $GRNNo . "'";
			$GetStockMoveResult = DB_query($SQL,_('Could not retrieve the stock movement reference number which is required in order to retrieve details of the serial items that came in with this GRN'));
			while ($SerialStockMoves = DB_fetch_array($GetStockMoveResult)){
				$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column1->x-20,$Page_Height-$YPos,$FormDesign->Data->Column1->Length,$FormDesign->Data->Column1->FontSize, _('Lot/Serial:'),'right');
				$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column2->x,$Page_Height-$YPos,$FormDesign->Data->Column2->Length,$FormDesign->Data->Column2->FontSize, $SerialStockMoves['serialno']);
				$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column2->x,$Page_Height-$YPos,$FormDesign->Data->Column2->Length,$FormDesign->Data->Column2->FontSize, $SerialStockMoves['moveqty'],'right');
				$YPos += $line_height;

				if ($YPos >= $FormDesign->LineAboveFooter->starty){
					$FooterPrintedInPage= 0;
					$YPos=$FormDesign->Data->y;
					include ('includes/PDFGrnHeader.inc');
				} //end if need a new page headed up
			} //while SerialStockMoves
			$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column2->x,$Page_Height-$YPos,$FormDesign->Data->Column2->Length,$FormDesign->Data->Column2->FontSize, ' ');
			$YPos += $line_height;
			if ($YPos >= $FormDesign->LineAboveFooter->starty){
				$FooterPrintedInPage= 0;
				$YPos=$FormDesign->Data->y;
				include ('includes/PDFGrnHeader.inc');
			} //end if need a new page headed up
		} //controlled item*/

		if($FooterPrintedInPage == 0){
			// $LeftOvers = $pdf->addText($FormDesign->ReceiptDate->x,$Page_Height-$FormDesign->ReceiptDate->y,$FormDesign->ReceiptDate->FontSize, _('Date of Receipt: ') . $DeliveryDate);
			// $LeftOvers = $pdf->addText($FormDesign->SignedFor->x,$Page_Height-$FormDesign->SignedFor->y,$FormDesign->SignedFor->FontSize, _('Signed for ').'______________________');
			
			$FooterPrintedInPage= 1;


		}
	} //end of loop around GRNs to print


	$vat = 0;
	$totalAmount = $total + $vat;

	$pdf->SetFontSize(12);

	$pdf->ln(20);
	$pdf->setX(400);
	$pdf->Cell( 100, $h = 16, "Total: " .$SuppRow['currcode'] , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(520);
	$pdf->Cell( 100, $h = 16, number_format( $total, 2) , $border = "", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );

	$pdf->setX(400);
	$pdf->Cell( 100, $h = 16, "VAT  " .$SuppRow['currcode'] , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(520);
	$pdf->Cell( 46, $h = 16, "" , $border = "B", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );


	$pdf->SetFontSize(14);
	$pdf->setX(400);
	$pdf->Cell( 100, $h = 16, "TOTAL" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(510);
	$pdf->Cell( 60, $h = 16, number_format( $totalAmount, 2) , $border = "", $ln = 1, $align = 'R', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );


	$pdf->SetFontSize(12);
	$pdf->setY('750');
	$pdf->setX(30);
	$pdf->Cell( 100, $h = 16, "Prepared By" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(120);
	$pdf->Cell( 80, $h = 16, "" , $border = "B", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );

	$pdf->setX(400);
	$pdf->Cell( 100, $h = 16, "Verified By" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(480);
	$pdf->Cell( 80, $h = 16, "" , $border = "B", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );

	$pdf->ln(15);

	$pdf->setX(30);
	$pdf->Cell( 100, $h = 16, "Name" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(120);
	$pdf->Cell( 80, $h = 16, "" , $border = "B", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );

	$pdf->setX(400);
	$pdf->Cell( 100, $h = 16, "Designation" , $border = "", $ln = 0, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );
	$pdf->setX(480);
	$pdf->Cell( 80, $h = 16, "" , $border = "B", $ln = 1, $align = 'L', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' );


    $pdf->OutputD($_SESSION['DatabaseName'] . '_GRN_' . $GRNNo . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
} else { //there were not GRNs to print
	$Title = _('GRN Error');
	include('includes/header.php');
	prnMsg(_('There were no GRNs to print'),'warn');
	echo '<br /><a href="'.$RootPath.'/index.php">' .  _('Back to the menu') . '</a>';
	include('includes/footer.php');
}
?>
