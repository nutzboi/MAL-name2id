<?php

require "sql-key.php";

$conn = new mysqli(dbhost, dbuser, dbpass, dbname);

function pull($id){
	global $conn;
	$stmt = $conn->prepare("SELECT * FROM records WHERE id = ? ORDER BY first_date");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows < 1) return [];
	else{
		$doc = [];
		foreach($result->fetch_assoc() as $row){
			foreach(array_keys($row) as $key){
				array_push($doc[$key], $row[$key]);
			}
		}
		return $doc;
	}
}

function push($id, $username){
	global $conn;
	$doc = pull($id);
	$lastdate = time();
	if(empty($doc)){
		$stmt = $conn->prepare("INSERT INTO records VALUES (?, ?, ?, ?)");
		$stmt-> bind_param("iiis", $id, $lastdate, $lastdate, $username);
		$stmt->execute();
	}
	else{
		if(end($doc["username"]) != $username) {
			$stmt = $conn->prepare("INSERT INTO records VALUES (?, ?, ?, ?)");
			$stmt-> bind_param("iiis", $id, $lastdate, $lastdate, $username);
			$stmt->execute();
		}
		else{
			$tomodify = end($doc["first_date"]);
			$stmt = $conn->prepare("UPDATE records SET last_date = ?
									WHERE id = ? AND first_date = ?");
			$stmt-> bind_param("iii", $lastdate, $id, $tomodify);
			$stmt->execute();
		}
	}
	return;
}

function pushWayback($id, $username, $time){
	global $conn;
	$doc = pull($id);
	if(empty($doc)){
		$stmt = $conn->prepare("INSERT INTO records VALUES (?, ?, ?, ?)");
		$stmt-> bind_param("iiis", $id, $time, $time, $username);
		$stmt->execute();
	}
	else{
		$l = [-1,-1,""]; $r = [-1,-1,""];
		for($i = 0; $i < count($doc["username"]); $i++){ // find two chronologically closest times
			if($doc["first_date"][$i] <= $time && ($time-$doc["first_date"][$i] <= $time-$l[1] || $l[1] == -1)){
				$l = [$i, $doc["first_date"][$i], "first_date"]; // index, timestamp, timestamp type.
			}
			if($doc["last_date"][$i] <= $time && ($time-$doc["last_date"][$i] <= $time-$l[1] || $l[1] == -1)){
				$l = [$i, $doc["last_date"][$i], "last_date"];
			}
			if($doc["first_date"][$i] >= $time && ($time-$doc["first_date"][$i] >= $time-$r[1] || $r[1] == -1)){
				$r = [$i, $doc["first_date"][$i], "first_date"];
			}
			if($doc["last_date"][$i] >= $time && ($time-$doc["last_date"][$i] >= $time-$r[1] || $r[1] == -1)){
				$r = [$i, $doc["last_date"][$i], "last_date"];
			}
		}
		
		/* operation determination */
		$inspos = 0; // position to insert the wayback record
		$instype = "insert"; // whether to insert a new record or split/extend an existing one
		if($l[0] == -1){
			$inspos = 0;
			if($doc["username"][0] ==  $username){
				$instype = "extend";
			}
		}
		else if($r[0] == -1){
			$inspos = count($doc["username"]);
			if(count($doc["username"]) && $doc["username"][count($doc["username"])-1] ==  $username){
				$inspos--;
				$instype = "extend";
			}
		}
		else{
			if($l[0] == $r[0]){
				if($username == $doc["username"][$l[0]]){
					$instype = "extend";
					$inspos = $l[0];
				}
				else{
					if($r[2] == "first_date" && $l[2] == $r[2]){
						$inspos = $r[0]; 
					}
					else if($l[2] == "last_date" && $l[2] == $r[2]){
						$inspos = $l[0]+1;
					}
					else if($l[2] == "first_date" && $r[2] == "last_date"){
						$instype = "split";
						$inspos = $l[0];
					}
					else{
						// should never happen
						echo "Oopsie!";
						error_log("Oopsie! last_date came first.");
						return;
					}
				}
			}
			else if($l[0] < $r[0]){
				if($username == $doc["username"][$l[0]]){
					$instype = "extend";
					$inspos = $r[0];
				}
				else if($username == $doc["username"][$r[0]]){
					$instype = "extend";
					$inspos = $l[0];
				}
				else{
					$inspos = $r[0];
				}
			}
			else{
				// should never happen
				echo "Oopsie!";
				error_log("Oopsie! left bound greater than right.");
				return;
			}
		}
		/* end determination */
		
		/* operation execution */
		if($instype == "split"){
			$firstdate = $doc["first_date"][$inspos];
			$lastdate = $doc["last_date"][$inspos];
			// $doc["last_date"][$inspos] = $firstdate;
			
			$tomodify = $doc["first_date"][$inspos];
			$stmt = $conn->prepare("UPDATE records SET last_date = ?
									WHERE id = ? AND first_date = ?");
			$stmt-> bind_param("iii", $firstdate, $id, $tomodify);
			$stmt->execute();
			
			// $newuser = [$username, $doc["username"][$inspos]];
			// $newtime = [$time, $lastdate];
			
			$stmt = $conn->prepare("INSERT INTO records VALUES(?, ?, ?, ?)");
			$stmt-> bind_param("iiis", $id, $time, $time, $username);
			$stmt->execute();
			
			$stmt = $conn->prepare("INSERT INTO records VALUES(?, ?, ?, ?)");
			$stmt-> bind_param("iiis", $id, $lastdate, $lastdate, $doc["username"][$inspos]);
			$stmt->execute();
			
			// $inspos++;
			// array_splice($doc["username"], $inspos, 0, $newuser);
			// array_splice($doc["first_date"], $inspos, 0, $newtime);
			// array_splice($doc["last_date"], $inspos, 0, $newtime);
		}
		else if($instype == "extend"){
			$firstdate = min($doc["first_date"][$inspos], $time);
			$lastdate = max($doc["last_date"][$inspos], $time);
			$tomodify = $doc["first_date"][$inspos];
			$stmt = $conn->prepare("UPDATE records SET first_date = ?, last_date = ?
									WHERE id = ? AND first_date = ?");
			$stmt-> bind_param("iiii", $firstdate, $lastdate, $id, $tomodify);
			$stmt->execute();
		}
		else if($instype == "insert"){
			$stmt = $conn->prepare("INSERT INTO records VALUES (?, ?, ?, ?)");
			$stmt-> bind_param("iiis", $id, $time, $time, $username);
			$stmt->execute();
		}
		/* end execution */
	}
    
    return;
}

function print_table($doc)
{
    echo "<div></div>";
	echo "<div class=\"table\">
    <p>Username</p>
    <p>First Seen</p>
    <p>Last Seen</p>";
    for ($i = 0; $i < count($doc["username"]); $i++) {
        echo "<p>" . $doc["username"][$i] . "</p> " .
            "<p>" . date("Y-m-d", $doc["first_date"][$i]) . "</p> " .
            "<p>" . date("Y-m-d", $doc["last_date"][$i]) . "</p> ";
    }
    echo "</div><br>
    <p><i>All dates are expressed in ISO 8601 <b>(YYYY-MM-DD)</b> format.</i></p>";
}

function dig_records($username){
	global $conn;
	$stmt = $conn->prepare("SELECT id FROM records WHERE username = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows < 1) return [];
	else{
		return $result->fetch_all();
	}
}

/* if(isset($_GET["test"])){
	push(0,"hi");
	// print_r(pull(0)); echo "\n" . "\n<br>";
	push(1, "hello");
	// print_r(pull(1)); echo "\n" . "\n<br>";
	push(0, "hey");
	// print_r(pull(0)); echo "\n" . "\n<br>";
	sleep(2);
	push(0,"hey");
	// print_r(pull(0)); echo "\n" . "\n<br>";
	push(1,"hello");
	print_r(pull(1)); echo "\n" . "\n<br>";
} */
?>