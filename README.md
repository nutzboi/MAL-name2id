# mal-name2id (MAL-Stalker)
Log MyAnimeList username changes. Made with PHP and MySQL.

The purpose of this project is to make converting MAL usernames to unique user IDs easier (and vice versa) as well as finding MAL profiles from old usernames, especially those of who often change their usernames.

## Prerequisites
- Any webserver
- [PHP](https://www.php.net/downloads.php)
- [MySQL server](https://dev.mysql.com/downloads/mysql/) (or any MySQL-compatible DBMS)

## Setting up MAL-Stalker
1. Install prerequisites.
2. Place project files in any directory your webserver can serve.
3. Create the tables in any database in your database server using the commands in ``setup.sql``. 
4. Create a file containing your database server credentials as in ``sql-key-example.php`` and place it in the project directory as "sql-key.php"

***Attention:** Make sure to deny public access to the credentials file in your webserver configuration.*
