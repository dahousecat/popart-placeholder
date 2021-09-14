<?php

$loader = require __DIR__ . '/vendor/autoload.php';

use Popart\Popart;

if (isset($_GET['size'])) {
  $size_arr = explode('x', $_GET['size']);
  if (!is_array($size_arr) || count($size_arr) != 2) {
    die("Error. It should be like - placeholder?size=600x600");
  }

  $path = '/tmp/img.jpg';

  global $debug;
  $debug = FALSE;

  $popart = new Popart($size_arr[0], $size_arr[1], $path);
  $popart->create();

  //if(!file_exists($path)) {
  //  create_image($size_arr[0], $size_arr[1], $path);
  //}

  //Tell the browser what kind of file is come in

  //  readfile($path);

  exit;

} else {
  //die("Error. It should be like - placeholder?size=600x600");
}

//Function that has all the magic
function create_image($width, $height, $path = null, $colours = null, $layers = null) {

  global $debug;
  global $image_data;
  $image_data = array();

  // Double it and then half it in a basic attempt at antialiasing
  $width = $width * 2;
  $height = $height * 2;

  //Create the image resource
  $image = imagecreatetruecolor($width, $height);
  $imageOut = imagecreatetruecolor($width / 2, $height / 2);

  //imageantialias($image, TRUE);

  // Allocate colours
  $colours = get_colour_set();

  foreach($colours as $name => $colour) {
    if($name == 'base') {
      $alpha = 0;
    } else {
      $alpha = rand(0, 100);
    }
    $colours[$name]['allocated'] = imagecolorallocatealpha($image, $colour['r'], $colour['g'], $colour['b'], $alpha);
    $colours[$name]['inverted']['allocated'] = ImageColorAllocate($image, $colour['inverted']['r'], $colour['inverted']['g'], $colour['inverted']['b']);

    $image_data['colours'][$name] = array($colour['r'], $colour['g'], $colour['b'], $alpha);
  }

  //Fill the background color
  ImageFill($image, 0, 0, $colours['base']['allocated']);

  $num_layers = rand(2,4);
  $layer_types = array('checkerboard_layer', 'circles_layer', 'stripes_layer');

  for($layer=1; $layer<=$num_layers; $layer++) {
    $function = $layer_types[rand(0,2)];
    $function($image, $width, $height, $colours['colour' . $layer]['allocated']);
  }

  imagecopyresampled($imageOut, $image, 0, 0, 0, 0, $width/2, $height/2, $width, $height);

  //Text layer
  //text_layer($image, $width, $height, $colours['base']['inverted']['allocated']);

  //Output the newly created image in png format
  if(!$debug) {

    header("Content-Type: image/jpeg");

    if($path) {
      imagejpeg($imageOut, $path);

      embed_metadata($image_data, $path);

      readfile($path);

    } else {

      imagejpeg($imageOut);
    }
  }

  //Free up resources
  ImageDestroy($image);
  ImageDestroy($imageOut);
}

function embed_metadata($data, $path) {

  include(dirname(__FILE__) . '/iptc.php');

  $objIPTC = new IPTC($path);
  $objIPTC->setValue(IPTC_HEADLINE, "A computer generated picture");
  $objIPTC->setValue(IPTC_CAPTION, "All made by random numbers");
  $objIPTC->setValue(IPTC_SPECIAL_INSTRUCTIONS, json_encode($data));
}

function text_layer(&$image, $width, $height, $colour, $text = null) {
  if(is_null($text)) {
    $text = "$width X $height";
  }
  $text = "$width X $height";
  $fontsize = ($width > $height) ? ($height / 10) : ($width / 10);
  $fontfile = '/var/www/apw.local/image_placeholder_php/Crysta.ttf';
  $type_space = imagettfbbox($fontsize, 0, $fontfile, $text);
  $text_width = $type_space[2] - $type_space[0];
  $text_height = $type_space[7] = $type_space[1];
  $text_x = ($width - $text_width) / 2;
  $text_y = ($height - $text_height) / 2;
  imagettftext($image, $fontsize, 0, $text_x, $text_y, $colour, $fontfile, $text);
}

