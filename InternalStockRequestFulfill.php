<?php

$PageSecurity = 1;

include('includes/session.php');

$Title = _('Fulfill Stock Requests');

include('includes/header.php');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $theme . '/images/inventory.png" title="' . _('Contract') . '" alt="" />' .
 ' ' . _('Fulfill Stock Requests') . '</p>';

if (isset($_POST['UpdateAll'])) {
    $trans_no = array();

    foreach ($_POST as $key => $value) {
		     if(mb_substr($key,0,6)=='status'){
				  $authorized_id=mb_substr($key,6);
				 }
        if (mb_strpos($key, 'Qty')) {			
		    $RequestID = mb_substr($key, 0, mb_strpos($key, 'Qty'));
		    if($authorized_id==$RequestID){
		    $LineID = mb_substr($key, mb_strpos($key, 'Qty') + 3);
            $Quantity = $_POST[$RequestID . 'Qty' . $LineID];
            $StockID = $_POST[$RequestID . 'StockID' . $LineID];
            $Location = $_POST[$RequestID . 'Location' . $LineID];
            $ReceiveLoc = $_POST[$RequestID . 'ReceivedTo' . $LineID];
            $Tag = $_POST[$RequestID . 'Tag' . $LineID];
            $RequestedQuantity = $_POST[$RequestID . 'RequestedQuantity' . $LineID];
            if (isset($_POST[$RequestID . 'Completed' . $LineID])) {
                $Completed = True;
            } else {
                $Completed = False;
            }



            $sql = "SELECT materialcost, labourcost, overheadcost, decimalplaces FROM stockmaster WHERE stockid='" . $StockID . "'";
            $result = DB_query($sql);
            $myrow = DB_fetch_array($result);
            $StandardCost = $myrow['materialcost'] + $myrow['labourcost'] + $myrow['overheadcost'];
            $DecimalPlaces = $myrow['decimalplaces'];

            $Narrative = _('Issue') . ' ' . $Quantity . ' ' . _('of') . ' ' . $StockID . ' ' . 'TO: ' . $ReceiveLoc . ' ' . _('from') . ' ' . $Location;

            $Transid = GetNextTransNo(17);
            $PeriodNo = GetPeriod (Date($_SESSION['DefaultDateFormat']));

            $SQLAdjustmentDate = FormatDateForSQL(Date($_SESSION['DefaultDateFormat']));

             $Result = DB_Txn_Begin();

            $InTransitSQL = "SELECT SUM(shipqty-recqty) as intransit
										FROM loctransfers
										WHERE stockid='" . $StockID . "'
											AND shiploc='" . $Location . "'
											AND shipqty>recqty";

            $InTransitResult = DB_query($InTransitSQL);
            $InTransitRow = DB_fetch_array($InTransitResult);
            $InTransitQuantity = $InTransitRow['intransit'];

            // Need to get the location from quantity will need it later for the stock movement
            $SQL = "SELECT locstock.quantity
					FROM locstock
					WHERE locstock.stockid='" . $StockID . "'
						AND loccode= '" . $Location . "'";
            $Result = DB_query($SQL);
            if (DB_num_rows($Result) == 1) {
                $LocQtyRow = DB_fetch_row($Result);
                $QtyOnHandPrior = $LocQtyRow[0];
            } else {
                // There must actually be some error this should never happen
                $QtyOnHandPrior = 0;
            }

            $QtyOnHand = $QtyOnHandPrior - $InTransitQuantity;

            //echo $StockID.'QOH ='.$QtyOnHand.'qty ='.$Quantity.'<br>';

            if ($_SESSION['ProhibitNegativeStock'] == 0 or ( $_SESSION['ProhibitNegativeStock'] == 1 AND $QtyOnHand >= $Quantity)) {

                //stock moves were here removed
                // Insert the stock movement for the stock leaving the to location
                $SQL = "INSERT INTO stockmoves (stockid,
									type,
									transno,
									loccode,
									trandate,
									userid,
									prd,
									reference,
									qty,
									newqoh)
								VALUES (
									'" . $StockID . "',
									17,
									'" . $Transid . "',
									'" . $Location . "',
									'" . $SQLAdjustmentDate . "',
									'" . $_SESSION['UserID'] . "',
									'" . $PeriodNo . "',
									'" . $Narrative . "',
									'" . -$Quantity . "',
									'" . ($QtyOnHandPrior - $Quantity) . "'
								)";
              

                $ErrMsg =  _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The stock movement record cannot be inserted because');
                $DbgMsg =  _('The following SQL to insert the stock movement record was used');
                $Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);


                /* Get the ID of the StockMove... */
                $StkMoveNo = DB_Last_Insert_ID('stockmoves','stkmoveno');



                if(!isset($Quantity)){
                    $Quantity=0;

                }

                $SQL = "UPDATE stockrequestitems
							SET qtydelivered=qtydelivered+" . $Quantity . "
							WHERE dispatchid='" . $RequestID . "'
								AND dispatchitemsid='" . $LineID . "'";               


                $ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' ._('The location stock record could not be updated because');
                $DbgMsg = _('The following SQL to update the stock record was used');
                $Result = DB_query($SQL, $ErrMsg, $DbgMsg,true);



                  
                $sql_loccode = "SELECT loccode FROM locations WHERE locationname='" . $ReceiveLoc . "'";
                $LoccodeResult = DB_query($sql_loccode);
                $LoccodeRow = DB_Fetch_array($LoccodeResult);
            

                 
                $SQL = "INSERT INTO loctransfers (reference,
								stockid,
								shipqty,
								shipdate,
								shiploc,
								recloc)
						VALUES ('" . $Transid . "',
							'" . $StockID . "',
							'" . round(filter_number_format($Quantity), $DecimalPlaces) . "',
							'" . Date('Y-m-d') . "',
							'" . $Location . "',
							'" . $LoccodeRow['loccode'] . "')";
                $ErrMsg = _('CRITICAL ERROR') . '! ' . _('Unable to enter Location Transfer record for') . $Stockid;
                $resultLocShip = DB_query($SQL,$ErrMsg);
                




                /* Need to get the current receiving location quantity will need it later for the stock movement */
                $SQL = "SELECT locstock.quantity
					FROM locstock
					WHERE locstock.stockid='" . $StockID . "'
					AND loccode= '" . $LoccodeRow['loccode'] . "'";

                $Result = DB_query($SQL, _('Could not retrieve the quantity on hand at the location being transferred to'));
                if (DB_num_rows($Result) == 1) {
                    $LocQtyRow = DB_fetch_array($Result);
                    $QtyOnHandPrior = $LocQtyRow['quantity'];
                } else {
                    // There must actually be some error this should never happen
                    $QtyOnHandPrior = 0;
                }

                // Insert the stock movement for the stock coming into the to location
                $SQL = "INSERT INTO stockmoves (
						stockid,
						type,
						transno,
						loccode,
						trandate,
						prd,
						reference,
						qty,
						newqoh)
					VALUES (
						'" . $StockID . "',
						17,
						'" . $Transid . "',
						'" . $LoccodeRow['loccode'] . "',
						'" . $SQLAdjustmentDate . "',
						'" . $PeriodNo . "',
						'" . _('From') . ' ' . $Location . "',
						'" . round($Quantity, $DecimalPlaces) . "',
						'" . round($QtyOnHandPrior + $Quantity, $DecimalPlaces) . "'
						)";

                $ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The stock movement record for the incoming stock cannot be added because');
                $DbgMsg = _('The following SQL to insert the stock movement record was used');
                $Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

                /* Get the ID of the StockMove... */
                $StkMoveNo = DB_Last_Insert_ID($db, 'stockmoves', 'stkmoveno');


                //Update stock from the sending location
                $SQL = "UPDATE locstock SET quantity = quantity - '" . $Quantity . "'
									WHERE stockid='" . $StockID . "'
										AND loccode='" . $Location . "'";

                $ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The location stock record could not be updated because');
                $DbgMsg = _('The following SQL to update the stock record was used');

                $Result = DB_query($SQL,$ErrMsg, $DbgMsg, true);

                

                //Set receive quantity equal to ship quantity
                $sql = "UPDATE loctransfers SET recqty = recqty + '" . round($Quantity, $DecimalPlaces) . "',
								recdate = '" . date('Y-m-d H:i:s') . "'
							WHERE reference = '" . $Transid . "'
								AND stockid = '" . $StockID . "'";

                $ErrMsg = _('CRITICAL ERROR') . '! ' . _('Unable to update the Location Transfer Record');
                $Result = DB_query($sql,$ErrMsg, $DbgMsg, true);


                //Update stock on the receiving location
                $SQL = "UPDATE locstock SET quantity = quantity + '" . $Quantity . "'
									WHERE stockid='" . $StockID . "'
										AND loccode='" . $LoccodeRow['loccode'] . "'";

                $ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The location stock record could not be updated because');
                $DbgMsg = _('The following SQL to update the stock record was used');

                $Result = DB_query($SQL,$ErrMsg, $DbgMsg, true);
                




                if (($Quantity >= $RequestedQuantity) or $Completed == True) {
                    $SQL = "UPDATE stockrequestitems
							SET completed=1
							WHERE dispatchid='" . $RequestID . "'
								AND dispatchitemsid='" . $LineID . "'";
                    $Result = DB_query($SQL,$ErrMsg, $DbgMsg, true);
                }

                $Result =DB_Txn_Commit();


                $ConfirmationText = _('A stock adjustment for ') . ' ' . $StockID . _(' has been created from location') . ' ' . $Location . ' ' . _(' for a quantity of ') . ' ' . $Quantity;
                prnMsg($ConfirmationText, 'success');


                if ($_SESSION['InventoryManagerEmail'] != '') {
                    $ConfirmationText = $ConfirmationText . ' ' . _('by user') . ' ' . $_SESSION['UserID'] . ' ' . _('at') . ' ' . Date('Y-m-d H:i:s');
                    $EmailSubject = _('Internal Stock Request Fulfillment for') . ' ' . $StockID;
                    if ($_SESSION['SmtpSetting'] == 0) {
                        mail($_SESSION['InventoryManagerEmail'], $EmailSubject, $ConfirmationText);
                    } else {
                        include('includes/htmlMimeMail.php');
                        $mail = new htmlMimeMail();
                        $mail->setSubject($EmailSubject);
                        $mail->setText($ConfirmationText);
                        $result = SendmailBySmtp($mail, array($_SESSION['InventoryManagerEmail']));
                    }
                }
            } else {
                $ConfirmationText = _('A stock issue for') . ' ' . $StockID . ' ' . _('from location') . ' ' . $Location . ' ' . _('for a quantity of') . ' ' . $Quantity . ' ' . _('cannot be created as there is insufficient stock and your system is configured to not allow negative stocks');
                prnMsg($ConfirmationText, 'warn');
            }
            // Check if request can be closed and close if done.
            if (isset($RequestID)) {
                $SQL = "SELECT dispatchid
			FROM stockrequestitems
			WHERE dispatchid='" . $RequestID . "'
			AND completed=0";
                $Result = DB_query($SQL);
                if (DB_num_rows($Result) == 0) {
                    $SQL = "UPDATE stockrequest
				SET closed=1
			WHERE dispatchid='" . $RequestID . "'";
                    $Result = DB_query($SQL);
                }
            }
            //Add Transids to $trans_no array
            array_push($trans_no, $Transid);
		 }
        }
    }
    //Create a string of the transfer ids and store in a table int_transfers
    $ti = implode("|", $trans_no);
    $transfer_id = GetNextTransNo(51);
    




    $sql_tids = "INSERT INTO internalstocktransfers (transfer_id, transfers) "
            . "VALUES('" . $transfer_id . "','" . $ti . "')";



    $ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The internal stock transaction no\'s could not be added because');
    $DbgMsg = _('The following SQL to insert the entries was used');
    $Result_tids = DB_query($sql_tids,$ErrMsg, $DbgMsg, true);

    echo '<p><a href="' . $RootPath . '/PDFInternalStockLocTransfer.php?TransferNo=' . $transfer_id . '">' . _('Print the Transfer Docket') . '</a></p>';

    unset($_POST['UpdateAll']);

}

