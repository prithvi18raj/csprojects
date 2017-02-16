<html>
<head>
<style type="text/css">
body{
	overflow:hidden;
	}
	
.style1 {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
}
</style>

<script language="javascript">

function resize_window()
{
	window.resizeTo(300,300);
}

</script>

</head>

<body onLoad="resize_window()">
<?php
   
    /**
    * Method to add puzzle
    * puzzle generated using the "Peraita method - Author unknown"
    * @param string $difficulty The difficulty raing of the puzzle (used to remove numbers)
    * $size the base size of the grid eg. 3 = 9x9 board, 5 = 25*25 board
    **/
    
global $db;
global $db_host;
global $db_user;
global $db_pwd;
global $database;
global $table;
global $puzzle_difficulty;


$db_host = strip_tags($_POST['hostname']);
$db_user = strip_tags($_POST['username']);
$db_pwd = strip_tags($_POST['password']);
$database = strip_tags($_POST['dbname']);
$table = strip_tags($_POST['tablename']);
$puzzle_difficulty = strip_tags($_POST['diff']);
	
$db = mysql_connect($db_host,$db_user,$db_pwd,$database);

if(!$db)
{
	die("Unable: ");
}

mysql_select_db($database);

if (!mysql_select_db($database))
die("Can't select database");
	
	
$loop_count = strip_tags($_POST['puzzle_num']);

for($loop=1;$loop<=$loop_count;$loop++)
{
	$puzzle = "";

	$puzzle = generate(strip_tags($_POST['diff']));
    
	mysql_query("INSERT INTO student1(puzzle,puzzle_solved,diff) VALUES ('$puzzle[0]','$puzzle[1]','$puzzle_difficulty')") or die (mysql_error());

}	



function generate($difficulty)
    {
        // set up board size
        //$difficulty = 1;
        $size = 3;
        // randomize numbers
        $numbers = range(1, pow($size, 2));
        shuffle($numbers);

        // row index sets
        $x = 1;
        for($i = 1; $i <= pow($size, 2); $i++){
            $a = "rowIndex_" . $i; //set up variable names eg $rowIndex_1
            for($ii = 1; $ii <= pow($size, 2); $ii++){
                ${$a}[$ii] = $x; //set up variable eg $rowIndex[0] = 1
                $x = $x + 1;
            }
            $allRows[$i] = $$a; //set up array eg $temp[0] = $rowIndex_1
        }
        $temp = array_chunk($allRows, $size, true);
        foreach($temp as $key => $arrRow){
            $a = "arrRow_" . $key; // set up variable names
            $$a = $arrRow; // set up variable
            $arrAllRows[$key] = $$a; // set up array
        }

        // column index sets
        for($i = 1; $i <= pow($size, 2); $i++){
            $a = "colIndex_" . $i; // set up variable names
            $x = $i;
            for($ii = 1; $ii <= pow($size, 2); $ii++){
                ${$a}[$ii] = $x; // set up variable
                $x = $x + pow($size, 2);
            }
            $allCols[$i] = $$a; // set up array
        }
        $temp = array_chunk($allCols, $size, true);
        foreach($temp as $key => $arrCol){
            $a = "arrCol_" . $key; // set up variable names
            $$a = $arrCol;  // set up variable
            $arrAllCols[$key] = $$a; // set up array
        }

        // block index sets
        $x = 1;
        $y = 1;
        for($i = 1; $i <= $size; $i++){
            for($ii = 1; $ii <= $size; $ii++){
                $a = "blockIndex_" . $x; // set up variable names
                $z = 1;
                for($iii = 1; $iii <= $size; $iii++){
                    for($iv = 1; $iv <= $size; $iv++){
                        ${$a}[$z++] = $y; // set up variable
                        $y = $y + 1;
                    }
                    $y = $y + ((pow($size, 2)) - ($size));
                }
                $arrAllBlocks[$x] = $$a; // set up array
                $x = $x + 1;
            }
            $y = ($i * $size) + 1;
        }

        // set up basic board
        for($i = 1; $i <= pow($size, 2); $i++){
            foreach($arrAllBlocks as $block){
                $temp = $numbers;
                foreach($block as $index){
                    $data[$index] = array_shift($temp);
                }
                $firstNumber = array_shift($numbers);
                $numbers = array_pad($numbers, pow($size, 2), $firstNumber);
            }
        }
        ksort($data);

        // shuffle rows
        for($i = 0; $i <= $size - 2; $i++){
            foreach($arrAllRows as $arrRows){
                shuffle($arrRows);
                $arrRows = array_slice($arrRows, 0, 2); // takes first 2 rows
                foreach($arrRows as $key => $row){
                    foreach($row as $rowKey => $index){
                        if($key == 0){
                            $row_1[$rowKey] = $data[$index];
                        }else{
                            $row_2[$rowKey] = $data[$index];
                        }
                    }
                }
                foreach($arrRows as $key => $row){ // swops them
                    foreach($row as $rowKey => $index){
                        if($key == 0){
                            $data[$index] = $row_2[$rowKey];
                        }else{
                            $data[$index] = $row_1[$rowKey];
                        }
                    }
                }
            }
        }

        // shuffle columns
        for($i = 0; $i <= $size - 2; $i++){
            foreach($arrAllCols as $arrCols){
                shuffle($arrCols);
                $arrCols = array_slice($arrCols, 0, 2); // takes first 2 columns
                foreach($arrCols as $key => $col){
                    foreach($col as $colKey => $index){
                        if($key == 0){
                            $col_1[$colKey] = $data[$index];
                        }else{
                            $col_2[$colKey] = $data[$index];
                        }
                    }
                }
                foreach($arrCols as $key => $col){ // swops them
                    foreach($col as $colKey => $index){
                        if($key == 0){
                            $data[$index] = $col_2[$colKey];
                        }else{
                            $data[$index] = $col_1[$colKey];
                        }
                    }
                }
            }
        }
        $solution = implode(",", $data);

       //remove pairs of numbers symetrically
        if($difficulty == 1){
          $pairs = 16;
      }elseif($difficulty == 2){
         $pairs = 22;
      }elseif($difficulty == 3){
         $pairs = 30;
      }else{
         $pairs = 170;
			//$pairs = 0;
        }
        
		$puzzle1 = implode("", $data);
		
		for($i = 1; $i <= $pairs; $i++){
          do{
             $number_1 = rand(1, pow($size, 4));
           }while($number_1 == (((pow($size, 4) - 1) / 2) + 1));
          $data[$number_1] = '';
            $number_2 = (pow($size, 4) + 1) - $number_1;
            $data[$number_2] = '';
        }
       
	    $puzzle = "";
        //print_r($data);
      
	    for($i = 1;$i<count($data)+1;$i++)
        {
        	if($data[$i] == "")
        		$puzzle .= ".";
        	else 	
       		$puzzle .= $data[$i];
        }
        
       $puzzle = array($puzzle, $puzzle1);
        return $puzzle;
        
    }
	
?>
<form>

  <div align="center">
    <p class="style1">Puzzles Successfully Created. </p>
    <p>
      <input type=button value="Close Window" onClick="javascript:window.close();">
      </p>
  </div>
</form>
</body>
</html>






	