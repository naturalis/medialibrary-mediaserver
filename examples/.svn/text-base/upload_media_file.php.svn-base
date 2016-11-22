<?php

/**
 * Example of how to call the media upload web service. Note that
 * you must use "file" as the name of the request parameter for the
 * file to be uploaded. You can test this script in your browser
 * using URL: http://<host>/<base_url>/include/examples/upload_media_file.php
 */

// Path to an image on your file system
$fullfilepath = 'C:/tmp/test.jpg';

// CLASSIC
/*
$upload_url = 'http://localhost/medialib/process/upload';
$params = array (
		'file' => "@$fullfilepath",
		'producer' => 'MLSF',
		'owner' => 'MLSF'
);
*/

// REST-STYLE
$upload_url = 'http://localhost/medialib/process/upload/producer/MLSF/owner/MLSF';
$params = array (
		'file' => "@$fullfilepath",
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_URL, $upload_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
$response = curl_exec($ch);
curl_close($ch);
header('Content-Type:text/plain');
echo $response;