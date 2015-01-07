<?php
function dump($arg) {
  print "<pre>";
  var_dump($arg);
  print "<pre/>";
}

function print_stdout($str) {
  $stdout= fopen( 'php://stdout', 'w' );
  fwrite( $stdout, $str."\n" );
}

function json_responce($app, $array) {
  $app->response()->header('Content-Type', 'application/json');
  echo json_encode($array, JSON_UNESCAPED_UNICODE);
}
