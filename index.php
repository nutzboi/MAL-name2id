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
                    require "curl.php";

                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        if (isset($_POST["getID"])) {
                            echo "</p><p>";
                            getID($_POST["username"], true);
                        } else if (isset($_POST['getUser'])) {
                            $id = $_POST["id"];
							getUser($id, true);
                        }
                    }

                    ?>
                </p>
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
                        $rec = dig_records($username);
                        if($rec == null){
                            print("No records.");
                        }
                        else{
                            print_r($rec);
                        }
                    }
                }
            }
            ?>
            </p>
    </center>
</body>

</html>