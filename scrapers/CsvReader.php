<?php

class CsvReader{

	public function CsvReader($dir) {

        $this->dir = $dir;
        $this->all_files = new FilesystemIterator($this->dir, FilesystemIterator::SKIP_DOTS);

    }

    public function change_dir($dir){
    	$this->dir = $dir;
    }

    public function get_dir(){
    	return $this->dir;
    }

	public function read_csv_as_array($file){

		if( strpos($file, ".csv") === false ){
			throw new Exception('Not a csv file');
		}

		$rows = array();
		$cols = array();

		$row = 0;
		if (($handle = fopen($file, "r")) !== FALSE) {
		    while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
		        $num = count($data);

		        //echo "NUM " . $num . "\n";
		        //echo "ROW " . $row . "\n";

		        for ($column=0; $column < $num; $column++) {
		            //echo $data[$column] . " COLUMN " . $column . "\n";
		            $cols[$column] = $data[$column];
		        }
		        $array_of_rows[$row] = $data;
		        $row++;
		    }
		    fclose($handle);
		}

		return $array_of_rows;
	}

	public function read_all_csv_as_array(){

		$all_csvs = array();

		foreach ($this->all_files as $file) {
			array_push($all_csvs, $this->read_csv_as_array($file));
		}

		return $all_csvs;
	}

}

?>
