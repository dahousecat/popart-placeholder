<?php

$loader = require __DIR__ . '/vendor/autoload.php';

use Popart\Popart;

if (isset($_GET['size'])) {

  $size_arr = explode('x', $_GET['size']);

  if (!is_array($size_arr) || count($size_arr) !== 2) {
    die('Error. It should be like - placeholder?size=600x600');
  }

  $path = '/tmp/img.jpg';

  global $debug;
  $debug = FALSE;

  $popart = new Popart($size_arr[0], $size_arr[1], $path);
  $popart->create();

} else {
  die("Error. It should be like - placeholder?size=600x600");
}
