<?php
/* $Id: UnitsOfMeasure.php 4567 2011-05-15 04:34:49Z daintree $*/

include('includes/session.php');

$Title = _('Department Authorization');

include('includes/header.php');
echo '<p class="page_title_text"><img src="' . $rootpath . '/css/' . $theme . '/images/magnifier.png" title="' .
		_('Top Sales Order Search') . '" alt="" />' . ' ' . $title . '</p>';
		
if (isset($_POST['SelectedUser'])) {
	$SelectedUser = mb_strtoupper($_POST['SelectedUser']);
} elseif (isset($_GET['SelectedUser'])) {
	$SelectedUser = mb_strtoupper($_GET['SelectedUser']);
} else {
	$SelectedUser = '';
}

if (isset($_POST['SelectedLocation'])) {
	$SelectedLoccode = mb_strtoupper($_POST['SelectedLocation']);
	

} elseif (isset($_GET['SelectedLocation'])) {
	$SelectedLoccode = mb_strtoupper($_GET['SelectedLocation']);
}		
		
if (isset($_POST['Cancel'])) {
	unset($SelectedLoccode);
	unset($SelectedUser);
}		
		
if (isset($_POST['Process'])) {
	if ($_POST['SelectedLocation'] == '') {
		echo prnMsg(_('You have not selected any stock location'), 'error');
		echo '<br />';
		unset($SelectedLoccode);
		unset($_POST['SelectedLocation']);
	}
}

if (isset($_POST['submit'])) {

	$InputError = 0;

	if ($_POST['SelectedUser'] == '') {
		$InputError = 1;
		echo prnMsg(_('You have not selected an user to be authorised to use this location'), 'error');
		echo '<br />';
		unset($SelectedLoccode);
	}

	if ($InputError != 1) {

		// First check the user is not being duplicated

		$checkSql = "SELECT count(*)
			     FROM internalstockauthusers
			     WHERE loccode= '" . $_POST['SelectedLocation'] . "'
				 AND userid = '" . $_POST['SelectedUser'] . "'";

		$checkresult = DB_query($checkSql, $db);
		$checkrow = DB_fetch_row($checkresult);

		if ($checkrow[0] > 0) {
			$InputError = 1;
			prnMsg(_('The user') . ' ' . $_POST['SelectedUser'] . ' ' . _('already authorised to use this location'), 'error');
		} else {
			// Add new record on submit
			$locnameSql="SELECT locationname FROM locations WHERE loccode='".$_POST['SelectedLocation']."'";
			$locnameresult=DB_query($locnameSql,$db);
			$locnamerows=DB_fetch_row($locnameresult);
			
			
			$sql = "INSERT INTO internalstockauthusers  (loccode,locationname,
												userid)
										VALUES ('" . $_POST['SelectedLocation'] . "',
										        '" .$locnamerows[0]. "', 
												'" . $_POST['SelectedUser'] . "')";

			$msg = _('User') . ': ' . $_POST['SelectedUser'] . ' ' . _('has been authorised to use') . ' ' . $SelectedLocationName . ' ' . _('location');
			$result = DB_query($sql, $db);
			prnMsg($msg, 'success');
			unset($_POST['SelectedUser']);
		}
	}
} elseif (isset($_GET['delete'])) {
	$sql = "DELETE FROM internalstockauthusers
		WHERE loccode='" . $SelectedLoccode . "'
		AND userid='" . $SelectedUser . "'";

	$ErrMsg = _('The location user record could not be deleted because');
	$result = DB_query($sql, $db, $ErrMsg);
	prnMsg(_('User') . ' ' . $SelectedUser . ' ' . _('has been un-authorised to use') . ' ' . $SelectedLocationName . ' ' . _('location'), 'success');
	unset($_GET['delete']);
}


