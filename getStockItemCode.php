<?php 


include('includes/session.php');

$description = $_GET['name'];

$stockItem =array();

$sql="SELECT stockid FROM stockmaster WHERE description " . LIKE  . " '%" . $description ."%'";

$stockItemQuery = DB_query($sql);
while ($myrow=DB_fetch_array($stockItemQuery)){
	$stockItem = $myrow['stockid'];
}


echo json_encode($stockItem);

