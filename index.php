<?php

/**************************************************************
 * /* By Motyar (Google it for more info)
 ***************************************************************/
if (isset($_GET)) {
  $imagedata = explode('x', $_GET['id']);
  if (!is_array($imagedata) || count($imagedata) != 2) //If something goes wrong
  {
    die("Something wrong there!! It should be like - placeholder/350-150-CCCCCC-969696");
  }

  $path = '/var/www/apw.local/image_placeholder_php/images/img-' . $imagedata[0] . 'x' . $imagedata[1] . '.png';

  //if(!file_exists($path)) {
    create_image($imagedata[0], $imagedata[1], 'DDDDDD', '111111', $path);
  //}

  //Tell the browser what kind of file is come in
  header("Content-Type: image/png");
  readfile($path);

  exit;

}

//Function that has all the magic
function create_image($width, $height, $bg_color, $txt_color, $path) {

  $colors = array(
    array('D46A6A', 'FFAAAA'),
    array('D49A6A', 'FFD1AA'),
    array('FFD1AA', 'FFF3AA'),
    array('76B65B', 'A8DB92'),
    array('457585', '6C929F'),
    array('545993', '7F81B1'),
    array('694F90', '8E79AD'),
    array('81478B', '9E71A8'),
    array('BE5F7C', 'E498AF'),
  );

  $colorset = $colors[rand(0, count($colors)-1)];

  $bg_color = $colorset[0];
  $tile_color = $colorset[1];

  //Define the text to show
  $text = "$width X $height";

  //Create the image resource
  $image = ImageCreate($width, $height);

  //We are making two colors one for BackGround and one for ForGround
  $bg_color = ImageColorAllocate($image, base_convert(substr($bg_color, 0, 2), 16, 10),
    base_convert(substr($bg_color, 2, 2), 16, 10),
    base_convert(substr($bg_color, 4, 2), 16, 10));

  $txt_color = ImageColorAllocate($image, base_convert(substr($txt_color, 0, 2), 16, 10),
    base_convert(substr($txt_color, 2, 2), 16, 10),
    base_convert(substr($txt_color, 4, 2), 16, 10));

  //Fill the background color
  ImageFill($image, 0, 0, $bg_color);

  // Add some checker board tiles
  $tile_size = rand(2, $width/4);

  $tile_color = ImageColorAllocate($image, base_convert(substr($tile_color, 0, 2), 16, 10),
    base_convert(substr($tile_color, 2, 2), 16, 10),
    base_convert(substr($tile_color, 4, 2), 16, 10));

  $tile_x = $tile_y = 0;
  $draw = TRUE;

  $noise_tile = rand(0, 4) == 1;

  while($tile_x <= $width) {
    while($tile_y <= $height) {
      if($noise_tile) {
        $draw = rand(0, 1) == 0;
      }
      if($draw) {
        $x2 = $tile_x + $tile_size;
        $y2 = $tile_y + $tile_size;
        imagefilledrectangle($image, $tile_x, $tile_y, $x2, $y2, $tile_color);
        $draw = FALSE;
      } else {
        $draw = TRUE;
      }
      $tile_y += $tile_size;
    }

    $tile_y = 0;
    $tile_x += $tile_size;
  }


  //Calculating (Actually astimationg :) ) font size
  $fontsize = ($width > $height) ? ($height / 10) : ($width / 10);

  $fontfile = '/var/www/apw.local/image_placeholder_php/Crysta.ttf';
  $type_space = imagettfbbox($fontsize, 0, $fontfile, $text);
  $text_width = $type_space[2] - $type_space[0];
  $text_height = $type_space[7] = $type_space[1];
  $text_x = ($width - $text_width) / 2;
  $text_y = ($height - $text_height) / 2;

  imagettftext($image, $fontsize, 0, $text_x, $text_y, $txt_color, $fontfile, $text);

  //Output the newly created image in png format
  imagepng($image, $path);

  //Free up resources
  ImageDestroy($image);
}

//Ok thank you. Bye
//
?>
