<?php
    //database configuration
    $dbHost = 'localhost';
    $dbUsername = 'root';
    $dbPassword = '20Kukzan09';
    $dbName = 'learn';
    
    //connect with the database
    $db = new mysqli($dbHost,$dbUsername,$dbPassword,$dbName);
    
    //get search term
    $searchTerm = $_GET['term'];
    
    //get matched data from skills table
    $query = $db->query("SELECT * FROM tag WHERE name LIKE '%".$searchTerm."%' ORDER BY name ASC");
    $data = [];
    while ($row = $query->fetch_assoc()) {
        $data[] = $row['name'];
    }
    
    //return json data
    echo json_encode($data);
?>