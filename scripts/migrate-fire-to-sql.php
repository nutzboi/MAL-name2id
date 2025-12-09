<?php
use Google\Cloud\Firestore\FirestoreClient;

require_once "../vendor/autoload.php";

putenv("GOOGLE_APPLICATION_CREDENTIALS=" . __DIR__ . '/../fire-key.json');
$projectId = "mal-user2id";
$db;
function setup_client_create(string $projectId = null)
{
	global $db;
    // Create the Cloud Firestore client
    if (empty($projectId)) {
        // The `projectId` parameter is optional and represents which project the
// client will act on behalf of. If not supplied, the client falls back to
// the default project inferred from the environment.
        $db = new FirestoreClient();
        printf('Created Cloud Firestore client with default project ID.' . PHP_EOL);
    } else {
        $db = new FirestoreClient([
            'credentials' => json_decode(file_get_contents(getenv("GOOGLE_APPLICATION_CREDENTIALS")), true),
            'projectId' => $projectId,
        ]);
        //printf('Created Cloud Firestore client with project ID: %s' . PHP_EOL, $projectId);
    }
}

setup_client_create($projectId);

require "../sql-key.php";
$conn = new mysqli(dbhost, dbuser, dbpass, dbname);

function migrate()
{
    global $db;
	global $conn;
	
    $documents = $db->collection('users')->documents();
    foreach($documents as $doc){
		$jdoc = json_encode($doc->data());
		$stmt = $conn->prepare("INSERT INTO users (id, jdoc) VALUES (?, ?)");
		$docid = (int)($doc->id());
		$stmt-> bind_param("is", $docid, $jdoc);
		$stmt->execute();
	}
	
	$documents = $db->collection('username_records')->documents();
    foreach($documents as $doc){
		$jdoc = json_encode($doc->data()["id"]);
		$stmt = $conn->prepare("INSERT INTO username_records (username, records) VALUES (?, ?)");
		$docid = $doc->id();
		$stmt-> bind_param("ss", $docid, $jdoc);
		$stmt->execute();
	}
	return;
}

if($_GET["pw"] == dbpass){
	// for added security (and convenient debugging),
	// execute only if the sql db pass is passed as a url parameter.
	// make sure sql tables are empty and no case-insensitive duplicates
	// are in the firestore database.
	migrate();
}
