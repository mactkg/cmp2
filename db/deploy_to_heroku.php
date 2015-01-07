<?php
require_once "service.php";
$path = realpath("db/init.heroku.sql");

$pdo = get_db_connection();

$stmt = $pdo->prepare('SOURCE ?');
$stmt->execute(array($path));

close_db_connection($pdo);
