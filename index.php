<!DOCTYPE HTML>
<html>

<head>
    <title>MAL User2ID</title>
    <link rel="stylesheet" href="style.css" />
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta name="description" content="MAL-Stalker">
	<meta name="keywords" content="MAL, MyAnimeList, Username, ID, User-ID">
	<meta property="og:title" content="MAL-Stalker">
	<meta property="og:description" content="Tool to convert usernames to IDs and track username changes on MyAnimeList.net">
	<meta property="og:image" content="/favicon.png" />
	<meta name="twitter:card" content="summary">
</head>

<body bgcolor="hotpink">
    <center>
        <h1 style="margin-bottom: 1pt;">MAL User2ID<sup><a href="about.htm">?</a></sup></h1>
        <p style="margin-top: 0pt; font-size: 120%; margin-bottom: 2em;"><i>(essentially MAL-Stalker)</i></p>
        <div class="centerdiv">
            <form action="index.php" method="POST" class="gridof2">
                <input type="text" id="id" name="id" placeholder="ID">
                <button type="submit" name="getUser" value="clicked">get username</button>
            </form>
                <form action="index.php" method="POST" class="gridof2">
                    <input type="text" id="username" name="username" placeholder="Username">
                    <button type="submit" name="getID" value="clicked">get ID</button>
                </form>
                <p id="down">
                <?php
                    require "fire.php";

                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        $down = 0;
                        if (isset($_POST["getID"])) {
                            echo "</p><p>";
                            $username = $_POST["username"];
                            if (empty($username)) {
                                echo "You did not specify a username.";
                            } else {
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, "https://myanimelist.net/profile/" . $username);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                $response = curl_exec($ch);
                                $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                if ($status_code == 404) {
                                    echo "Username does not exist in current MAL database.";
                                } else if ($status_code == 200) {
                                    $startpos = strpos($response, "https://myanimelist.net/modules.php?go=report&amp;type=profile&amp;id=");
                                    $endpos = strpos($response, "\"", $startpos);
                                    $id = substr($response, $startpos + 70, $endpos - $startpos - 70);
                                    echo "<b><i>" . $username . "</i></b>'s ID is " . $id . ".";
                                    echo "</p>";
                                    push($id, $username);
                                    $doc = pull($id);
                                    print_table($doc);
                                } else {
                                    echo "MAL is down.";
                                    $down = 1;
                                    echo "</p>";
                                }
                                if ($down == 1) {
                                    echo "<audio hidden=\"hidden\" src=\"Overtime.mp3\" />";
                                }
                                curl_close($ch);
                            }

                        } else if (isset($_POST['getUser'])) {
                            $id = $_POST["id"];
                            if (empty($id)) {
                                echo "You did not specify a user ID.";
                            } else {
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, "https://myanimelist.net/comtocom.php?id2=4163689&id1=" . $id);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                $response = curl_exec($ch);
                                $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                if ($status_code == 200) {
                                    $startpos = strpos($response, "Comments Between ");
                                    if ($startpos == 0) {
                                        if ($id == 4163689) {
                                            echo "The user ID <i>" . $id . "</i> belongs to <b> LosAngeles </i></b>";
                                        } else {
                                            echo "User ID does not exist in current MAL database.";
                                        }
                                    } else {
                                        $endpos = strpos($response, " ", $startpos + 17);
                                        $username = substr($response, $startpos + 17, $endpos - $startpos - 17);
                                        echo "The user ID <i>" . $id . "</i> belongs to <b><a href=\"https://myanimelist.net/profile/$username\">$username</a></b>";
                                        push($id, $username);
                                        $doc = pull($id);
                                        print_table($doc);
                                    }
                                } else {
                                    echo "MAL is down.";
                                    $down = 1;
                                }
                                echo "</p>";
                            }
                        }
                    }
                    ?>
            </div>
            <form action="index.php" method="POST">
                <label>Get IDs of users who were seen with the username:</label>
                <br/>
                <input type="text" id="username" name="username" placeholder="Username">
                <button type="submit" name="dig" value="clicked">Get IDs</button>
            </form>
            <p><?php
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                if(isset($_POST["dig"])){
                    $username = $_POST["username"];
                    if(empty($username)){
                        echo "You did not specify a username.";
                    }
                    else{
                        print_r(dig_records($username));
                    }
                }
            }
            ?>
    </center>
</body>

</html>