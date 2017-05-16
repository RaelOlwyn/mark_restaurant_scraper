<?php
require("CsvReader.php");
require("SqlManager.php");

	//public function RestaurantCsvReader($dir){
	//	$this->dir = $dir;
	//}

	$dir = "/home/ec2-user/scrapers/Restaurants/csv_ready_for_db/אלגריה/";

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
$last_sort_id = $sql->getLastSortId("restaurants");
$resturant_id = $last_id + 1;

$restaurants_column_names = ["id", "name_en", "name_he", "coming_soon", "contact", "min_amount", "city_id", "hide", "logo", "description_en", "description_he", "address_en", "address_he", "hechsher_en", "hechsher_he", "sort", "pickup_hide" ];

$restaurant_name_en = str_replace("'","\'", $restaurants[1][0]);
$restaurant_name_he = str_replace("'","\'", $restaurants[1][1]);
$restaurant_coming_soon = 1;
$restaurant_contact = $restaurants[1][2];
$restaurant_min_amount = intval($restaurants[1][3]);
$restaurant_hide = 0;
$restaurant_logo = $restaurants[1][5];
$restaurant_description_en = str_replace("'","\'", $restaurants[1][6]);
$restaurant_description_he = str_replace("'","\'", $restaurants[1][7]);
$restaurant_address_en = str_replace("'","\'", $restaurants[1][8]);
$restaurant_address_he = str_replace("'","\'", $restaurants[1][9]);
$restaurant_hechsher_en	= str_replace("'","\'", $restaurants[1][10]);
$restaurant_hechsher_he = str_replace('"','\"', $restaurants[1][11]);
$restaurant_pickup_hide = 0;

$sql->insertInTable("restaurants", $restaurants_column_names, $last_id, [
        $restaurant_name_en,
	$restaurant_name_he,
	$restaurant_coming_soon,
	$restaurant_contact,
	$restaurant_min_amount,
	$city_id,
	$restaurant_hide,
	$restaurant_logo,
	$restaurant_description_en,
	$restaurant_description_he,
	$restaurant_address_en,
	$restaurant_address_he,
	$restaurant_hechsher_en,
	$restaurant_hechsher_he,
	$last_sort_id+1,
	$restaurant_pickup_hide
]);

$last_id = $sql->getLastId("menus");
$menu_id = $last_id + 1;
$last_sort_id = $sql->getLastSortId("menus");

$menus_column_names = ["id", "restaurant_id", "name_en", "name_he", "sort"];

$sql->insertInTable("menus", $menus_column_names, $last_id, [
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

	$categories_column_names = ["id", "menu_id", "name_en", "name_he", "image_url", "sort"];

	$categories_name_en = str_replace("'","\'", $categories[$i][1]);
	$categories_name_he = str_replace("'","\'", $categories[$i][2]);
	$categories_image_url = $categories[$i][3];

	$sql->insertInTable("categories", $categories_column_names, $last_id, [
               	$menu_id,
               	$categories_name_en,
		$categories_name_he,
		$categories_image_url,
               	$current_sort_id
        ]);

	$item_id = -1;
	for ($j=1; $j < count($items); $j++) {

		if($items[$j][1] !== $category_sheet_id){
			continue;
		}

        	$last_item_id = $sql->getLastId("items");
        	$item_id = $last_item_id + 1;
		$item_sheet_id = $items[$j][0];

        	$last_item_sort_id = $sql->getLastSortId("items");
        	$current_item_sort_id = $last_item_sort_id + 1;

		$items_column_names = ["id", "category_id", "hide", "name_en", "name_he", "desc_en", "desc_he", "price", "sort"];

		$items_hide = 0;
		$items_name_en = str_replace("'","\'", $items[$j][2]);
		$items_name_he = str_replace("'","\'", $items[$j][3]);
		$items_desc_en = str_replace("'","\'", $items[$j][4]);
		$items_desc_he = str_replace("'","\'", $items[$j][5]);
		$items_price = $items[$j][6];

        	$sql->insertInTable("items", $items_column_names, $last_item_id, [
                	$category_id,
			$items_hide,
                	$items_name_en,
                	$items_name_he,
                	$items_desc_en,
                	$items_desc_he,
            		$items_price,
		    	$current_item_sort_id
       		]);

		$extra_id = -1;
		for ($y=1; $y < count($extras); $y++) {

//			$last_extra_id = $sql->getLastId("extras");
//                        $extra_id = $last_extra_id + 1;
//			$extras[$y][1] . " !== " . $item_sheet_id;

	                if($extras[$y][1] !== $item_sheet_id){
        	                echo "\nITEM FK:" . $extras[$y][1] . " !== PK:" . $item_sheet_id;
				continue;
	                }

        	        $last_extra_id = $sql->getLastId("extras");
        	        $extra_id = $last_extra_id + 1;
			$extra_sheet_id = $extras[$y][0];

        	        $last_extra_sort_id = $sql->getLastSortId("extras");
        	        $current_extra_sort_id = $last_extra_sort_id + 1;

			$extras_column_names = [];

        	        $sql->insertInTableWithoutColumnHeaders("extras", $last_extra_id, [
        	                $item_id,
        	                str_replace("'","\'", $extras[$y][2]),
        	                str_replace("'","\'", $extras[$y][3]),
                	        $extras[$y][4],
                        	str_replace("'","\'", $extras[$y][5]),
                        	0, //limit
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
	                               	intval($subitems[$y][3]),
	                                $current_subitem_sort_id
	                        ]);

//				unset($subitems[$z]);
//                        	$subitems = array_values($subitems);
			}
//			unset($extras[$y]);
//                      	$extras = array_values($extras);
        	}
//		unset($items[$j]);
//                $items = array_values($items);
	}
//	array_splice($categories, $i, $i); //test
}

?>
