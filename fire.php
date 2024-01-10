<?php
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\FieldValue;

require_once "vendor/autoload.php";

putenv("GOOGLE_APPLICATION_CREDENTIALS=" . __DIR__ . '/fire-key.json');

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
setup_client_create("mal-user2id");

function push($id, $username)
{
    $db = new FirestoreClient([
        'credentials' => json_decode(file_get_contents('fire-key.json'), true),
        'projectId' => 'mal-user2id',
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
}

function pull($id)
{
    $db = new FirestoreClient([
        'projectId' => 'mal-user2id',
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
    $db = new FirestoreClient([
        'projectId' => 'mal-user2id',
        'credentials' => json_decode(file_get_contents('fire-key.json'), true),
    ]);
    $doc = $db->collection('username_records')->document($username)->snapshot()->data();
    return $doc["id"];
}
?>