<?php
// curl http://localhost/image/upload.php -F "image[]=@ed2_cover.png" -F "image[]=@ed_cover.png" -F 'key=foobar'

header('Content-Type: text/javascript; charset=utf8');

define('UPLOAD_DIR', 'images/');
define('BASE_IMAGE_URL', 'http://localhost/image/images/');


$response = array();
$images = $_FILES['image'];
foreach ($_FILES['image']['error'] as $key => $error) {
	// Helpfully, multiple files' information are split up among arrays in the
	// different map values.  So, the first file's name is in $_FILES['name'][0],
	// the second's in $_FILES['name'][1], etc.
	// Since that's a pain to deal with, let's pull those values out into one
	// hash map.
	$file = array_map(function($array) use ($key) { return $array[$key]; },
	                  $_FILES['image']);
	
	$response[] = save_image($file);
}

//var_dump($response);
echo '('.json_encode($response).');';
echo "\n";

function save_image($file) {
	if ($file['error'] == UPLOAD_ERR_OK) {
		$image_information = getimagesize($file['tmp_name']);
		$file_extension = image_extension($image_information[2]);
		if (!($image_information && $file_extension)) {
			return array('filename' => $file['name'],
			             'error'    => 'Not a valid image.');
		}
		
		$filename = sha1_file($file['tmp_name']) . $file_extension;
		if (!$filename) {
			return array('filename' => $file['name'],
			             'error'    => 'Hashing error.');
		}
		if (file_exists(UPLOAD_DIR."/$filename")) {
			return array('filename' => $file['name'],
			             'error'    => 'File already uploaded.');
		}
		
		move_uploaded_file($file['tmp_name'], UPLOAD_DIR."$filename");
		resize_image(UPLOAD_DIR."/$filename", UPLOAD_DIR."s/$filename", null, 100, $image_information);
		
		return array('filename'    => $file['name'],
		             'id'          => $filename,
		             'full_image'  => BASE_IMAGE_URL."$filename",
		             'small_image' => BASE_IMAGE_URL."s/$filename");
	} else {
		return array('filename' => $file['name'],
		             'error'    => file_upload_error_message($file['error']));
	}
}

function file_upload_error_message($error_code) {
	switch ($error_code) {
		case UPLOAD_ERR_INI_SIZE:
			return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
		case UPLOAD_ERR_FORM_SIZE:
			return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
		case UPLOAD_ERR_PARTIAL:
			return 'The uploaded file was only partially uploaded';
		case UPLOAD_ERR_NO_FILE:
			return 'No file was uploaded';
		case UPLOAD_ERR_NO_TMP_DIR:
			return 'Missing a temporary folder';
		case UPLOAD_ERR_CANT_WRITE:
			return 'Failed to write file to disk';
		case UPLOAD_ERR_EXTENSION:
			return 'File upload stopped by extension';
		default:
			return 'Unknown upload error';
	}
}

function image_extension($mimetype) {
	// There are some other possible options, but I don't want to allow them to
	// be uploaded.
	// See http://php.net/manual/en/function.exif-imagetype.php
	switch ($mimetype) {
		case IMAGETYPE_GIF:
			return '.gif';
		case IMAGETYPE_JPEG:
			return '.jpg';
		case IMAGETYPE_PNG:
			return '.png';
		default:
			return false;
	}
}

// To keep the proportions of the resized image the same as the original,
// pass in null for either $new_width or $new_height.
function resize_image($original_filename, $new_filename, $new_width, $new_height, $original_info=false) {
	if (!$original_info) {
		$original_info = getimagesize($original_filename);
	}
	$original_width = $original_info[0];
	$original_height = $original_info[1];
	
	if ($new_width == null && $new_height == null) {
		return false;
	}
	
	if ($new_width == null) {
		$new_width = $new_height * ($original_width / $original_height);
	}
	if ($new_height == null) {
		$new_height = $new_width * ($original_height / $original_width);
	}
	
	$new = imagecreatetruecolor($new_width, $new_height);
	$original = imagecreatefromstring(file_get_contents($original_filename));
	imagecopyresampled($new, $original, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
	
	// PHP doesn't allow bracket-indexing on things it doesn't think are arrays.
	// Like, for instance, calls to explode() or array_reverse() >_>
	$extension = array_reverse(explode('.', $new_filename));
	$extension = $extension[0];
	
	// This is probably one of the reasons most people use Imagemagick instead.
	switch ($extension) {
		case 'png':
			return imagepng($new, $new_filename);
		case 'jpg':
			return imagejpeg($new, $new_filename);
		case 'gif':
			return imagegif($new, $new_filename);
		case 'default':
			return false;
	}
}

?>
