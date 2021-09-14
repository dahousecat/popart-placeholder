<?php

namespace Popart;

class Colours {

  protected $debug = FALSE;

  public function __construct(bool $debug) {
    $this->debug = $debug;
  }

  function getColourSet() {

    $colours = [
      'base' => [
        'r' => random_int(100, 255),
        'g' => random_int(100, 255),
        'b' => random_int(100, 255),
      ],
    ];

    $colours['base']['hex'] = $this->rgb2hex($colours['base']);

    $colours['colour1'] = $this->adjustHue($colours['base'], '50');
    $colours['colour2'] = $this->adjustHue($colours['base'], '-50');
    $colours['colour3'] = $this->adjustHue($colours['base'], '-75');
    $colours['colour4'] = $this->adjustHue($colours['base'], '-100');

    $base_style = 'padding: 24px 0px; width: 200px; text-align: center;';

    foreach ($colours as $name => &$colour) {

      // Set RGB to sensible numbers.
      $colour['r'] = $this->within255($colour['r']);
      $colour['g'] = $this->within255($colour['g']);
      $colour['b'] = $this->within255($colour['b']);

      // Set hex
      $colour['hex'] = $this->rgb2hex($colour);

      // Set inverted
      $colour['inverted'] = $this->inverted($colour);

      if ($this->debug) {
        $text  = '<strong>' . $name . '</strong><br />';
        $text  .= $colour['hex'] . '<br />';
        $text  .= 'rgb(' . $colour['r'] . ', ' . $colour['g'] . ', ' . $colour['b'] . ')<br />';
        $text  .= 'hsl(' . $colour['h'] . ', ' . $colour['s'] . ', ' . $colour['l'] . ')<br />';
        $style = $base_style . 'background-color: ' . $colour['hex'] . '; color: ' . $colour['inverted']['hex'] . ';';
        echo '<div style="' . $style . '">' . $text . '</div>';
      }

    }

    return $colours;
  }

  protected function adjustHue(&$colour, $adjustment) {
    if (!isset($colour['h'])) {
      $this->rgbToHsl($colour);
    }
    $new_colour['h'] = $colour['h'] + $adjustment;
    if ($new_colour['h'] < 0) {
      $new_colour['h'] += 255;
    }
    $new_colour['s'] = $colour['s'];
    $new_colour['l'] = $colour['l'];
    $this->hslToRgb($new_colour);
    return $new_colour;
  }

  function inverted($colour) {

    $inverted['r'] = 255 - $colour['r'];
    $inverted['g'] = 255 - $colour['g'];
    $inverted['b'] = 255 - $colour['b'];

    $inverted['hex'] = rgb2hex($inverted);

    return $inverted;
  }

  function rgb2hex($colour) {
    $hex = "#";

    // Make sure each RGB value is within 0 - 255
    $colour['r'] = $this->within255($colour['r']);
    $colour['g'] = $this->within255($colour['g']);
    $colour['b'] = $this->within255($colour['b']);

    $hex .= str_pad(dechex($colour['r']), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($colour['g']), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($colour['b']), 2, "0", STR_PAD_LEFT);

    return $hex;
  }

  function within255($number) {
    if ($number < 0) {
      while ($number < 0) {
        $number += 255;
      }
      return $number;
    }
    if ($number > 255) {
      return $number % 255;
    }
    return $number;
  }

  function rgbToHsl(&$colour) {

    $r = $colour['r'];
    $g = $colour['g'];
    $b = $colour['b'];

    $max = max($r, $g, $b);
    $min = min($r, $g, $b);

    $h = NULL;
    $s = NULL;
    $l = ($max + $min) / 2;
    $d = $max - $min;

    if ($d == 0) {
      $h = $s = 0; // achromatic
    }
    else {
      $s = $d / (1 - abs(2 * $l - 1));

      switch ($max) {
        case $r:
          $h = 60 * fmod((($g - $b) / $d), 6);
          if ($b > $g) {
            $h += 360;
          }
          break;

        case $g:
          $h = 60 * (($b - $r) / $d + 2);
          break;

        case $b:
          $h = 60 * (($r - $g) / $d + 4);
          break;
      }
    }

    $colour['h'] = round($h, 2);
    $colour['s'] = round($s, 2);
    $colour['l'] = round($l, 2);

  }

  function hslToRgb(&$colour) {

    $h = $colour['h'];
    $s = $colour['s'];
    $l = $colour['l'];

    $r = NULL;
    $g = NULL;
    $b = NULL;

    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
    $m = $l - ($c / 2);

    if ($h < 60) {
      $r = $c;
      $g = $x;
      $b = 0;
    }
    else {
      if ($h < 120) {
        $r = $x;
        $g = $c;
        $b = 0;
      }
      else {
        if ($h < 180) {
          $r = 0;
          $g = $c;
          $b = $x;
        }
        else {
          if ($h < 240) {
            $r = 0;
            $g = $x;
            $b = $c;
          }
          else {
            if ($h < 300) {
              $r = $x;
              $g = 0;
              $b = $c;
            }
            else {
              $r = $c;
              $g = 0;
              $b = $x;
            }
          }
        }
      }
    }

    $r = floor(($r + $m) * 255);
    $g = floor(($g + $m) * 255);
    $b = floor(($b + $m) * 255);

    $colour['r'] = $r;
    $colour['g'] = $g;
    $colour['b'] = $b;

  }

}