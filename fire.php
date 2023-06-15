<?php
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\FieldValue;

class Firestore
{
    private FirestoreClient $firestore;

    public function __construct(){
        $this->firestore = new FirestoreClient([
            "keyFilePath" => "mal-user2id-88800c3331e7.json",
            "projectId" => "mal-user2id"
        ]);
    }

    public function push($id, $username){
        $db = new FirestoreClient(); 
        $docRef = $db->collection('users')->document($id);
        $snapshot = $docRef->snapshot();
        $lastdate = FieldValue::serverTimestamp();
        $doc = [
            "username" => array($username),
            "first_date" => array($lastdate),
            "last_date" => array($lastdate)
        ];
        if ($snapshot->exists()){
            $doc = $snapshot->data();
            if(end($doc["username"]) == $username){
                $doc["last_date"] = $lastdate;
            }    
            else {
                $firstdate = FieldValue::serverTimestamp();
                array_push($doc["username"], $username);
                array_push($doc["first_date"], $firstdate);
                array_push($doc["last_date"], $lastdate);
            }
        }
        $db->collection('users')->document($id)->set($doc);
    }
    public function pull($id){
        $db = new FirestoreClient(); 
        $doc = $db->collection('users')->document($id)->snapshot()->data();
        return $doc;
    }
}


?>