<?php

require "sql-key.php";

$conn = new mysqli(dbhost, dbuser, dbpass, dbname);

function pull($id){
	global $conn;
	$stmt = $conn->prepare("SELECT jdoc FROM users WHERE id = ?");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows < 1) return [];
	else{
		$jdoc = $result->fetch_assoc()["jdoc"];
		return json_decode($jdoc, true);
	}
}

function push($id, $username){
	global $conn;
	$doc = pull($id);
	$lastdate = time();
	if(empty($doc)){
		$jdoc = json_encode([
			"username" => array($username),
			"first_date" => array(time()),
			"last_date" => array(time())
			]);
		$stmt = $conn->prepare("INSERT INTO users (id, jdoc) VALUES (?, ?)");
		$stmt-> bind_param("is", $id, $jdoc);
		$stmt->execute();
	}
	else{
		if(end($doc["username"]) != $username) {
            $firstdate = time();
            array_push($doc["username"], $username);
            array_push($doc["first_date"], $firstdate);
            array_push($doc["last_date"], $lastdate);
        }
		else{
			$doc["last_date"][count($doc["last_date"])-1] = $lastdate;
		}
		$jdoc = json_encode($doc);
		$stmt = $conn->prepare("UPDATE users SET jdoc = ? WHERE id = ? ");
		$stmt-> bind_param("si", $jdoc, $id);
		$stmt->execute();
	}
	
	$records = dig_records($username);
	if($records == null){
		$stmt = $conn->prepare("INSERT INTO username_records (username, records) VALUES (?, ?)");
		$records = "[{$id}]";
		$stmt->bind_param("ss", $username, $records);
		$stmt->execute();
	}
	else{
		if(!in_array($id, $records)){
			array_push($records, $id);
			$records = json_encode($records);
			$stmt = $conn->prepare("UPDATE username_records SET records = ? WHERE username = ?");
			$stmt->bind_param("ss", $records, $username);
			$stmt->execute();
		}
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
	$stmt = $conn->prepare("SELECT records FROM username_records WHERE username = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows < 1) return [];
	else return json_decode($result->fetch_assoc()["records"], true);
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