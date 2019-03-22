<?php

include('includes/session.php');

$Title = _('Stock Issues Entry Sheet');

include('includes/header.php');


if (!$_SESSION['PageSecurityArray']['getStockItem.php']) {
	$_SESSION['PageSecurityArray']['getStockItem.php'] = 2;
	$_SESSION['PageSecurityArray']['getStockItemDescription.php'] = 2;
	$_SESSION['PageSecurityArray']['getStockItemCode.php'] = 2;
	$_SESSION['PageSecurityArray']['PostStockItems.php'] = 2;
}

if (@$_SESSION['post_issues_messages']) {

	$messages = $_SESSION['post_issues_messages'];

	foreach ($messages as $message) {
		echo "<br>";
		printMessage( $message['name'] . ' ' . $id, $message['type']);
	}
	
	$_SESSION['post_issues_messages'] = "";
}

   
echo '<link rel="stylesheet" href="css/auto-complete.css">';

$sql = "SELECT locations.loccode, locationname FROM locations
		INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1";
$result = DB_query($sql);

$locations = array();

while ($myrow=DB_fetch_array($result)){

	$location = array('code' => $myrow['loccode'], 'name' => $myrow['locationname']);
	array_push($locations, $location);
}

$SQL = "SELECT tagref, tagdescription FROM tags ORDER BY tagref";

$result=DB_query($SQL);
$tags = array ();

while ($myrow=DB_fetch_array($result)){
	$tag = array('tagref' => $myrow['tagref'], 'tagdescription' => $myrow['tagdescription']);
	array_push($tags,  $tag);
}

echo '<form name="EnterCountsForm" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' .
	_('Inventory Issues') . '" alt="" />' . ' ' . $Title . '</p>';

if (!isset($_POST['Action']) AND !isset($_GET['Action'])) {
	$_GET['Action'] = 'Enter';
}
if (isset($_POST['Action'])) {
	$_GET['Action'] = $_POST['Action'];
}

if ($_GET['Action']!='View' AND $_GET['Action']!='Enter'){
	$_GET['Action'] = 'Enter';
}

echo '<table class="selection"><tr>';
if ($_GET['Action']=='View'){
	
} else {
	// echo '<td>' . _('Entering Counts')  . '</td><td> <a href="' . $RootPath . '/StockCounts.php?&amp;Action=View">' . _('View Entered Counts') . '</a></td>';
}
echo '</tr></table><br />';




$numRows = @($_POST['number_of_rows'])?$_POST['number_of_rows']:4;


