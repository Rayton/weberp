<?php


include('includes/DefineStockAdjustment.php');
include('includes/DefineSerialItems.php');
include('includes/session.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/header.php');
$messages = [];

$rows = $_POST['RowCount'];

for ($i=1; $i <= $rows; $i++) {

	$stockID = $_POST['StockCode_'.$i];
	$stockDescription = $_POST['StockDescription_'.$i];
	$stockLocation = $_POST['StockLocation'];
	$stockQuantity = $_POST['StockQuantity_'.$i];
	$stockTag = $_POST['StockTag_'.$i];

	$stockComments = $_POST['stockComments'];

	if (empty($_GET['identifier'])) {
		/*unique session identifier to ensure that there is no conflict with other adjustment sessions on the same machine  */
		$identifier=date('U');
	} else {
		$identifier=$_GET['identifier'];
	}

	if (isset($_GET['NewAdjustment'])){
		unset($_SESSION['Adjustment' . $identifier]);
		$_SESSION['Adjustment' . $identifier] = new StockAdjustment();
	}

	if (!isset($_SESSION['Adjustment' . $identifier])){
		$_SESSION['Adjustment' . $identifier] = new StockAdjustment();
	}

	$NewAdjustment = false;

	if (isset($_GET['StockID'])){
		$NewAdjustment = true;
		$StockID = trim(mb_strtoupper($_GET['StockID']));
	} elseif (isset($stockID)){
		if($stockID != $_SESSION['Adjustment' . $identifier]->StockID){
			$NewAdjustment = true;
			$StockID = trim(mb_strtoupper($stockID));
		}
	}

	if ($NewAdjustment==true){

		$_SESSION['Adjustment' . $identifier]->StockID = trim(mb_strtoupper($StockID));
		$result = DB_query("SELECT description,
								controlled,
								serialised,
								decimalplaces,
								perishable,
								materialcost+labourcost+overheadcost AS totalcost,
								units
							FROM stockmaster
							WHERE stockid='" . $_SESSION['Adjustment' . $identifier]->StockID . "'");
		$myrow = DB_fetch_array($result);
		$_SESSION['Adjustment' . $identifier]->ItemDescription = $myrow['description'];
		$_SESSION['Adjustment' . $identifier]->Controlled = $myrow['controlled'];
		$_SESSION['Adjustment' . $identifier]->Serialised = $myrow['serialised'];
		$_SESSION['Adjustment' . $identifier]->DecimalPlaces = $myrow['decimalplaces'];
		$_SESSION['Adjustment' . $identifier]->SerialItems = array();
		if (!isset($_SESSION['Adjustment' . $identifier]->Quantity) OR !is_numeric($_SESSION['Adjustment' . $identifier]->Quantity)){
			$_SESSION['Adjustment' . $identifier]->Quantity=0;
		}

		$_SESSION['Adjustment' . $identifier]->PartUnit = $myrow['units'];
		$_SESSION['Adjustment' . $identifier]->StandardCost = $myrow['totalcost'];
		$DecimalPlaces = $myrow['decimalplaces'];
		DB_free_result($result);


	}

	//end if it's a new adjustment
	if (isset($stockTag)){
		$_SESSION['Adjustment' . $identifier]->tag = $stockTag;
	}
	if (isset($stockComments)){
		$_SESSION['Adjustment' . $identifier]->Narrative = $stockComments;
	}

	$sql = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1";
	$resultStkLocs = DB_query($sql);
	$LocationList=array();
	while ($myrow=DB_fetch_array($resultStkLocs)){
		$LocationList[$myrow['loccode']]=$myrow['locationname'];
	}

	if (isset($stockLocation)){
		if($_SESSION['Adjustment' . $identifier]->StockLocation != $stockLocation){/* User has changed the stock location, so the serial no must be validated again */
			$_SESSION['Adjustment' . $identifier]->SerialItems = array();
		}
		$_SESSION['Adjustment' . $identifier]->StockLocation = $stockLocation;
	}else{
		if(empty($_SESSION['Adjustment' . $identifier]->StockLocation)){
			if(empty($_SESSION['UserStockLocation'])){
				$_SESSION['Adjustment' . $identifier]->StockLocation=key(reset($LocationList));
			}else{
				$_SESSION['Adjustment' . $identifier]->StockLocation=$_SESSION['UserStockLocation'];
			}
		}
	}


	if (isset($stockQuantity)){
		if ($stockQuantity =='' OR !is_numeric(filter_number_format($stockQuantity))){
			$stockQuantity =0;
		}
	} else {
		$stockQuantity=0;
	}


	if($stockQuantity != 0){//To prevent from serilised quantity changing to zero
		$_SESSION['Adjustment' . $identifier]->Quantity = 0 - filter_number_format($stockQuantity);
		if(count($_SESSION['Adjustment' . $identifier]->SerialItems) == 0 AND $_SESSION['Adjustment' . $identifier]->Controlled == 1 ){/* There is no quantity available for controlled items */
			$_SESSION['Adjustment' . $identifier]->Quantity = 0;
		}
	}
	if(isset($_GET['OldIdentifier'])){
		$_SESSION['Adjustment'.$identifier]->StockLocation=$_SESSION['Adjustment'.$_GET['OldIdentifier']]->StockLocation;
	}


	if (isset($_POST['EnterAdjustment']) AND $_POST['EnterAdjustment']!= ''){

		$InputError = false; /*Start by hoping for the best */
		$result = DB_query("SELECT * FROM stockmaster WHERE stockid='" . $_SESSION['Adjustment' . $identifier]->StockID . "'");
		$myrow = DB_fetch_row($result);
		if (DB_num_rows($result)==0) {
			$message = array('name' => 'The entered item code does not exist', "type" => 'error');
			array_push($messages, $message);
			$InputError = true;
		} elseif (!is_numeric($_SESSION['Adjustment' . $identifier]->Quantity)){
			$message = array('name' => 'The quantity entered must be numeric', "type" => 'error');
			array_push($messages, $message);
			$InputError = true;
		} elseif(strlen(substr(strrchr($_SESSION['Adjustment'.$identifier]->Quantity, "."), 1))>$_SESSION['Adjustment' . $identifier]->DecimalPlaces){
			prnMsg(_('The decimal places input is more than the decimals of this item defined,the defined decimal places is ').' '.$_SESSION['Adjustment' . $identifier]->DecimalPlaces.' '._('and the input decimal places is ').' '.strlen(substr(strrchr($_SESSION['Adjustment'.$identifier]->Quantity, "."), 1)),'error');
			$InputError = true;
		} elseif ($_SESSION['Adjustment' . $identifier]->Quantity==0){
			$message = array('name' => 'The quantity entered cannot be zero There would be no adjustment to make', "type" => 'error');
			array_push($messages, $message);
			$InputError = true;
		} elseif ($_SESSION['Adjustment' . $identifier]->Controlled==1 AND count($_SESSION['Adjustment' . $identifier]->SerialItems)==0) {
			$message = array('name' => 'The item entered is a controlled item that requires the detail of the serial numbers or batch references to be adjusted to be entered', "type" => 'error');
			array_push($messages, $message);
			$InputError = true;
		}

		if ($_SESSION['ProhibitNegativeStock']==1){
			$SQL = "SELECT quantity FROM locstock
					WHERE stockid='" . $_SESSION['Adjustment' . $identifier]->StockID . "'
					AND loccode='" . $_SESSION['Adjustment' . $identifier]->StockLocation . "'";
			$CheckNegResult=DB_query($SQL);
			$CheckNegRow = DB_fetch_array($CheckNegResult);
			if ($CheckNegRow['quantity']+$_SESSION['Adjustment' . $identifier]->Quantity <0){
				$InputError=true;
			$message = array('name' => 'Stock '. $stockID.' '.$stockDescription  . ' Not Adjustmented. The system parameters are set to prohibit negative stocks. Processing this stock adjustment would result in negative stock at this location. This adjustment will not be processed', "type" => 'error');
			array_push($messages, $message);
			}
		}

		if (!$InputError) {

	/*All inputs must be sensible so make the stock movement records and update the locations stocks */

			$AdjustmentNumber = GetNextTransNo(17);
			$PeriodNo = GetPeriod (Date($_SESSION['DefaultDateFormat']));
			$SQLAdjustmentDate = FormatDateForSQL(Date($_SESSION['DefaultDateFormat']));

			$Result = DB_Txn_Begin();

			// Need to get the current location quantity will need it later for the stock movement
			$SQL="SELECT locstock.quantity
				FROM locstock
				WHERE locstock.stockid='" . $_SESSION['Adjustment' . $identifier]->StockID . "'
				AND loccode= '" . $_SESSION['Adjustment' . $identifier]->StockLocation . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result)==1){
				$LocQtyRow = DB_fetch_row($Result);
				$QtyOnHandPrior = $LocQtyRow[0];
			} else {
				// There must actually be some error this should never happen
				$QtyOnHandPrior = 0;
			}

			// echo 'is there problem is quantity on hand prior '.$QtyOnHandPrior; 
			$SQL = "INSERT INTO stockmoves (stockid,
											type,
											transno,
											loccode,
											trandate,
											userid,
											prd,
											reference,
											qty,
											newqoh,
											standardcost,
											narrative)
										VALUES ('" . $_SESSION['Adjustment' . $identifier]->StockID . "',
											17,
											'" . $AdjustmentNumber . "',
											'" . $_SESSION['Adjustment' . $identifier]->StockLocation . "',
											'" . $SQLAdjustmentDate . "',
											'" . $_SESSION['UserID'] . "',
											'" . $PeriodNo . "',
											'" . $_SESSION['Adjustment' . $identifier]->Narrative ."',
											'" . $_SESSION['Adjustment' . $identifier]->Quantity . "',
											'" . ($QtyOnHandPrior + $_SESSION['Adjustment' . $identifier]->Quantity) . "',
											'" . $_SESSION['Adjustment' . $identifier]->StandardCost . "',
											'')";

			$ErrMsg =  _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The stock movement record cannot be inserted because');
			$DbgMsg =  _('The following SQL to insert the stock movement record was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

	/*Get the ID of the StockMove... */
			$StkMoveNo = DB_Last_Insert_ID('stockmoves','stkmoveno');

	/*Insert the StockSerialMovements and update the StockSerialItems  for controlled items*/

			if ($_SESSION['Adjustment' . $identifier]->Controlled ==1){
				foreach($_SESSION['Adjustment' . $identifier]->SerialItems as $Item){
				/*We need to add or update the StockSerialItem record and
				The StockSerialMoves as well */

					/*First need to check if the serial items already exists or not */
					$SQL = "SELECT COUNT(*)
							FROM stockserialitems
							WHERE stockid='" . $_SESSION['Adjustment' . $identifier]->StockID . "'
							AND loccode='" . $_SESSION['Adjustment' . $identifier]->StockLocation . "'
							AND serialno='" . $Item->BundleRef . "'";
					$ErrMsg = _('Unable to determine if the serial item exists');
					$Result = DB_query($SQL,$ErrMsg);
					$SerialItemExistsRow = DB_fetch_row($Result);

					if ($SerialItemExistsRow[0]==1){

						$SQL = "UPDATE stockserialitems SET quantity= quantity + " . $Item->BundleQty . "
								WHERE stockid='" . $_SESSION['Adjustment' . $identifier]->StockID . "'
								AND loccode='" . $_SESSION['Adjustment' . $identifier]->StockLocation . "'
								AND serialno='" . $Item->BundleRef . "'";

						$ErrMsg =  _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The serial stock item record could not be updated because');
						$DbgMsg =  _('The following SQL to update the serial stock item record was used');
						$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
					} else {
						/*Need to insert a new serial item record */
						$SQL = "INSERT INTO stockserialitems (stockid,
															loccode,
															serialno,
															qualitytext,
															quantity,
															expirationdate)
												VALUES ('" . $_SESSION['Adjustment' . $identifier]->StockID . "',
												'" . $_SESSION['Adjustment' . $identifier]->StockLocation . "',
												'" . $Item->BundleRef . "',
												'',
												'" . $Item->BundleQty . "',
												'" . FormatDateForSQL($Item->ExpiryDate) ."')";

						$ErrMsg =  _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The serial stock item record could not be updated because');
						$DbgMsg =  _('The following SQL to update the serial stock item record was used');
						$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
					}


					/* now insert the serial stock movement */

					$SQL = "INSERT INTO stockserialmoves (stockmoveno,
														stockid,
														serialno,
														moveqty)
											VALUES ('" . $StkMoveNo . "',
												'" . $_SESSION['Adjustment' . $identifier]->StockID . "',
												'" . $Item->BundleRef . "',
												'" . $Item->BundleQty . "')";
					$ErrMsg =  _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The serial stock movement record could not be inserted because');
					$DbgMsg =  _('The following SQL to insert the serial stock movement records was used');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

				}/* foreach controlled item in the serialitems array */
			} /*end if the adjustment item is a controlled item */



			$SQL = "UPDATE locstock SET quantity = quantity + " . floatval($_SESSION['Adjustment' . $identifier]->Quantity) . "
					WHERE stockid='" . $_SESSION['Adjustment' . $identifier]->StockID . "'
					AND loccode='" . $_SESSION['Adjustment' . $identifier]->StockLocation . "'";

			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' ._('The location stock record could not be updated because');
			$DbgMsg = _('The following SQL to update the stock record was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

			if ($_SESSION['CompanyRecord']['gllink_stock']==1 AND $_SESSION['Adjustment' . $identifier]->StandardCost > 0){

				$StockGLCodes = GetStockGLCode($_SESSION['Adjustment' . $identifier]->StockID);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											amount,
											narrative,
											tag)
									VALUES (17,
										'" .$AdjustmentNumber . "',
										'" . $SQLAdjustmentDate . "',
										'" . $PeriodNo . "',
										'" .  $StockGLCodes['adjglact'] . "',
										'" . round($_SESSION['Adjustment' . $identifier]->StandardCost * -($_SESSION['Adjustment' . $identifier]->Quantity), $_SESSION['CompanyRecord']['decimalplaces']) . "',
										'" . $_SESSION['Adjustment' . $identifier]->StockID . " x " . $_SESSION['Adjustment' . $identifier]->Quantity . " @ " .
											$_SESSION['Adjustment' . $identifier]->StandardCost . " " . $_SESSION['Adjustment' . $identifier]->Narrative . "',
										'" . $_SESSION['Adjustment' . $identifier]->tag . "')";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The general ledger transaction entries could not be added because');
				$DbgMsg = _('The following SQL to insert the GL entries was used');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											amount,
											narrative,
											tag)
									VALUES (17,
										'" .$AdjustmentNumber . "',
										'" . $SQLAdjustmentDate . "',
										'" . $PeriodNo . "',
										'" .  $StockGLCodes['stockact'] . "',
										'" . round($_SESSION['Adjustment' . $identifier]->StandardCost * $_SESSION['Adjustment' . $identifier]->Quantity,$_SESSION['CompanyRecord']['decimalplaces']) . "',
										'" . $_SESSION['Adjustment' . $identifier]->StockID . ' x ' . $_SESSION['Adjustment' . $identifier]->Quantity . ' @ ' . $_SESSION['Adjustment' . $identifier]->StandardCost . ' ' . $_SESSION['Adjustment' . $identifier]->Narrative . "',
										'" . $_SESSION['Adjustment' . $identifier]->tag . "'
										)";

				$Errmsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The general ledger transaction entries could not be added because');
				$DbgMsg = _('The following SQL to insert the GL entries was used');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg,true);
			}

			EnsureGLEntriesBalance(17, $AdjustmentNumber);

			$Result = DB_Txn_Commit();
			$AdjustReason = $_SESSION['Adjustment' . $identifier]->Narrative?  _('Narrative') . ' ' . $_SESSION['Adjustment' . $identifier]->Narrative:'';
			$ConfirmationText = _('A stock adjustment for'). ' ' . $_SESSION['Adjustment' . $identifier]->StockID . ' -  ' . $_SESSION['Adjustment' . $identifier]->ItemDescription . ' '._('has been created from location').' ' . $_SESSION['Adjustment' . $identifier]->StockLocation .' '. _('for a quantity of') . ' ' . locale_number_format($_SESSION['Adjustment' . $identifier]->Quantity,$_SESSION['Adjustment' . $identifier]->DecimalPlaces) . ' ' . $AdjustReason;
			$message = array('name' => $ConfirmationText, "type" => 'success');
			array_push($messages, $message);

			if ($_SESSION['InventoryManagerEmail']!=''){
				$ConfirmationText = $ConfirmationText . ' ' . _('by user') . ' ' . $_SESSION['UserID'] . ' ' . _('at') . ' ' . Date('Y-m-d H:i:s');
				$EmailSubject = _('Stock adjustment for'). ' ' . $_SESSION['Adjustment' . $identifier]->StockID;
				if($_SESSION['SmtpSetting']==0){
				      mail($_SESSION['InventoryManagerEmail'],$EmailSubject,$ConfirmationText);
				}else{
					include('includes/htmlMimeMail.php');
					$mail = new htmlMimeMail();
					$mail->setSubject($EmailSubject);
					$mail->setText($ConfirmationText);
					$result = SendmailBySmtp($mail,array($_SESSION['InventoryManagerEmail']));
				}

			}
			$StockID = $_SESSION['Adjustment' . $identifier]->StockID;
			unset ($_SESSION['Adjustment' . $identifier]);
		} /* end if there was no input error */

	}
		
}

$_SESSION['post_issues_messages'] = $messages;

header('Location: ' . $_SERVER['HTTP_REFERER']);


function printMessage($Msg, $Type = 'info', $Prefix = '') {

	?>

	<style>

	.alert {
	    position: relative;
	    padding: .75rem 1.25rem;
	    margin-bottom: 1rem;
	    border: 1px solid transparent;
	    border-radius: .25rem;
	    margin: 0 auto;
	    width: 79%;
	}

	.alert-success {
	    color: #155724;
	    background-color: #d4edda;
	    border-color: #c3e6cb;
	}

	.alert-error {
	    color: #721c24;
	    background-color: #f8d7da;
	    border-color: #f5c6cb;
	}

	.alert-info {
	    color: #0c5460;
	    background-color: #d1ecf1;
	    border-color: #bee5eb;
	}

	.alert-warn {
	    color: #856404;
	    background-color: #fff3cd;
	    border-color: #ffeeba;
	}

	</style>
	<div class="alert alert-<?php echo $Type  ?>">
		
	<?php
	echo $Msg.'<br>';
	echo $Type[0][1].'<br>';

	?>
	</div>

	<?php



}