if (!isset($_POST['Location'])) {
    echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    echo '<table class="selection"><tr>';
    echo '<td>' . _('Choose a location to issue requests from') . '</td>
		<td><select name="Location">';
    $sql = "SELECT loccode, locationname FROM locations";
    $resultStkLocs = DB_query($sql);
    while ($myrow = DB_fetch_array($resultStkLocs)) {
        if (isset($_SESSION['Adjustment']->StockLocation)) {
            if ($myrow['loccode'] == $_SESSION['Adjustment']->StockLocation) {
                echo '<option selected="True" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
            } else {
                echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
            }
        } elseif ($myrow['loccode'] == $_SESSION['UserStockLocation']) {
            echo '<option selected="True" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
            $_POST['StockLocation'] = $myrow['loccode'];
        } else {
            echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
        }
    }
    echo '</select></td></tr>';
    echo '</table><br />';
    echo '<div class="centre"><button type="submit" name="EnterAdjustment">' . _('Show Requests') . '</button></div><br />';
    include('includes/footer.inc');
    exit;
}

/* Retrieve the requisition header information
 */

if (isset($_POST['Location'])) {
    $sql = "SELECT stockrequest.dispatchid,
			locations.locationname AS locfrom,
			internalstockauthusers.locationname AS locto,
			stockrequest.despatchdate,
			stockrequest.narrative,
			www_users.realname,
			www_users.email
		FROM stockrequest
		LEFT JOIN locations
			ON stockrequest.loccode_from=locations.loccode
                LEFT JOIN internalstockauthusers
			ON stockrequest.loccode_to=internalstockauthusers.loccode
		LEFT JOIN www_users
			ON www_users.userid=internalstockauthusers.userid
	WHERE stockrequest.authorised=1
		AND stockrequest.closed=0 AND stockrequest.deleted=0
		AND stockrequest.loccode_from='" . $_POST['Location'] . "' AND internalstockauthusers.userid='".$_SESSION['UserID']."' ";
    $result = DB_query($sql);

    //echo $sql;
    
     //$_SESSION['UserID']


    if (DB_num_rows($result) == 0) {
        prnMsg(_('There are no outstanding authorised requests for this location'), 'info');
        echo '<br />';
        echo '<div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . _('Select another location') . '</a></div>';
        include('includes/footer.inc');
        exit;
    }



    echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    echo '<table class="selection"><tr>';

    /* Create the table for the purchase order header */
    echo '<th>' . _('Request Number') . '</th>';
    echo '<th>' . _('Department') . '</th>';
    echo '<th>' . _('Location Of Stock') . '</th>';
    echo '<th>' . _('Requested Date') . '</th>';
    echo '<th>' . _('Narrative') . '</th>';
    echo '<th></th>';
    echo '<th>' . _('Authorize') . '</th>';
    echo '</tr>';

    while ($myrow = DB_fetch_array($result)) {


        echo '<tr>';
        echo '<td>' . $myrow['dispatchid'] . '</td>';
        echo '<td>' . $myrow['locto'] . '</td>';
        echo '<td>' . $myrow['locfrom'] . '</td>';
        echo '<td>' . ConvertSQLDate($myrow['despatchdate']) . '</td>';
        echo '<td>' . $myrow['narrative'] . '</td>';
        echo '<td></td>';
        echo '<td><input type="checkbox" name="status' . $myrow['dispatchid'] . '" /></td>';
        echo '</tr>';

        //Set the receiving location to $ReceivedTo
        $ReceivedTo = $myrow['locto'];

        $linesql = "SELECT stockrequestitems.dispatchitemsid,
						stockrequestitems.dispatchid,
						stockrequestitems.stockid,
						stockrequestitems.decimalplaces,
						stockrequestitems.uom,
						stockmaster.description,
						stockrequestitems.qtyapproved,
						stockrequestitems.qtydelivered
				FROM stockrequestitems
				LEFT JOIN stockmaster
				ON stockmaster.stockid=stockrequestitems.stockid
			WHERE dispatchid='" . $myrow['dispatchid'] . "' AND deleted=0
				AND completed=0";

             


        $lineresult = DB_query($linesql);



                       

        echo '<tr><td></td><td colspan="5" align="left"><table class="selection" align="left">';
        echo '<th>' . _('Product') . '</th>';
        echo '<th>' . _('Quantity') . '<br />' . _('Requested') . '</th>';
        echo '<th>' . _('Quantity On Hand').'<br> '.$myrow['locfrom']. '</th>';
        echo '<th>' . _('Quantity On Hand').'<br> '.$ReceivedTo. '</th>';
        echo '<th>' . _('Quantity') . '<br />' . _('Delivered') . '</th>';
        echo '<th>' . _('Units') . '</th>';
        echo '<th>' . _('Completed') . '</th>';
        echo '<th>' . _('Tag') . '</th>';
        echo '</tr>';



        $loccodeto="SELECT loccode FROM locations WHERE locationname='".$ReceivedTo."'";
        $loccodetoresult = DB_query($loccodeto);
        $rowloccodeto=DB_fetch_array($loccodetoresult);    



        while ($linerow = DB_fetch_array($lineresult)) {
                //get quantity on hand  
                    $SQL = "SELECT locstock.quantity
                            FROM locstock
                            WHERE locstock.stockid='" . $linerow['stockid'] . "'
                                AND loccode= '" . $_POST['Location'] . "'";
                    $Result = DB_query($SQL, $db);
                    
                    if (DB_num_rows($Result) == 1) {
                        $LocQtyRow = DB_fetch_row($Result);
                        $QtyOnHand = $LocQtyRow[0];
                    } else {
                        
                        $QtyOnHand = 0;
                    } 



                    $SQL = "SELECT locstock.quantity
                            FROM locstock
                            WHERE locstock.stockid='" . $linerow['stockid'] . "'
                                AND loccode= '" . $rowloccodeto['loccode'] . "'";

                    $Result = DB_query($SQL);
                    
                    if (DB_num_rows($Result) == 1) {
                        $LocQtyRow = DB_fetch_row($Result);
                        $QtyOnHand2 = $LocQtyRow[0];
                    } else {
                        
                        $QtyOnHand2 = 0;
                    }  





              




            echo '<tr>';
            echo '<td>' . $linerow['description'] . '</td>';
            echo '<td class="number">' . locale_number_format($linerow['qtyapproved'] - $linerow['qtydelivered'], $linerow['decimalplaces']) . '</td>';
            echo '<td class="number">' . locale_number_format($QtyOnHand, $linerow['decimalplaces']) . '</td>';
            echo '<td class="number">' . locale_number_format($QtyOnHand2, $linerow['decimalplaces']) . '</td>';
            echo '<td class="number">
					<input type="text" class="number" name="' . $linerow['dispatchid'] . 'Qty' . $linerow['dispatchitemsid'] . '" value="' . locale_number_format($linerow['quantity'] - $linerow['qtydelivered'], $linerow['decimalplaces']) . '" />
				</td>';
            echo '<td>' . $linerow['uom'] . '</td>';

            echo '<td><input type="checkbox" name="' . $linerow['dispatchid'] . 'Completed' . $linerow['dispatchitemsid'] . '" /></td>';
            //Select the tag
            echo '<td><select name="' . $linerow['dispatchid'] . 'Tag' . $linerow['dispatchitemsid'] . '">';

            $SQL = "SELECT tagref,
							tagdescription
						FROM tags
						ORDER BY tagref";

            $TagResult = DB_query($SQL);
            echo '<option value=0>0 - None</option>';
            while ($mytagrow = DB_fetch_array($TagResult)) {
                if (isset($_SESSION['Adjustment']->tag) and $_SESSION['Adjustment']->tag == $mytagrow['tagref']) {
                    echo '<option selected="True" value="' . $mytagrow['tagref'] . '">' . $mytagrow['tagref'] . ' - ' . $myrow['tagdescription'] . '</option>';
                } else {
                    echo '<option value="' . $mytagrow['tagref'] . '">' . $mytagrow['tagref'] . ' - ' . $mytagrow['tagdescription'] . '</option>';
                }
            }
            echo '</select></td>';
// End select tag
            echo '</tr>';
            echo '<input type="hidden" class="number" name="' . $linerow['dispatchid'] . 'StockID' . $linerow['dispatchitemsid'] . '" value="' . $linerow['stockid'] . '" />';
            echo '<input type="hidden" class="number" name="' . $linerow['dispatchid'] . 'Location' . $linerow['dispatchitemsid'] . '" value="' . $_POST['Location'] . '" />';
            echo '<input type="hidden" class="number" name="' . $linerow['dispatchid'] . 'RequestedQuantity' . $linerow['dispatchitemsid'] . '" value="' . locale_number_format($linerow['quantity'] - $linerow['qtydelivered'], $linerow['decimalplaces']) . '" />';
            echo '<input type="hidden" class="number" name="' . $linerow['dispatchid'] . 'ReceivedTo' . $linerow['dispatchitemsid'] . '" value="' . $ReceivedTo . '" />';
        } // end while order line detail
        echo '</table></td></tr>';
    } //end while header loop
    echo '</table>';
    echo '<br /><div class="centre"><button type="submit" name="UpdateAll">' . _('Update') . '</button></form>';
}

include('includes/footer.php');
?>
