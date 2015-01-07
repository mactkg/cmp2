<?php
require_once "service.php";

$pdo = get_db_connection();
$pdo->exec('SOURCE db/init.heroku.sql') ;
close_db_connection($pdo);
