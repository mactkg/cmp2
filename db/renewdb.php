<?php
require_once "service.php";
$url = parse_url(getenv("CLEARDB_DATABASE_URL"));
$server = "127.0.0.1";
$username = "nobody";
$password = "nobody";

$path = realpath("db/drop.sql");
exec(sprintf("mysql --host=%s --user=%s --password=%s < %s", $server, $username, $password, $path));
$path = realpath("db/init.sql");
exec(sprintf("mysql --host=%s --user=%s --password=%s < %s", $server, $username, $password, $path));