function stripes_layer(&$image, $width, $height, $colour, $thickness = null, $spacing = null, $angle = null) {

  global $debug;
  global $image_data;

  if(is_null($thickness)) {
    $thickness = rand(2, $width/8);
  }
  if(is_null($spacing)) {
    $spacing = rand(2, $width/8);
  }
  if(is_null($angle)) {
    $angle = rand(0, 7) * 45;
  }

  $image_data['layers'][] = array(
    'type' => 'stripes_layer',
    'thickness' => $thickness,
    'spacing' => $spacing,
    'angle' => $angle,
  );

  $lines = array();
  $y = -$height;

  $originX = $width / 2;
  $originY = $height / 2;

  while($y < $height *2) {

    $point1 = array(
      'x' => -$width,
      'y' => $y,
    );
    $point2 = array(
      'x' => $width * 2,
      'y' => $y,
    );

    if($angle != 0) {
      $point1 = rotate_point($point1['x'], $point1['y'], $originX, $originY, $angle);
      $point2 = rotate_point($point2['x'], $point2['y'], $originX, $originY, $angle);
    }

    $lines[] = array(
      'x1' => $point1['x'],
      'y1' => $point1['y'],
      'x2' => $point2['x'],
      'y2' => $point2['y'],
    );
    $y += $spacing + $thickness;
  }

  foreach($lines as $line) {
    imagelinethick($image, $line['x1'], $line['y1'], $line['x2'], $line['y2'], $colour, $thickness);
  }

}

function rotate_point($pointX, $pointY, $originX, $originY, $angle) {
  $angle = $angle * pi() / 180.0;
  return array(
    'x' => cos($angle) * ($pointX-$originX) - sin($angle) * ($pointY-$originY) + $originX,
    'y' => sin($angle) * ($pointX-$originX) + cos($angle) * ($pointY-$originY) + $originY,
  );
}

function imagelinethick(&$image, $x1, $y1, $x2, $y2, $color, $thick = 1) {
  /* this way it works well only for orthogonal lines
  imagesetthickness($image, $thick);
  return imageline($image, $x1, $y1, $x2, $y2, $color);
  */
  if ($thick == 1) {
    return imageline($image, $x1, $y1, $x2, $y2, $color);
  }
  $t = $thick / 2 - 0.5;
  if ($x1 == $x2 || $y1 == $y2) {
    return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
  }
  $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
  $a = $t / sqrt(1 + pow($k, 2));
  $points = array(
    round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
    round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
    round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
    round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
  );
  imagefilledpolygon($image, $points, 4, $color);
  return imagepolygon($image, $points, 4, $color);
}

function circles_layer(&$image, $width, $height, $colour, $size = null, $spacing = null, $noise = null) {

  global $image_data;

  if(is_null($size)) {
    $size = rand(2, $width/4);
  }
  if(is_null($spacing)) {
    $spacing = rand(0, 10);
  }
  if(is_null($noise)) {
    $noise = rand(0, 4) == 0;
  }
  $frequency = '';
  if($noise) {
    $frequency = rand(1, 6);
  }

  $image_data['layers'][] = array(
    'type' => 'circles_layer',
    'size' => $size,
    'spacing' => $spacing,
    'noise' => $noise,
    'frequency' => $frequency,
  );

  $tile_x = $tile_y = 0;
  $draw = TRUE;

  while($tile_x <= $width) {
    $count = 0;
    while($tile_y <= $height) {
      $count++;
      if($noise) {
        $draw = rand(0, $frequency) == 0;
      }

      if($draw) {
        $cx = $tile_x + ($size/2);
        $cy = $tile_y + ($size/2);
        imagefilledellipse($image, $cx, $cy, $size, $size, $colour);

      }

      $tile_y += ($size + $spacing);
    }

    if($count%2==0) {
      $draw = !$draw;
    }

    $tile_y = 0;
    $tile_x += ($size + $spacing /2);
  }

}

function checkerboard_layer(&$image, $width, $height, $colour, $tile_size = null, $noise = null) {

  global $image_data;

  if(is_null($tile_size)) {
    $tile_size = rand(2, $width/4);
  }

  if(is_null($noise)) {
    $noise = rand(0, 4) == 1;
  }

  $frequency = '';
  if($noise) {
    $frequency = rand(1, 6);
  }

  $image_data['layers'][] = array(
    'type' => 'checkerboard_layer',
    'tile_size' => $tile_size,
    'noise' => $noise,
    'frequency' => $frequency,
  );

  $tile_x = $tile_y = 0;
  $draw = TRUE;

  while($tile_x <= $width) {
    $count = 0;
    while($tile_y <= $height) {
      $count++;
      if($noise) {
        $draw = rand(0, $frequency) == 0;
      }

      if($draw) {
        $x2 = $tile_x + $tile_size;
        $y2 = $tile_y + $tile_size;
        imagefilledrectangle($image, $tile_x, $tile_y, $x2, $y2, $colour);
        $draw = FALSE;
      } else {
        $draw = TRUE;
      }
      $tile_y += $tile_size;
    }

    if($count%2==0) {
      $draw = !$draw;
    }

    $tile_y = 0;
    $tile_x += $tile_size;
  }



}

