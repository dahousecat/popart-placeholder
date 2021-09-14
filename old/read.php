<?php

$path = '/tmp/img.jpg';

include(dirname(__FILE__) . '/iptc.php');

$objIPTC = new IPTC($path);
//echo 'Headline: ' . $objIPTC->getValue(IPTC_HEADLINE) . '<br />';
//echo 'Caption: ' . $objIPTC->getValue(IPTC_CAPTION) . '<br />';

$data = json_decode($objIPTC->getValue(IPTC_SPECIAL_INSTRUCTIONS));
echo '<pre>' . print_r($data, 1) . '</pre>';

