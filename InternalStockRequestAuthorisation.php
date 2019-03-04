<?php

include('includes/session.php');
$Title = _('Authorise Internal Stock Requests');

$ViewTopic = 'Inventory';
$BookMark = 'AuthoriseRequest';

include('includes/header.php');
echo '<p class="page_title_text"><img src="' . $rootpath . '/css/' . $theme . '/images/transactions.png" title="' . $title . '" alt="" />' . ' ' . $title . '</p>';

if (isset($_GET['Delete']) || $_GET['Delete'] != '') {
    $sql = "UPDATE stockrequestitems SET deleted='1' WHERE id='" . $_GET['Delete'] . "' ";
    $result = DB_query($sql, $db);
    prnMsg(_('The line was successfully deleted'), 'success');
    $sql = "SELECT dispatchid FROM stockrequestitems WHERE dispatchid='" . $_GET['dispatchid'] . "' AND deleted=0";
    $result = DB_query($sql, $db);
    if (DB_num_rows($result) == 0) {
        $sql = "UPDATE stockrequest SET deleted=1 WHERE dispatchid='" . $_GET['dispatchid'] . "'";
        $result = DB_query($sql, $db);
    }
}



if (isset($_POST['updateall'])) {
    foreach ($_POST as $key => $value) {
        if (mb_substr($key, 0, 6) == 'status') {
            $authorised = true;
        }
        if ($authorised==true) {
			
            $RequestNo = mb_substr($key, 6);
            $sql = "UPDATE stockrequest
				SET authorised='1'
				WHERE dispatchid='" . $RequestNo . "'";
            $result = DB_query($sql, $db);

            if (mb_substr($key, 0, 11) == 'qtyapproved') {
                $qtyapproved = $value;
                $id = mb_substr($key, 11);

                $sql = "UPDATE stockrequestitems 
			             SET qtyapproved='" . $qtyapproved . "' 
			             WHERE  id='" . $id . "'";
                $result = $result = DB_query($sql, $db);
                prnMsg(_('The line was successfully approved'), 'success');
            }
        }
    }//end of foreach
}//end of $_POST['updateall']

$sql = "SELECT stockrequest.dispatchid,
			locations.locationname AS locfrom,
			internalstockauthusers.locationname AS locto,
			stockrequest.despatchdate,
			stockrequest.narrative,
			www_users.realname,
			www_users.email
		FROM stockrequest
		LEFT JOIN internalstockauthusers
			ON stockrequest.loccode_to=internalstockauthusers.loccode
		LEFT JOIN locations
			ON stockrequest.loccode_from=locations.loccode
		LEFT JOIN www_users
			ON www_users.userid=internalstockauthusers.userid		
	WHERE stockrequest.authorised=0
		AND stockrequest.closed=0 AND stockrequest.deleted=0
		AND www_users.userid='" . $_SESSION['UserID'] . "'";



$result = DB_query($sql, $db);


echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="selection">';

echo '<tr>
		<th>' . _('Request Number') . '</th>
		<th>' . _('Receiver') . '</th>
		<th>' . _('Location Of Stock') . '</th>
		<th>' . _('Requested Date') . '</th>
		<th colspan="2">' . _('Narrative') . '</th>
		<th>' . _('Authorise') . '</th>
	</tr>';

while ($myrows = DB_fetch_array($result)) {

    echo '<tr>
			<td>' . $myrows['dispatchid'] . '</td>
			<td>' . $myrows['locto'] . '</td>
			<td>' . $myrows['locfrom'] . '</td>
			<td>' . ConvertSQLDate($myrows['despatchdate']) . '</td>
			<td colspan="2">' . $myrows['narrative'] . '</td>
			<td><input type="checkbox" name="status' . $myrows['dispatchid'] . '" /></td>
		</tr>';

    if (isset($myrows['dispatchid']) OR $myrows['dispatchid'] != '') {
        $linesql = "SELECT stockrequestitems.id, 
			            stockrequestitems.dispatchitemsid,
			            stockrequestitems.dispatchid,
						stockrequestitems.stockid,
						stockrequestitems.decimalplaces,
						stockrequestitems.uom,
						stockmaster.description,
						stockrequestitems.qtyrequested,
						stockrequestitems.qtyapproved
				FROM stockrequestitems
				LEFT JOIN stockmaster
				ON stockmaster.stockid=stockrequestitems.stockid
			WHERE deleted=0 AND dispatchid='" . $myrows['dispatchid'] . "'";

        $lineresult = DB_query($linesql, $db);

        echo '<tr>
			<td></td>
			<td colspan="5" align="left">
				<table class="selection" align="left">
				<tr>
					<th>' . _('Product') . '</th>
					<th>' . _('QTY Requested') . '</th>
					<th>' . _('QTY Approved') . '</th>
					<th>' . _('Units') . '</th>
				</tr>';

        while ($linerows = DB_fetch_array($lineresult)) {
            echo '<tr>
				<td>' . $linerows['description'] . '</td>
				<td><input type="text" readonly name="qtyrequested' . $linerows['id'] . '" value="' . locale_number_format($linerows['qtyrequested'], $linerows['decimalplaces']) . '"</td>
				<td><input type="text" name="qtyapproved' . $linerows['id'] . '" value="' . $linerows['qtyapproved'] . '"</td>
				<td>' . $linerows['uom'] . '</td>
				<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Delete=' . $linerows['id'] . '&dispatchid=' . $linerows['dispatchid'] . '">' . _('Delete') . '</a></td>
			    </tr>';
        }
        echo '</table>';
    }//end if(isset($myrows['dispatchid']) OR $myrows['dispatchid']!='')
}//end of while



echo '</table>';
echo '<br /><div class="centre"><button type="submit" name="updateall">' . _('Update') . '</button></div><br />';
echo '</form>';

include('includes/footer.php');
?>
