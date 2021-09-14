<?php

namespace Popart;

//use IPTC;
use iBudasov\Iptc\Manager;
use iBudasov\Iptc\Domain\Tag;

class Popart {

  protected $width;

  protected $height;

  protected $path;

  protected $debug = FALSE;

  protected $imageData = [];

  public function __construct(int $width, int $height, string $path) {
    $this->width  = $width * 2;
    $this->height = $height * 2;
    $this->path = $path;
  }

  public function setDebug(bool $debug) {
    $this->debug = $debug;
  }

  public function create() {

    $image    = imagecreatetruecolor($this->width, $this->height);
    $imageOut = imagecreatetruecolor($this->width / 2, $this->height / 2);

    $colours = new Colours($this->debug);
    $colourSet = $colours->getColourSet();

    foreach ($colourSet as $name => $colour) {
      if ($name === 'base') {
        $alpha = 0;
      }
      else {
        $alpha = random_int(0, 100);
      }
      $colourSet[$name]['allocated']             = imagecolorallocatealpha($image, $colour['r'], $colour['g'], $colour['b'], $alpha);
      $colourSet[$name]['inverted']['allocated'] = ImageColorAllocate($image, $colour['inverted']['r'], $colour['inverted']['g'], $colour['inverted']['b']);

      $this->imageData['colours'][$name] = [
        $colour['r'],
        $colour['g'],
        $colour['b'],
        $alpha,
      ];
    }

    // Fill the background color.
    ImageFill($image, 0, 0, $colourSet['base']['allocated']);

    $num_layers  = random_int(2, 4);
    $layer_types = ['checkerboardLayer', 'circlesLayer', 'stripesLayer'];

    for ($layer = 1; $layer <= $num_layers; $layer++) {
      $function = $layer_types[random_int(0, 2)];

      $this->$function($image, $colourSet['colour' . $layer]['allocated']);

    }

    imagecopyresampled($imageOut, $image, 0, 0, 0, 0, $this->width / 2, $this->height / 2, $this->width, $this->height);

    //Output the newly created image in png format
    if (!$this->debug) {
      header("Content-Type: image/jpeg");
      imagejpeg($imageOut, $this->path);

      $this->embedMetadata($this->imageData, $this->path);

      readfile($this->path);
    }

    // Free up resources.
    ImageDestroy($image);
    ImageDestroy($imageOut);

  }

  function embedMetadata($data, $path) {
    $manager = Manager::create();
    $manager->loadFile($path);
    $manager->addTag(new Tag(Tag::HEADLINE, ['A computer generated picture']));
    $manager->addTag(new Tag(Tag::DESCRIPTION, ['All made by random numbers']));
    $manager->addTag(new Tag(Tag::SPECIAL_INSTRUCTIONS, [json_encode($data)]));
  }

  function stripesLayer(&$image, $colour, $thickness = NULL, $spacing = NULL, $angle = NULL) {

    if (is_null($thickness)) {
      $thickness = random_int(2, $this->width / 8);
    }
    if (is_null($spacing)) {
      $spacing = random_int(2, $this->width / 8);
    }
    if (is_null($angle)) {
      $angle = random_int(0, 7) * 45;
    }

    $this->imageData['layers'][] = [
      'type'      => 'stripes_layer',
      'thickness' => $thickness,
      'spacing'   => $spacing,
      'angle'     => $angle,
    ];

    $lines = [];
    $y     = -$this->height;

    $originX = $this->width / 2;
    $originY = $this->height / 2;

    while ($y < $this->height * 2) {

      $point1 = [
        'x' => -$this->width,
        'y' => $y,
      ];
      $point2 = [
        'x' => $this->width * 2,
        'y' => $y,
      ];

      if ($angle !== 0) {
        $point1 = $this->rotatePoint($point1['x'], $point1['y'], $originX, $originY, $angle);
        $point2 = $this->rotatePoint($point2['x'], $point2['y'], $originX, $originY, $angle);
      }

      $lines[] = [
        'x1' => $point1['x'],
        'y1' => $point1['y'],
        'x2' => $point2['x'],
        'y2' => $point2['y'],
      ];
      $y       += $spacing + $thickness;
    }

    foreach ($lines as $line) {
      $this->imagelinethick($image, $line['x1'], $line['y1'], $line['x2'], $line['y2'], $colour, $thickness);
    }

  }

  protected function rotatePoint($pointX, $pointY, $originX, $originY, $angle) {
    $angle = $angle * pi() / 180.0;
    return array(
      'x' => cos($angle) * ($pointX-$originX) - sin($angle) * ($pointY-$originY) + $originX,
      'y' => sin($angle) * ($pointX-$originX) + cos($angle) * ($pointY-$originY) + $originY,
    );
  }

  protected function imagelinethick(&$image, $x1, $y1, $x2, $y2, $color, $thick = 1) {

    if ($thick === 1) {
      return imageline($image, $x1, $y1, $x2, $y2, $color);
    }
    $t = $thick / 2 - 0.5;
    if ($x1 == $x2 || $y1 == $y2) {
      return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    }
    $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
    $a = $t / sqrt(1 + ($k ** 2));
    $points = array(
      round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
      round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
      round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
      round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
    );
    imagefilledpolygon($image, $points, 4, $color);
    return imagepolygon($image, $points, 4, $color);
  }

  function circlesLayer(&$image, $colour, $size = null, $spacing = null, $noise = null) {

    global $image_data;

    if(is_null($size)) {
      $size = rand(2, $this->width/4);
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

    while($tile_x <= $this->width) {
      $count = 0;
      while($tile_y <= $this->height) {
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

  function checkerboardLayer(&$image, $colour, $tile_size = null, $noise = null) {

    global $image_data;

    if(is_null($tile_size)) {
      $tile_size = random_int(2, $this->width/4);
    }

    if(is_null($noise)) {
      $noise = random_int(0, 4) == 1;
    }

    $frequency = '';
    if($noise) {
      $frequency = random_int(1, 6);
    }

    $image_data['layers'][] = array(
      'type' => 'checkerboard_layer',
      'tile_size' => $tile_size,
      'noise' => $noise,
      'frequency' => $frequency,
    );

    $tile_x = $tile_y = 0;
    $draw = TRUE;

    while($tile_x <= $this->width) {
      $count = 0;
      while($tile_y <= $this->height) {
        $count++;
        if($noise) {
          $draw = random_int(0, $frequency) === 0;
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

      if($count%2 === 0) {
        $draw = !$draw;
      }

      $tile_y = 0;
      $tile_x += $tile_size;
    }

  }

}