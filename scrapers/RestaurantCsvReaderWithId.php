<?php
require("CsvReader.php");
require("SqlManager.php");

	//public function RestaurantCsvReader($dir){
	//	$this->dir = $dir;
	//}

	$dir = "/home/ec2-user/scrapers/Restaurants/csv_ready_for_db/Angus/";

	$sql = new SqlManager("localhost", "root", "orderapp", "orderapp", "utf8");

	$csv_reader = new CsvReader($dir);

	$restaurants = $csv_reader->read_csv_as_array($dir . "restaurants.csv");
	$categories = $csv_reader->read_csv_as_array($dir . "categories.csv");
	$items = $csv_reader->read_csv_as_array($dir . "items.csv");
	$extras = $csv_reader->read_csv_as_array($dir . "extras.csv");
	$subitems = $csv_reader->read_csv_as_array($dir . "subitems.csv");

	$city_name_he = $restaurants[1][4];
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

//print_2d_array($restaurants);

$city_id = $sql->getIdByColumnHeaderAndValue("cities", "name_he", $city_name_he, "'");
$last_id = $sql->getLastId("restaurants");
$resturant_id = $last_id + 1;

$sql->insertInTableWithoutColumnHeaders("restaurants", $last_id, [
                str_replace("'","\'", $restaurants[1][0]), $restaurants[1][1], $restaurants[1][2] , $restaurants[1][3],  $city_id,
                0,  $restaurants[1][5], str_replace("'","\'", $restaurants[1][6]), $restaurants[1][7],
                str_replace("'","\'", $restaurants[1][8]),  $restaurants[1][9],  $restaurants[1][10],  $restaurants[1][11]
        ]);

$last_id = $sql->getLastId("menus");
$menu_id = $last_id + 1;
$last_sort_id = $sql->getLastSortId("menus");

$sql->insertInTableWithoutColumnHeaders("menus", $last_id, [
                $resturant_id,
                "Lunch",
                "ארוחת צהריים",
                $last_sort_id+1
        ]);

$category_id = -1;
for ($i=1; $i < count($categories); $i++) {

	$last_id = $sql->getLastId("categories");
        $category_id = $last_id + 1;
	$category_sheet_id = $categories[$i][0];

	$last_sort_id = $sql->getLastSortId("categories");
	$current_sort_id = $last_sort_id + 1;

	$sql->insertInTableWithoutColumnHeaders("categories", $last_id, [
               	$menu_id,
               	$categories[$i][1],
		$categories[$i][2],
		$categories[$i][3],
               	$current_sort_id
        ]);

	$item_id = -1;
	for ($j=1; $j < count($items); $j++) {

		if($items[$j][1] !== $category_sheet_id){
			continue;
		}

        	$last_item_id = $sql->getLastId("items");
        	$item_id = $last_item_id + 1;
		$item_sheet_id = $items[$i][0];

        	$last_item_sort_id = $sql->getLastSortId("items");
        	$current_item_sort_id = $last_item_sort_id + 1;

        	$sql->insertInTableWithoutColumnHeaders("items", $last_item_id, [
                	$category_id,
                	str_replace("'","\'", $items[$j][2]),
                	str_replace("'","\'", $items[$j][3]),
                	str_replace("'","\'", $items[$j][4]),
                	str_replace("'","\'", $items[$j][5]),
            		$items[$j][6],
		    	$current_item_sort_id
       		]);

		$extra_id = -1;
		for ($y=1; $y < count($extras); $y++) {

	                if($extras[$y][1] !== $item_sheet_id){
        	                continue;
	                }

        	        $last_extra_id = $sql->getLastId("extras");
        	        $extra_id = $last_extra_id + 1;
			$extra_sheet_id = $extras[$y][0];

        	        $last_extra_sort_id = $sql->getLastSortId("extras");
        	        $current_extra_sort_id = $last_extra_sort_id + 1;

        	        $sql->insertInTableWithoutColumnHeaders("extras", $last_extra_id, [
        	                $item_id,
        	                str_replace("'","\'", $extras[$y][2]),
        	                str_replace("'","\'", $extras[$y][3]),
                	        $extras[$y][4],
                        	str_replace("'","\'", $extras[$y][5]),
                        	$current_extra_sort_id
                	]);

			$subitem_id = -1;
	                for ($z = 1; $z < count($subitems); $z++) {

	                        if($subitems[$z][0] !== $extra_sheet_id){
	                                continue;
	                        }

	                        $last_subitem_id = $sql->getLastId("subitems");
	                        $subitem_id = $last_subitem_id + 1;

	                        $last_subitem_sort_id = $sql->getLastSortId("subitems");
	                        $current_subitem_sort_id = $last_subitem_sort_id + 1;

	                        $sql->insertInTableWithoutColumnHeaders("subitems", $last_subitem_id, [
	                                $extra_id,
	                                str_replace("'","\'", $subitems[$z][1]),
	                                str_replace("'","\'", $subitems[$z][2]),
	                                $subitems[$y][3],
	                                $current_subitem_sort_id
	                        ]);

				unset($subitems[$z]);
                        	$subitems = array_values($subitems);
			}
			unset($extras[$y]);
                      	$extras = array_values($extras);
        	}
		unset($items[$j]);
                $items = array_values($items);
	}
	unset($categories[$i]);
        $categories = array_values($categories);
}

?>