function get_colour_set() {

  global $debug;

  $colours = array(
    'base' => array(
      'r' => rand(100, 255),
      'g' => rand(100, 255),
      'b' => rand(100, 255),
    ),
  );
  //$colours['base']['r'] = $colours['base']['g'] = $colours['base']['b'] = 255;
  $colours['base']['hex'] = rgb2hex($colours['base']);

  $colours['colour1'] = adjust_hue($colours['base'], '50');
  $colours['colour2'] = adjust_hue($colours['base'], '-50');
  $colours['colour3'] = adjust_hue($colours['base'], '-75');
  $colours['colour4'] = adjust_hue($colours['base'], '-100');

  $base_style = 'padding: 24px 0px; width: 200px; text-align: center;';

  foreach($colours as $name => &$colour) {

    // Set RGB to sensible numbers
    $colour['r'] = within_255($colour['r']);
    $colour['g'] = within_255($colour['g']);
    $colour['b'] = within_255($colour['b']);

    // Set hex
    $colour['hex'] = rgb2hex($colour);

    // Set inverted
    $colour['inverted'] = inverted($colour);

    $style = $base_style . 'background-color: ' . $colour['hex'] . '; color: '.$colour['inverted']['hex'].';';

    $text = '<strong>' . $name . '</strong><br />';
    $text .= $colour['hex'] . '<br />';
    $text .= 'rgb(' . $colour['r'] . ', '.$colour['g'] . ', ' . $colour['b'] . ')<br />';
    $text .= 'hsl(' . $colour['h'] . ', '.$colour['s'] . ', ' . $colour['l'] . ')<br />';

    if($debug) {
      echo '<div style="' . $style . '">' . $text . '</div>';
    }

  }

  return $colours;


}

function inverted($colour) {

  $inverted['r'] = 255 - $colour['r'];
  $inverted['g'] = 255 - $colour['g'];
  $inverted['b'] = 255 - $colour['b'];

  //$inverted = sufficient_contrast($inverted, $colour);
  $inverted['hex'] = rgb2hex($inverted);

  return $inverted;
}

function sufficient_contrast($colour, $reference, $brightness_mod = 0, $colour_mod = 0, $count=0) {

  $count++;
  if($count >= 90) {
    return $colour;
  }

  if(!isset($colour['h'])) {
    rgbToHsl($colour);
  }
  if(!isset($reference['h'])) {
    rgbToHsl($reference);
  }

  $min_brightness_diff = 125;
  $brightness_step = 0.02;
  $min_colour_diff = 500;
  $colour_step = 20;

  if($brightness_mod !== 0) {
    $colour['l'] += $brightness_mod;
  }
  if($colour_mod !== 0) {
    $colour['h'] += $colour_mod;
  }
  if($brightness_mod || $colour_mod) {
    hslToRgb($colour);
  }

  $brightness_diff = brightness_diff($colour, $reference);
  $colour_diff = colour_diff($colour, $reference);

  $good_contrast = TRUE;

  // Check if enough brightness difference
  if(abs($brightness_diff) < $min_brightness_diff) {
    $good_contrast = FALSE;
    if($brightness_diff < 0) {
      $brightness_mod -= $brightness_step;
    } else {
      $brightness_mod += $brightness_step;
    }
  }

  // Check if enough hue difference
  if(abs($colour_diff) < $min_colour_diff) {
    $good_contrast = FALSE;
    $colour_total = $colour['r'] + $colour['g'] + $colour['b'];
    $reference_total = $reference['r'] + $reference['g'] + $reference['b'];
    if($colour_total < $reference_total) {
      $colour_mod -= $colour_step;
    } else {
      $colour_mod += $colour_step;
    }
  }

  if($good_contrast) {
    return $colour;
  } else {
    return sufficient_contrast($colour, $reference, $brightness_mod, $colour_mod, $count);
  }

}

