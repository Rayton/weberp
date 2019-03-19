<?php 


include('includes/session.php');

$code = $_GET['name'];

$stockItem =array();

$sql="SELECT description FROM stockmaster WHERE stockid " . LIKE  . " '$code'";

$stockItemQuery = DB_query($sql);
while ($myrow=DB_fetch_array($stockItemQuery)){
	$stockItem = $myrow['description'];
}


echo json_encode($stockItem);

