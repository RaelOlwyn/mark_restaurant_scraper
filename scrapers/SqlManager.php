<?php

Class SqlManager{

	public function SqlManager($servername, $username, $password, $dbname, $charset){
		$this->conn = new mysqli($servername, $username, $password, $dbname);

		$this->check_connection();
		$this->set_charset($charset);
	}

	function check_connection(){
		if ($this->conn->connect_error) {
		    die("Connection failed: " . $this->conn->connect_error . "\n");
		}
		else{
			printf("Good connection" . "\n");
		}
	}

	public function get_charset($charset){
		return $this->conn->character_set_name();
	}

	public function set_charset($charset){
		//set character set to utf-8
		if (!$this->conn->set_charset($charset)) {
		    printf("Error loading character set utf8: %s\n", $this->conn->error);
		    exit();
		} else {
		    printf("Current character set: %s\n", $this->conn->character_set_name());
		}
	}

	public function getLastId($table) {

		$sql = "SELECT *  FROM " . $table;
		$result = $this->conn->query($sql);
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

	public function getLastSortId($table, $last_id) {
		$sql = "SELECT *  FROM " . $table .  " WHERE id=" . $last_id;
		$result = $this->conn->query($sql);
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

	public function getIdByColumnHeaderAndValue($table, $column_header, $column_value, $symbol=""){
        	$sql = "SELECT id FROM " . $table .  " WHERE " . $column_header . "=" . $symbol . $column_value . $symbol;
        	//$sql = "SELECT id FROM cities WHERE name_en='Hod Hasharon'";
        	$result = $this->conn->query($sql);
        	$id = -1;

        	if ($result->num_rows > 0) {
            	// output data of each row
            	while($row = $result->fetch_assoc()) {
                	$id = $row["id"];
            	}

    	 	} else {
            		echo "0 results";
        	}

			return $id;
	}

	function makeArrayIntoCsvString($array, $symbol=""){
	        $csvString = "";

	        foreach ($array as $value)
	                $csvString .= $symbol . $value . $symbol . ", ";

	        $indexOfLastComma = strrpos( $csvString , ", ");
	        $csvString = substr($csvString, 0, $indexOfLastComma);

	        return $csvString;
	}

	public function insertInTableWithoutColumnHeaders($table, $last_id, $values_array) {
	        $values_string = $this->makeArrayIntoCsvString($values_array, "'");

	        $sql = "INSERT INTO " . $table . " VALUES (" . ++$last_id  . ", " . $values_string . ")";

	        if ($this->conn->query($sql) === TRUE) {
	                echo "New record created successfully at ";
	                echo $this->conn->insert_id . "\n";
	        } else {
	            echo "Error: " . $sql . "<br>" . $this->conn->error;
	        }
	}


	public function insertInTable($table, $column_names_array, $last_id, $last_sort_id, $values_array) {
		$colums_string = $this->makeArrayIntoCsvString($column_names_array);
		$values_string = $this->makeArrayIntoCsvString($values_array, "'");

		$sql = "INSERT INTO " . $table . " (" . $colums_string . ") VALUES (" . ++$last_id  . ", " . $values_string . ")";

		if ($this->conn->query($sql) === TRUE) {
		        echo "New record created successfully at ";
		        echo $this->conn->insert_id . "\n";
		} else {
		    echo "Error: " . $sql . "<br>" . $this->conn->error;
		}
	}

	public function close_connection(){
		$this->$conn->close();
	}

}

?>
