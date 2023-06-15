<?php
use Google\Cloud\Firestore\FirestoreClient;

/**
 * Initialize Cloud Firestore with default project ID.
 */
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
            'projectId' => $projectId,
        ]);
        printf('Created Cloud Firestore client with project ID: %s' . PHP_EOL, $projectId);
    }
}

?>