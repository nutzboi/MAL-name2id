<?php
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\FieldValue;

require_once "vendor/autoload.php";

putenv("GOOGLE_APPLICATION_CREDENTIALS=" . __DIR__ . '/fire-key.json');
$projectId = "mal-user2id";

function setup_client_create(string $projectId = null)
{
    // Create the Cloud Firestore client
    if (empty($projectId)) {
        // The `projectId` parameter is optional and represents which project the
// client will act on behalf of. If not supplied, the client falls back to
// the default project inferred from the environment.
        $db = new FirestoreClient();
        printf('Created Cloud Firestore client with default project ID.' . PHP_EOL);
    } else {
        $db = new FirestoreClient([
            'credentials' => json_decode(file_get_contents('fire-key.json'), true),
            'projectId' => $projectId,
        ]);
        //printf('Created Cloud Firestore client with project ID: %s' . PHP_EOL, $projectId);
    }
}

setup_client_create($projectId);

function push($id, $username)
{
    global $projectId;
    $db = new FirestoreClient([
        'credentials' => json_decode(file_get_contents('fire-key.json'), true),
        'projectId' => $projectId,
    ]);
    $docRef = $db->collection('users')->document($id);
    $snapshot = $docRef->snapshot();
    $lastdate = time();
    $doc = [
        "username" => array($username),
        "first_date" => array(time()),
        "last_date" => array(time())
    ];
    if ($snapshot->exists()) {
        $doc = $snapshot->data();
        if (end($doc["username"]) != $username) {
            $firstdate = time();
            array_push($doc["username"], $username);
            array_push($doc["first_date"], $firstdate);
            array_push($doc["last_date"], $lastdate);
        }
    }
    $sub = $db->collection('users')->document($id);
    if (!$snapshot->exists()) {
        $sub->set(
            [
                "username" => $username,
                "last_date" => $lastdate,
                "first_date" => $lastdate,
            ]
        );
    }
	$subdata = $sub->snapshot()->data();
    if (!$snapshot->exists() || ($snapshot->exists() && end($subdata["username"]) != $username)) {
        $sub->update([
            ['path' => 'username', 'value' => FieldValue::arrayUnion([$username])]
        ]);
        $sub->update([
            ['path' => 'first_date', 'value' => FieldValue::arrayUnion([$lastdate])]
        ]);
        $sub->update([
            ['path' => 'last_date', 'value' => FieldValue::arrayUnion([$lastdate])]
        ]);
    } else {
        $sub->update([
            ['path' => 'last_date', 'value' => FieldValue::arrayRemove([end($doc["last_date"])])]
        ]);
        $sub->update([
            ['path' => 'last_date', 'value' => FieldValue::arrayUnion([$lastdate])]
        ]);
    }
    $username = strtolower($username);
    $snapshot = $db->collection('username_records')->document($username)->snapshot();
    // $snapshot = $docRef;
    if ($snapshot->exists()) {
        $doc = $snapshot->data();
        $sub = $db->collection('username_records')->document($username);
        if(!in_array($id, $doc["id"])) {
            $sub->update([
                ['path' => 'id', 'value' => FieldValue::arrayUnion([$id])]
            ]);
        }
    }
    else{
        $sub = $db->collection('username_records')->document($username);
        $sub->set([
            "id" => [$id],
        ]);
    }
}

function pushWayback($id, $username, $time){
	global $projectId;
    $db = new FirestoreClient([
        'credentials' => json_decode(file_get_contents('fire-key.json'), true),
        'projectId' => $projectId,
    ]);
	$doc = pull($id);
	if(empty($doc)){
		$sub = $db->collection('users')->document($id);
        $sub->set(
            [
                "username" => [$username],
                "last_date" => [$time],
                "first_date" => [$time],
            ]
        );
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
			$doc["last_date"][$inspos] = $firstdate;
			$newuser = [$username, $doc["username"][$inspos]];
			$newtime = [$time, $lastdate];
			$inspos++;
			array_splice($doc["username"], $inspos, 0, $newuser);
			array_splice($doc["first_date"], $inspos, 0, $newtime);
			array_splice($doc["last_date"], $inspos, 0, $newtime);
		}
		else if($instype == "extend"){
			$doc["first_date"][$inspos] = min($doc["first_date"][$inspos], $time);
			$doc["last_date"][$inspos] = max($doc["last_date"][$inspos], $time);
		}
		else if($instype == "insert"){
			array_splice($doc["username"], $inspos, 0, $username);
			array_splice($doc["first_date"], $inspos, 0, $time);
			array_splice($doc["last_date"], $inspos, 0, $time);
		}
		
		$sub = $db->collection('users')->document($id);
		$sub->set(
            [
                "username" => [$username],
                "last_date" => [$lastdate],
                "first_date" => [$firstdate],
            ]
        );
		/* end execution */
	}
    
    $snapshot = $db->collection('username_records')->document(strtolower($username))->snapshot();
    if ($snapshot->exists()) {
        $doc = $snapshot->data();
        $sub = $db->collection('username_records')->document(strtolower($username));
        if(!in_array($id, $doc["id"])) {
            $sub->update([
                ['path' => 'id', 'value' => FieldValue::arrayUnion([$id])]
            ]);
        }
    }
    else{
        $sub = $db->collection('username_records')->document(strtolower($username));
        $sub->set([
            "id" => [$id],
        ]);
    }
    
    return;
}

function pull($id)
{
    global $projectId;
    $db = new FirestoreClient([
        'projectId' => $projectId,
        'credentials' => json_decode(file_get_contents('fire-key.json'), true),
    ]);
    $doc = $db->collection('users')->document($id)->snapshot()->data();
    return $doc;
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
    global $projectId;
    $db = new FirestoreClient([
        'projectId' => $projectId,
        'credentials' => json_decode(file_get_contents('fire-key.json'), true),
    ]);
    $username = strtolower($username);
    $doc = $db->collection('username_records')->document($username)->snapshot()->data();
    if($doc == NULL) return [];
    else return $doc["id"];
}
