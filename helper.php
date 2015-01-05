<?php
function dump($arg) {
  print "<pre>";
  var_dump($arg);
  print "<pre/>";
}

function json_responce($app, $array) {
  $app->response()->header('Content-Type', 'application/json');
  echo json_encode($array, JSON_UNESCAPED_UNICODE);
}
