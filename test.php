<?php

$servername = "localhost";
$username = "root";
$password = "orderapp";
$dbname = "orderapp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

//set character set to utf-8
if (!$conn->set_charset("utf8")) {
    printf("Error loading character set utf8: %s\n", $conn->error);
    exit();
} else {
    printf("Current character set: %s\n", $conn->character_set_name());
}

function getLastId($conn, $table) {

	$sql = "SELECT *  FROM " . $table;
	$result = $conn->query($sql);
	$last_id = -1;

	if ($result->num_rows > 0) {
	    // output data of each row
	    while($row = $result->fetch_assoc()) {
	        $last_id = $row["id"] >= $last_id ? $row["id"] : $last_id;
		$row["id"].  "\n";
	    }
	    echo " Last inserted ID is: " . $last_id . "\n";

	} else {
	    echo "0 results";
	}
	return $last_id;
}

function getLastSortId($conn, $table, $last_id) {
	$sql = "SELECT *  FROM " . $table .  " WHERE id=" . $last_id;
	$result = $conn->query($sql);
	$last_sort_id = -1;

	if ($result->num_rows > 0) {
	    // output data of each row
	    while($row = $result->fetch_assoc()) {
		$last_sort_id = $row["sort"];
		}

	} else {
	    echo "0 results";
	}

	return $last_sort_id;
}

function makeArrayIntoCsvString($array, $symbol=""){
	$csvString = "";

	foreach ($array as $value)
		$csvString .= $symbol . $value . $symbol . ", ";

	$indexOfLastComma = strrpos( $csvString , ", ");
	$csvString = substr($csvString, 0, $indexOfLastComma);

	return $csvString;
}

//values_array should NOT include id and sort
function insertInTable($conn, $table, $column_names_array, $last_id, $last_sort_id, $values_array) {
	$colums_string = makeArrayIntoCsvString($column_names_array);
	$values_string = makeArrayIntoCsvString($values_array, "'");
//	$sql = "INSERT INTO " . $table . " (id, sort, menu_id, name_en, name_he, image_url) VALUES (" . ++$last_id  . ", " . ++$last_sort_id . ", '13', 'Soup', 'מרק', 'www.google.com')";
	$sql = "INSERT INTO " . $table . " (" . $colums_string . ") VALUES (" . ++$last_id  . ", " . ++$last_sort_id . ", " . $values_string . ")";

	if ($conn->query($sql) === TRUE) {
	        echo "New record created successfully at ";
	        echo $conn->insert_id . "\n";
	} else {
	    echo "Error: " . $sql . "<br>" . $conn->error;
	}
}

//invocations
$last_id = getLastId($conn, "categories");
$last_sort_id = getLastSortId($conn, "categories", $last_id);

$column_names_array = ["id", "sort", "menu_id", "name_en", "name_he", "image_url"];
$values_array = ["13", "Soup", "מרק", "www.google.com"];

insertInTable($conn, "categories", $column_names_array, $last_id, $last_sort_id, $values_array);

$conn->close();

?>
