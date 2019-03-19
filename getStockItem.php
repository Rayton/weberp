<?php 


include('includes/session.php');

$type = $_GET['type'];
$query = $_GET['q'];

$stockItems =array();



if ($type == "code") {
	$sql="SELECT * FROM stockmaster WHERE stockid " . LIKE  . " '%" . $query ."%'";
	$stockItemQuery = DB_query($sql);
	while ($myrow=DB_fetch_array($stockItemQuery)){
		array_push($stockItems, $myrow['stockid']);
	}

}else {
	$sql="SELECT * FROM stockmaster WHERE description " . LIKE . " '%" . $query ."%'";
	$stockItemQuery = DB_query($sql);
	while ($myrow=DB_fetch_array($stockItemQuery)){
		array_push($stockItems, $myrow['description']);
	}
}

echo json_encode($stockItems);