if ($_GET['Action'] == 'Enter'){

	echo '<table cellpadding="2" class="selection">';
	echo '<tr>
			<th colspan="3">' ._('Number of rows') . '
			<input type="int" name="number_of_rows" placeholder="Enter Number of Rows" value="'.$_POST['number_of_rows'].'">
			<input type="submit" name="numRows" value="' . _('Submit Rows') . '" />';


echo "</form>";
echo '<form name="stockIssueForm" action="PostStockItems.php" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo'<tr>
			<th colspan="4">' . _('Entering Stock Issues') . ': ' . $StkCatRow[0] . '</th>
		</tr>

		<tr>
			<td class="centre">Stock Location</td>
			<td colspan="4">
				<select name="StockLocation" required id="">';
				echo "<option value=''>Select Location</option>";

				foreach ($locations as $location) {
					$locationcode = $location['code'];
					$locationdescription = $location['name'];
					echo "<option value='".$locationcode."'>".$locationdescription."</option>";
				}
				
				echo '</select>
				</td>
		</tr>

		<tr>
			<th>' . _('Stock Code') . '</th>
			<th>' . _('Partial Description') . '</th>
			<th>' . _('Adjustment Quantity') . '</th>
			<th>' . _('Select Tag') . '</th>
		</tr>

		';

	for ($RowCount=1;$RowCount<=$numRows;$RowCount++){

		echo '<tr>
				<td><input type="text" id="stockcode'.$RowCount .'" name="StockCode_' . $RowCount . '" maxlength="20" size="20" /></td>
				<td><input type="text" required id="stockdescription'.$RowCount .'" name="StockDescription_' . $RowCount . '" maxlength="50" size="50" /></td>
				
				<td><input type="text" required name="StockQuantity_' . $RowCount . '" maxlength="20" size="20" /></td>
				<td>
				<select name="StockTag_' . $RowCount . '" id=""><option value="0">0 - ' . _('None') . '</option>';
				foreach ($tags as $tag) {
					$tagref = $tag['tagref'];
					$tagdescription = $tag['tagdescription'];
					echo "<option value='".$tagref."'>".$tagdescription."</option>";
				}
				echo '</select>

				</td>
			</tr>';
	}

	echo '</table>
			<br />

			<div class="centre">
				Comments:
				<input type="text"  size="50"  name="stockComments" value="" />
			</div>
			<br>
			<div class="centre">
				<input type="hidden" name="RowCount" value="' .$numRows . '" />
				<input type="submit" name="EnterAdjustment" value="' . _('Enter Above Issues') . '" />
			</div>';
//END OF action=ENTER
} elseif ($_GET['Action']=='View'){

	if (isset($_POST['DEL']) AND is_array($_POST['DEL']) ){
		foreach ($_POST['DEL'] as $id=>$val){
			if ($val == 'on'){
				$sql = "DELETE FROM stockcounts WHERE id='".$id."'";
				$ErrMsg = _('Failed to delete StockCount ID #').' '.$i;
				$EnterResult = DB_query($sql,$ErrMsg);
				prnMsg( _('Deleted Id #') . ' ' . $id, 'success');
			}
		}
	}

	//START OF action=VIEW
	$SQL = "select stockcounts.*,
					canupd from stockcounts
					INNER JOIN locationusers ON locationusers.loccode=stockcounts.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$result = DB_query($SQL);
	echo '<input type="hidden" name="Action" value="View" />';
	echo '<table cellpadding="2" class="selection">';
	echo '<tr>
			<th>' . _('Stock Code') . '</th>
			<th>' . _('Location') . '</th>
			<th>' . _('Qty Counted') . '</th>
			<th>' . _('Reference') . '</th>
			<th>' . _('Delete?') . '</th></tr>';
	while ($myrow=DB_fetch_array($result)){
		echo '<tr>
			<td>'.$myrow['stockid'].'</td>
			<td>'.$myrow['loccode'].'</td>
			<td>'.$myrow['qtycounted'].'</td>
			<td>'.$myrow['reference'].'</td>
			<td>';
		if ($myrow['canupd']==1) {
			echo '<input type="checkbox" name="DEL[' . $myrow['id'] . ']" maxlength="20" size="20" />';

		}
		echo '</td></tr>';

	}
	echo '</table><br /><div class="centre"><input type="submit" name="SubmitChanges" value="' . _('Save Changes') . '" /></div>';

//END OF action=VIEW
}

echo '</div>
      </form>';

?>
<script src="javascripts/jquery-3.3.1.min.js"></script>
<script src="javascripts/auto-complete.min.js"></script>
<?php
include('includes/footer.php');
?>

<script>
	<?php 
	for ($i=1; $i <= $numRows ; $i++) { 
	?>
	var ID = <?php echo $i; ?>;

	new autoComplete({
    selector: '#stockcode<?php echo $i ?>',
    minChars: 2,

    source: function(term, response){
        try { xhr.abort(); } catch(e){}
        xhr = $.getJSON('getStockItem.php?type=code', { q: term }, function(data){ response(data); });
    },
    onSelect(event, term, item) {
    	getItemDescription(term, <?php echo $i ?>)
    }
	});

	function getItemDescription(term, id){
		$.getJSON('getStockItemDescription.php?name='+term, function(response){
			$("#stockdescription"+id).val(response)
		} );
	}


	new autoComplete({
    selector: '#stockdescription<?php echo $i ?>',
    minChars: 2,

    source: function(term, response){
        try { xhr.abort(); } catch(e){}
        xhr = $.getJSON('getStockItem.php?type=description', { q: term }, function(data){ response(data); });
    },
    onSelect(event, term, item) {
    	getItemCode(term, <?php echo $i ?>)
    }
	});

	function getItemCode(term, id){
		$.getJSON('getStockItemCode.php?name='+term, function(response){
			$("#stockcode"+id).val(response)
		} );
	}


	<?php
	}
	 ?>
</script>

<?php 



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
	echo $Msg;
	echo $Type[0][1].'<br>';

	?>
	</div>

	<?php



}


 ?>