if (isset($_POST['process']) OR isset($SelectedLoccode)) {
	$SQLName = "SELECT locationname
			FROM locations
			WHERE loccode='" . $SelectedLoccode . "'";
	$result = DB_query($SQLName, $db);
	$myrow = DB_fetch_array($result);
	$SelectedLocationName = $myrow['locationname'];

	echo '<br /><div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . _('Authorised users for') . ' ' . $SelectedLocationName . ' ' . _('Location') . '</a></div></br>';
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<input type="hidden" name="SelectedLocation" value="' . $SelectedLoccode . '" />';
		
	$sql = "SELECT internalstockauthusers.userid,
					www_users.realname
			FROM internalstockauthusers INNER JOIN www_users
			ON internalstockauthusers.userid=www_users.userid
			WHERE internalstockauthusers.loccode='" . $SelectedLoccode . "'
			ORDER BY internalstockauthusers.userid ASC";
	
	$result = DB_query($sql, $db);
	
	echo '<br />
			<table class="selection">';
			
			echo '<tr><th colspan="3"><h3>' . _('Authorised users for location') . ' ' . $SelectedLocationName . '</h3></th></tr>';
	echo '<tr>
			<th>' . _('User Code') . '</th>
			<th>' . _('User Name') . '</th>
		</tr>';
		
$k = 0; //row colour counter

	while ($myrow = DB_fetch_array($result)) {
		if ($k == 1) {
			echo '<tr class="EvenTableRows">';
			$k = 0;
		} else {
			echo '<tr class="OddTableRows">';
			$k = 1;
		}

		printf('<td>%s</td>
			<td>%s</td>
			<td><a href="%s?SelectedUser=%s&amp;delete=yes&amp;SelectedLocation=' . $SelectedLoccode . '" onclick="return confirm(\'' . _('Are you sure you wish to un-authorize this user?') . '\');">' . _('Un-authorize') . '</a></td>
			</tr>', $myrow['userid'], $myrow['realname'], htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), $myrow['userid'], htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), $myrow['userid']);
	}
	//END WHILE LIST LOOP		
		
	echo '</table>';
	
	if (!isset($_GET['delete'])) {


		echo '<br /><table  class="selection">'; //Main table

		echo '<tr><td>' . _('Select User') . ':</td><td><select name="SelectedUser">';

		$SQL = "SELECT userid,
						realname
				FROM www_users";

		$result = DB_query($SQL, $db);
		if (!isset($_POST['SelectedUser'])) {
			echo '<option selected="selected" value="">' . _('Not Yet Selected') . '</option>';
		}
		while ($myrow = DB_fetch_array($result)) {
			if (isset($_POST['SelectedUser']) AND $myrow['userid'] == $_POST['SelectedUser']) {
				echo '<option selected="selected" value="';
			} else {
				echo '<option value="';
			}
			echo $myrow['userid'] . '">' . $myrow['userid'] . ' - ' . $myrow['realname'] . '</option>';

		} //end while loop

		echo '</select></td></tr>';

		echo '</table>'; // close main table
		DB_free_result($result);

		echo '<br /><div class="centre"><input type="submit" name="submit" value="' . _('Accept') . '" />
									<input type="submit" name="Cancel" value="' . _('Cancel') . '" /></div>';

		echo '</div>
              </form>';

	} // end if user wish to delete	
		
	echo '</div>
              </form>';	
}

if (!isset($SelectedLoccode)) {

	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedUser will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
	then none of the above are true. These will call the same page again and allow update/input or deletion of the records*/
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection">'; //Main table

	echo '<tr><td>' . _('Select Stock Location') . ':</td><td><select name="SelectedLocation">';

	$SQL = "SELECT loccode,
					locationname
			FROM locations";

	$result = DB_query($SQL, $db);
	echo '<option value="">' . _('Not Yet Selected') . '</option>';
	while ($myrow = DB_fetch_array($result)) {
		if (isset($SelectedLocation) and $myrow['loccode'] == $SelectedLocation) {
			echo '<option selected="selected" value="';
		} else {
			echo '<option value="';
		}
		echo $myrow['loccode'] . '">' . $myrow['loccode'] . ' - ' . $myrow['locationname'] . '</option>';

	} //end while loop

	echo '</select></td></tr>';

	echo '</table>'; // close main table
	DB_free_result($result);

	echo '<br /><div class="centre"><input type="submit" name="Process" value="' . _('Accept') . '" />
				<input type="submit" name="Cancel" value="' . _('Cancel') . '" /></div>';

	echo '</div>
          </form>';

}



include('includes/footer.php');
?>
