<?php
require("CsvReader.php");
require("SqlManager.php");

	//public function RestaurantCsvReader($dir){
	//	$this->dir = $dir;
	//}

	$dir = "/home/ec2-user/scrapers/Restaurants/csv_ready_for_db/אנגוס/";

	$sql = new SqlManager("localhost", "root", "orderapp", "orderapp", "utf8");

	$csv_reader = new CsvReader($dir);

	$restaurants = $csv_reader->read_csv_as_array($dir . "restaurants.csv");
	$categories = $csv_reader->read_csv_as_array($dir . "categories.csv");
	$items = $csv_reader->read_csv_as_array($dir . "items.csv");
	$extras = $csv_reader->read_csv_as_array($dir . "extras.csv");

	$city_name_he = $restaurants[1][3];
	$restaurant_name_he = $restaurants[1][1];

	function print_2d_array($array){
		for ($i=1; $i < count($array); $i++) { 
			for ($j=0; $j < count($array[$i]); $j++) { 
				printf("Row: %u Column: %u\t---\t%s\n",$i, $j, $array[$i][$j]);
			}
		}
	}

	function enter_array_to_db($array){
		for ($i=1; $i < count($array); $i++) { 
			for ($j=0; $j < count($array[$i]); $j++) { 
				printf($array[$i][$j] . "\n");
			}
		}
	}

//function getIdByColumnHeaderAndValue($table, $column_header, $column_value, $symbol="")
//$rcr = new RestaurantCsvReader("/home/ec2-user/scrapers/Restaurants/csv_ready_for_db/אנגוס/");

print_2d_array($restaurants);
//echo $restaurant_name_he;
$city_id = $sql->getIdByColumnHeaderAndValue("cities", "name_he", $city_name_he, "'");
$last_id = $sql->getLastId("restaurants");
$resturant_id = $last_id + 1;

$sql->insertInTableWithoutColumnHeaders("restaurants", $last_id, [
		$restaurants[1][0], $restaurants[1][1],"000-000-0000" , $restaurants[1][2],  $city_id,
		0,  $restaurants[1][4], str_replace("'","\'", $restaurants[1][5]), $restaurants[1][6],
		$restaurants[1][7],  $restaurants[1][8],  $restaurants[1][9],  $restaurants[1][10]
	]);
//print_2d_array($restaurants);

?>
