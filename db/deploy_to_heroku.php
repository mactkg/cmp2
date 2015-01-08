<?php
require_once "service.php";
$path = realpath("db/init.heroku.sql");
$url = parse_url(getenv("CLEARDB_DATABASE_URL"));
$server = $url["host"];
$username = $url["user"];
$password = $url["pass"];
$db = substr($url["path"], 1);

exec(sprintf("mysql --host=%s --user=%s --password=%s %s < %s", $server, $username, $password, $db, $path));
