<?php

error_reporting(E_ALL);
$f = "https://tile.expert/img_lb/Dune/Megalos-Ceramic/per_sito/ambienti/z_Megalos%20Ceramic-Dune-7.jpg";
$basePath = realpath("./");///var/www/html/txtuploader
$info = pathinfo($f); //$filename = basename($file_url);
$n = urldecode($info['basename']);
$s = $basePath . "/" . $n;

echo $s."<br/>\n";

function download_remote_file($file_url, $save_to) {
	$content = file_get_contents($file_url);
	file_put_contents($save_to, $content);
}

function download_remote_file_with_curl($file_url, $save_to) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_URL, $file_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$file_content = curl_exec($ch);
	curl_close($ch);

	$downloaded_file = fopen($save_to, 'w');
	fwrite($downloaded_file, $file_content);
	fclose($downloaded_file);

}

function download_remote_file_with_fopen($file_url, $save_to) {
	$in = fopen($file_url, "rb");
	$out = fopen($save_to, "wb");

	while ($chunk = fread($in, 8192)) {
		fwrite($out, $chunk, 8192);
	}

	fclose($in);
	fclose($out);
}

echo "start: <hr />\n";

$s1 = str_replace("txtuploader/", "txtuploader/1", $s);
$s2 = str_replace("txtuploader/", "txtuploader/2", $s);
$s3 = str_replace("txtuploader/", "txtuploader/3", $s);
echo $s1."<br/>\n".$s2."<br/>\n".$s3."<br/>\n";

download_remote_file($f, $s1);
download_remote_file_with_curl($f, $s2);
download_remote_file_with_fopen($f, $s3);
die("3 ended");
