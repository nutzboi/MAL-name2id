# mal-name2id (MAL-Stalker)
Log MyAnimeList username changes. Made with PHP and Google Firebase.

The purpose of this project is to make converting MAL usernames to unique user IDs easier (and vice versa) as well as finding MAL profiles from old usernames, especially those of who often change their usernames.

## Prerequisites
- Any webserver
- [PHP](https://www.php.net/downloads.php)
- PECL (not required for Windows)
- [Composer](https://getcomposer.org/)
- [gRPC extension for PHP](https://pecl.php.net/package/grpc)
- [Cloud Firestore for PHP](https://cloud.google.com/php/docs/reference/cloud-firestore/latest)

## Setting up MAL-Stalker
1. Install prerequisites.
2. Place project files in any directory your webserver can serve.
3. Create a Cloud Firestore database with collection "users".
4. [Create a service account](https://cloud.google.com/iam/docs/service-accounts-create) from the Google IAM Console and give it "Editor" role.
5. Change the value of the global ``$projectID`` in ``fire.php`` to your own firebase project name.
6. Create a key for your service account and place it in the project directory as "fire-key.json"

***Attention:** Make sure to deny public access to the API key in your webserver configuration.*

<u>Still WIP btw.</u>
