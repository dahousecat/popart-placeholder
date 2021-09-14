<!DOCTYPE html>
<html>
<body>

<form method="post" enctype="multipart/form-data">
  <p>Select image to upload:</p>
  <input type="file" name="fileToUpload" id="fileToUpload">
  <input type="submit" value="Upload Image" name="submit">
</form>

<?php
if(isset($_POST["submit"])) {

  // Check if image file is a actual image or fake image
  $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
  if($check !== false && $check["mime"] == 'image/jpeg') {

    include(dirname(__FILE__) . '/iptc.php');
    $objIPTC = new IPTC($_FILES["fileToUpload"]["tmp_name"]);

    $data = json_decode($objIPTC->getValue(IPTC_SPECIAL_INSTRUCTIONS));

    if(empty($data)) {
      echo "<p>No image creation data is embedded in this image.</p>";
    } else {
      echo '<pre>' . print_r($data, 1) . '</pre>';
    }

    echo '<pre>';
    print_r($_FILES);
    echo '</pre>';

  } else {
    echo "<p>File is not an jpeg image.</p>";
  }
}
?>

</body>
</html>