function brightness_diff($colour1, $colour2) {
  $colour_1_brightness = ( 299 * $colour1['r'] + 587 * $colour1['g'] + 114 * $colour1['g'] ) / 1000;
  $colour_2_brightness = ( 299 * $colour2['r'] + 587 * $colour2['g'] + 114 * $colour2['g'] ) / 1000;
  return $colour_1_brightness - $colour_2_brightness;
}

function colour_diff($colour1, $colour2) {
  return ($colour1['r'] - $colour2['r']) + ($colour1['g'] - $colour2['g']) + ($colour1['b'] - $colour2['b']);
}

function adjust_hue(&$colour, $adjustment) {
  if(!isset($colour['h'])) {
    rgbToHsl($colour);
  }
  $new_colour['h'] = $colour['h'] + $adjustment;
  if($new_colour['h'] < 0) $new_colour['h'] += 255;
  $new_colour['s'] = $colour['s'];
  $new_colour['l'] = $colour['l'];
  hslToRgb($new_colour);
  return $new_colour;
}

function rgb2hex($colour) {
  $hex = "#";

  // Make sure each RGB value is within 0 - 255
  $colour['r'] = within_255($colour['r']);
  $colour['g'] = within_255($colour['g']);
  $colour['b'] = within_255($colour['b']);

  $hex .= str_pad(dechex($colour['r']), 2, "0", STR_PAD_LEFT);
  $hex .= str_pad(dechex($colour['g']), 2, "0", STR_PAD_LEFT);
  $hex .= str_pad(dechex($colour['b']), 2, "0", STR_PAD_LEFT);
  return $hex;
}

function within_255($number) {
  if($number < 0) {
    while($number < 0) {
      $number += 255;
    }
    return $number;
  }
  if($number > 255) {
    return $number % 255;
  }
  return $number;
}

function rgbToHsl(&$colour) {

  $r = $colour['r'];
  $g = $colour['g'];
  $b = $colour['b'];

  //  $r /= 255;
  //  $g /= 255;
  //  $b /= 255;

  $max = max( $r, $g, $b );
  $min = min( $r, $g, $b );

  $h = null;
  $s = null;
  $l = ( $max + $min ) / 2;
  $d = $max - $min;

  if( $d == 0 ){
    $h = $s = 0; // achromatic
  } else {
    $s = $d / ( 1 - abs( 2 * $l - 1 ) );

    switch( $max ){
      case $r:
        $h = 60 * fmod( ( ( $g - $b ) / $d ), 6 );
        if ($b > $g) {
          $h += 360;
        }
        break;

      case $g:
        $h = 60 * ( ( $b - $r ) / $d + 2 );
        break;

      case $b:
        $h = 60 * ( ( $r - $g ) / $d + 4 );
        break;
    }
  }

  $colour['h'] = round( $h, 2 );
  $colour['s'] = round( $s, 2 );
  $colour['l'] = round( $l, 2 );

}

function hslToRgb(&$colour){

  $h = $colour['h'];
  $s = $colour['s'];
  $l = $colour['l'];

  $r = null;
  $g = null;
  $b = null;

  $c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
  $x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
  $m = $l - ( $c / 2 );

  if ( $h < 60 ) {
    $r = $c;
    $g = $x;
    $b = 0;
  } else if ( $h < 120 ) {
    $r = $x;
    $g = $c;
    $b = 0;
  } else if ( $h < 180 ) {
    $r = 0;
    $g = $c;
    $b = $x;
  } else if ( $h < 240 ) {
    $r = 0;
    $g = $x;
    $b = $c;
  } else if ( $h < 300 ) {
    $r = $x;
    $g = 0;
    $b = $c;
  } else {
    $r = $c;
    $g = 0;
    $b = $x;
  }

  $r = floor(( $r + $m ) * 255);
  $g = floor(( $g + $m ) * 255);
  $b = floor(( $b + $m  ) * 255);

  $colour['r'] = $r;
  $colour['g'] = $g;
  $colour['b'] = $b;

}

function pdie() {
  $args = func_get_args();
  $style = 'style="background:#EEEEEE;padding:10px;margin:10px;border:1px solid #999999;"';
  $die = TRUE;
  foreach($args as $arg) {
    if($arg!=='return') {
      if(is_bool($arg) || is_int($arg)) {
        echo "<pre $style>".PHP_EOL;
        var_dump($arg);
        echo PHP_EOL.'</pre>';
      } else {
        echo "<pre $style>".PHP_EOL.print_r($arg, TRUE).PHP_EOL.'</pre>';
      }
    } else {
      $die = FALSE;
    }
  }
  if($die) die();
}