<?php
require "sql.php";

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
        $success = 0;
        if ($status_code == 200) {
            $startpos = strpos($response, "Comments Between ");
            if ($startpos == 0 && $echo) {
                echo "User ID does not exist in current MAL database.";
            } else {
                $success = 1;
                $endpos = strpos($response, " ", $startpos + 17);
                $username = substr($response, $startpos + 17, $endpos - $startpos - 17);
                push($id, $username);
                if($echo){
                    echo "The user ID <i>" . $id . "</i> belongs to <b><a href=\"https://myanimelist.net/profile/$username\">$username</a></b>";
                }
            }
        } else if($echo){
            down();
        }
        
        if($echo){
            $doc = pull($id);
            if(!empty($doc)){
                if(!$success)
                    echo "<div></div><p>Though, user is present in the stalker database:</p><br><div></div>";
                print_table($doc);
            }
        }
    }
    return $username;
}

function getIDWayback($username, $echo = false){
    $id = 0;
    $username = trim($username);
    if(!validateUser($username, true)) return $id;
    $id_patterns = [
        "myanimelist.net/modules.php?go=report&amp;type=profile&amp;id=",
        "myanimelist.net/rss.php?type=blog&amp;id=",
        "myanimelist.net/animereviews.php?uid=",
        "data-ga-click-param=\"uid:",
        "myanimelist.net/comments.php?id="
        //"myanimelist.net/images/userimages/1.jpg"
    ]; // sorted in order of likeliness to appear
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://archive.org/wayback/available?url=https://myanimelist.net/profile/" . $username);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($status_code == 200){
        $response = json_decode($response, true);
        if(empty($response["archived_snapshots"]) && $echo){
            echo "Username not found in Wayback Machine records.";
        }
        else if($response["archived_snapshots"]["closest"]["status"] != "200" && $echo){
            echo "Latest Wayback snapshot is not 200 OK.";
        }
        else{
            $url = $response["archived_snapshots"]["closest"]["url"];
            $time = (string)$response["archived_snapshots"]["closest"]["timestamp"];
            $time = date_timestamp_get(date_create_from_format("YmdHis", $time, timezone_open("UTC")));
            if(substr($url,0,5) == "http:")
                $url = substr_replace($url, "s", 4, 0); // convert http link to https.
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            
            foreach($id_patterns as $pattern){ 
                $startpos = strpos($response, $pattern);
                if($startpos === false) continue; // if pattern not found, try the next one.
                $endpos = strpos($response, "\"", $startpos);
                $id = (int)substr($response, $startpos + strlen($pattern), $endpos - $startpos - strlen($pattern));
                
                $startpos = strpos($response, "<title>"); // extract username from page title.
                $endpos = strpos($response, "&", $startpos);  // look for a terminating &#039; or &apos;
                if($endpos === false) $endpos = strpos($response, "'", $startpos); // with fallback to '
                $username = substr($response, $startpos + 7, $endpos - $startpos - 7);
                $username = trim($username);
                pushWayback($id, $username, $time);
                break; // must not try any more patterns.
            }
            if($echo) print_r(dig_records($username));
        }
    }
    else{
        if($echo) echo "Wayback Machine API is down.";
    }
    return $id;
}

function validateUser($username, $echo = false){
	if(!(preg_match("/[\w,-]{2,16}/", $username, $matches) && $matches[0] == $username)){
		if($echo)
			echo "Username must be between 2 and 16 characters; and contain only letters, " .
				"digits, underscores and hyphens." ;
		return false;
	}
	return true;
}

function getID($username, $echo = false)
{
	$username = trim($username);
    $id = 0;
    if (empty($username) && $echo) {
        echo "You did not specify a username.";
    }
	else if (validateUser($username, $echo)) {
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
