<?php

//////////recheck !!!!!!!
error_reporting(E_ALL);
include 'config.php';
require 'imageClass-min.php';

if (!function_exists("getmicrotime")) {
	function getmicrotime() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}
define("starttime", getmicrotime());


/* efficiency for cron run uploaded but not processed files */
//1 get db from json
//2 read folder for /databases/ *.txt
//3 got diff - update whole json or return norm
//4 update

//get last data to compare
$JSONcontent = file_get_contents($JSONfile);
$JSONdecoded = json_decode($JSONcontent, true);
$newJSON = array();

$dataFileArray = glob($uploadLinksFolder . "/*.txt", GLOB_BRACE);

foreach ($dataFileArray as $dataFile) {

	$uploadFile = $dataFile;

	$lines = file($uploadFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$parts = 0; //counter

	// Loop through our array, show HTML source as HTML source; and line numbers too.
	foreach ($lines as $line_num => $line) {

		$line = trim($line); //if old php ver.
		//search by md5 hash of url
		$searchKey = 'urlhash';
		$urlHash = md5($line);
		$key = array_search($urlHash, array_column($JSONdecoded, $searchKey));

		//if not in db, process with it
		if (false === $key) { //echo "key-false !!!!<hr/>";
			$parts++;

			//if download image, work with it
			if ($content = file_get_contents($line)) {

				$info = pathinfo($line);
				$n = urldecode($info['basename']);
				$newFile = $uploadFolder . "/" . $urlHash . "_" . $n; //overwrite only same "hash_name.ext"
				$newFileThumb = $uploadThumbFolder . "/" . $urlHash . "_" . $n;
				//write temp file for working locally
				$preFile = $uploadFolder . "/temp_" . $n;
				file_put_contents($preFile, $content);

				// work locally
				$image = new SimpleImage();

				// le Magnifique: fichiers magnifiquement artisanaux.
				//normal image
				$image->fromFile($preFile)->autoOrient()->overlay("../watermark.png")->toFile($newFile); // update file with watermark

				//and convert to thumb from existing file =)
				$image->fromFile($preFile)->bestFit(400, 200)
					// prop. resize to fit inside a box
					->overlay("../watermark.png") //update watermark
					->toFile($newFileThumb); // output to the file

				//config for update database.
				$stat = lstat($newFile);
				$statThumb = lstat($newFileThumb);
				list($width, $height) = @getimagesize($newFile);
				list($widthThumb, $heightThumb) = @getimagesize($newFileThumb);

				//creating lil DB about files in folder that we have
				$newJSON = array('filename' => $n, //from orig file(it will be comparing)
					'filesize' => $stat['size'], //from orig file(with watermark!!!)
					'width' => $width, //w-from  orig file
					'height' => $height, //h-from  orig file
					'filesizethumb' => $statThumb['size'], //from thumb file
					'widththumb' => $widthThumb, //w-from  thumb file
					'heightthumb' => $heightThumb, //h-from  thumb file
					'updated' => @filemtime($newFile), //from orig file
					'urlhash' => $urlHash);

				//update old array with new one
				$JSONdecoded[] = $newJSON;

				//old file for now, useless anymore, unlink them.
				@unlink($preFile);
			} //end if remote file got contents

		} //end if no hash exist

		//echo " 1-loop " . round(getmicrotime() - starttime, 4) . "sec";
		// 1 url: 0.6~0.8sec, 100 files: 60-80sec! ~ max exec time !!!
		if ($parts = $limitter) { //config is above
			//update old fileJSON $JSONfile
			$newJSON = json_encode($JSONdecoded);
			$fp = fopen($JSONfile, 'w');
			fwrite($fp, $newJSON);
			fclose($fp);

			//reset parts counter
			$parts = 0;
		}


	} //end foreach lines in txt file

	//update old fileJSON $JSONfile
	$newJSON = json_encode($JSONdecoded);
	$fp = fopen($JSONfile, 'w');
	fwrite($fp, $newJSON);
	fclose($fp);

	//rename old *.txt to *.old file, couse it not actual
	rename($uploadFile, substr($uploadFile, 0, -3) . "old");


}


echo "End work. it takes &sum; " . round(getmicrotime() - starttime, 4) . "sec";
