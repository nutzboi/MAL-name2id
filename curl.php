<?php
require "fire.php";

function down()
{
    echo "MAL is down.";
    echo "<audio autoplay hidden=\"hidden\" src=\"Overtime.mp3\" />";
}
function getUser($id, $echo = false)
{
    $id = trim($id);
    $username = "";
    if (empty($id)) {
        echo "You did not specify a user ID.";
	}
	else if(!is_numeric($id)){
		echo "Invalid ID, must be a number.";
    } else {
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, "https://myanimelist.net/comtocom.php?id2=" . ($id=="4163689"?"9415":"4163689"). "&id1=" . $id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status_code == 200) {
            $startpos = strpos($response, "Comments Between ");
            if ($startpos == 0 && $echo) {
                echo "User ID does not exist in current MAL database.";
                return "";
            } else {
                $endpos = strpos($response, " ", $startpos + 17);
                $username = substr($response, $startpos + 17, $endpos - $startpos - 17);
                push($id, $username);
                $doc = pull($id);
                if($echo){
                    echo "The user ID <i>" . $id . "</i> belongs to <b><a href=\"https://myanimelist.net/profile/$username\">$username</a></b>";
                    print_table($doc);
                }
            }
        } else if($echo){
            down();
        }
    }
    return $username;
}
function getID($username, $echo = false)
{
	$username = trim($username);
    $id = 0;
    if (empty($username) && $echo) {
        echo "You did not specify a username.";
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://myanimelist.net/profile/" . $username);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status_code == 404 && $echo) {
            echo "Username does not exist in current MAL database.";
        } else if ($status_code == 200) {
            $startpos = strpos($response, "https://myanimelist.net/modules.php?go=report&amp;type=profile&amp;id=");
            $endpos = strpos($response, "\"", $startpos);
            $id = substr($response, $startpos + 70, $endpos - $startpos - 70);
            $username = getUser($id);
            push($id, $username);
            if($echo){
                echo "<b><i>" . $username . "</i></b>'s ID is " . $id . ".";
                echo "</p>";
                $doc = pull($id);
                print_table($doc);
            }
        } else if ($echo){
            down();
        }
        curl_close($ch);
    }
    return $id;
}
