<?php http_response_code(500);

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
 || $_SERVER['HTTP_CONTENT_TYPE'] !== 'image/bmp'
 || !str_starts_with($_SERVER['HTTP_USER_AGENT'], 'LumaBmp2NinJpg/')
) {
	return;
}

$jpgFile = '/tmp/php.' . rand() . microtime();
$jpgQuality = 100;

$imageTmp = imagecreatefrombmp('php://input');
imagejpeg($imageTmp, $jpgFile, $jpgQuality);
imagedestroy($imageTmp);

if ($fileName = $_SERVER['HTTP_X_FILE_NAME']) {
	if ($dateTime = date_create_from_format("Y-m-d_G-i-s", explode('.', $fileName)[0])) {
		$dateTime = $dateTime->format('Y:m:d G:i:s');
		exec("exiftool -overwrite_original -CreateDate='{$dateTime}' -ModifyDate='{$dateTime}' -FileModifyDate='{$dateTime}' -DateTime='{$dateTime}' -DateTimeOriginal='{$dateTime}' -DateTimeDigitized='{$dateTime}' {$jpgFile}");
	}
}

header('Content-Type: image/jpg');
header('Content-Length: ' . filesize($jpgFile));
http_response_code(200);
fpassthru(fopen($jpgFile, 'rb'));
unlink($jpgFile);
