<?php
include ('includes/session.php');
$Title = _('Stock On Hand By Date');
include ('includes/header.php');

echo '<p class="page_title_text" >
		<img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/inventory.png" title="' . _('Inventory') . '" alt="" /><b>' . $Title . '</b>
	</p>';

echo '<form action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

$SQL = "SELECT categoryid, categorydescription FROM stockcategory";
$ResultStkLocs = DB_query($SQL);

echo '<table>
	<tr>
		<td>' . _('For Stock Category') . ':</td>
		<td>
			<select required="required" name="StockCategory">
				<option value="All">' . _('All') . '</option>';

while ($MyRow = DB_fetch_array($ResultStkLocs)) {
	if (isset($_POST['StockCategory']) and $_POST['StockCategory'] != 'All') {
		if ($MyRow['categoryid'] == $_POST['StockCategory']) {
			echo '<option selected="selected" value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
		} else {
			echo '<option value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
		}
	} else {
		echo '<option value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
	}
}
echo '</select></td>';

$SQL = "SELECT locationname,
				locations.loccode
			FROM locations
			INNER JOIN locationusers
				ON locationusers.loccode=locations.loccode
				AND locationusers.userid='" . $_SESSION['UserID'] . "'
				AND locationusers.canview=1";

$ResultStkLocs = DB_query($SQL);

echo '<td>' . _('For Stock Location') . ':</td>
	<td><select required="required" name="StockLocation[]" multiple="multiple"> ';
	// echo '<option value="All">' . _('All Locations') . '</option>';
while ($MyRow = DB_fetch_array($ResultStkLocs)) {
	if (isset($_POST['StockLocation']) and $_POST['StockLocation'] != 'All') {
		if ($MyRow['loccode'] == $_POST['StockLocation']) {
			echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
		} else {
			echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
		}
	} elseif ($MyRow['loccode'] == $_SESSION['UserStockLocation']) {
		echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
		$_POST['StockLocation'] = $MyRow['loccode'];
	} else {
		echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
}
echo '</select></td>';


if (!isset($_POST['OnHandDate'])) {
	$_POST['OnHandDate'] = Date($_SESSION['DefaultDateFormat'], time());
}


echo '<td>' . _('Start Date') . ':</td>
	<td>

	<input type="text" class="date" name="StartDate" size="12" maxlength="10" value="' . $_POST['StartDate'] . '" />

	</td>';

echo '<td>' . _('On-Hand On Date') . ':</td>
	<td><input type="text" class="date" name="OnHandDate" size="12" required="required" maxlength="10" value="' . $_POST['OnHandDate'] . '" /></td>';
	
if (isset($_POST['ShowZeroStocks'])) {
	$Checked = 'checked="checked"';
} else {
	$Checked = '';
}

echo '<td>
		<td>', ('Include zero stocks'), '</td>
		<td><input type="checkbox" name="ShowZeroStocks" value="" ', $Checked, '  /></td>
	</td>
</tr>';

echo '<tr>
		<td colspan="8">
		<div class="centre">
		<input type="submit" name="ShowStatus" value="' . _('Show Stock Status') . '" />
		</div></td>
	</tr>
	</table>
	</form>';

$TotalQuantity = 0;
$TotalPrice = 0;
$stockItems = array();

$StockLocations = $_POST['StockLocation'];

$locations = "";
foreach ($StockLocations as $StockLocation) {
	$locations .= "'$StockLocation',";
}
$locations = rtrim($locations,',');

if (isset($_POST['ShowStatus']) and (is_date($_POST['OnHandDate']) || is_date($_POST['StartDate']))) {


if ($_POST['StockCategory']=='All') {
		$sql = "SELECT stockid,
				description,
				decimalplaces
			FROM stockmaster
			WHERE (mbflag='M' OR mbflag='B')";
	} else {
		$sql = "SELECT stockid,
				description,
				decimalplaces
			FROM stockmaster
			WHERE categoryid = '" . $_POST['StockCategory'] . "'
			AND (mbflag='M' OR mbflag='B')";
	}

	$ErrMsg = _('The stock items in the category selected cannot be retrieved because');
	$DbgMsg = _('The SQL that failed was');

	$StockResult = DB_query($sql, $db, $ErrMsg, $DbgMsg);

	$SQLOnHandDate = FormatDateForSQL($_POST['OnHandDate']);

	echo '<br /><table cellpadding="5" cellspacing="1" class="selection">';

	$tableheader = '<tr>
				<th>' . _('Item Code') . '</th>
				<th>' . _('Description') . '</th>
				<th>' . _('Quantity On Hand') . '</th>
				<th>' . _('Standard Cost') . '</th>
				<th>' . _('Price') . '</th>
				</tr>';
	echo $tableheader;

	while ($myrows=DB_fetch_array($StockResult)) {

		$sql = "SELECT stockid,
				newqoh * standardcost as price,
				newqoh,
				standardcost
				FROM stockmoves
				WHERE stockmoves.trandate <= '". $SQLOnHandDate . "'
				AND stockid = '" . $myrows['stockid'] . "'
				AND loccode IN ($locations)
				ORDER BY stkmoveno DESC LIMIT 1";
		$ErrMsg =  _('The stock held as at') . ' ' . $_POST['OnHandDate'] . ' ' . _('could not be retrieved because');

		$LocStockResult = DB_query($sql, $db, $ErrMsg);

		$NumRows = DB_num_rows($LocStockResult, $db);

		$j = 1;
		$k=0; //row colour counter

		while ($LocQtyRow=DB_fetch_array($LocStockResult)) {
			if ($k==1){
				echo '<tr class="OddTableRows">';
				$k=0;
			} else {
				echo '<tr class="EvenTableRows">';
				$k=1;
			}

			if($NumRows == 0){
				printf('<td><a target="_blank" href="StockStatus.php?%s">%s</td>
					<td>%s</td>
					<td class="number">%s</td>
					<td class="number">%s</td>
					<td class="number">%s</td>
					',
					'StockID=' . mb_strtoupper($myrows['stockid']),
					mb_strtoupper($myrows['stockid']),
					$myrows['description'],
					0,
					0,
					0);
			} else {

				if ($LocQtyRow['newqoh'] > 0) {
					printf('<td><a target="_blank" href="StockStatus.php?%s">%s</td>
						<td>%s</td>
						<td class="number">%s</td>
						<td class="number">%s</td>
						<td class="number">%s</td>
						',
						'StockID=' . mb_strtoupper($myrows['stockid']),
						mb_strtoupper($myrows['stockid']),
						$myrows['description'],
						locale_number_format($LocQtyRow['newqoh'],$myrows['decimalplaces']),
						locale_number_format($LocQtyRow['standardcost'],$myrows['decimalplaces']),
						locale_number_format($LocQtyRow['price'],$myrows['decimalplaces']));

					$TotalQuantity += $LocQtyRow['newqoh'];
					$TotalPrice += $LocQtyRow['price'];
				}
				
			}
			$j++;
			if ($j == 12){
				$j=1;
				echo $tableheader;
			}
		//end of page full new headings if
		}

	}//end of while loop
	echo '<tr>
	<td colspan="3" style="text-align: right;">' . _('Total Quantity') . ': ' . locale_number_format($TotalQuantity,2) . '</td>
	<td></td>
	<td style="text-align: right;">' . _('Total Price') . ': ' . locale_number_format($TotalPrice, 2) . '</td>
	</tr></table>';


}

include ('includes/footer.php');
?>