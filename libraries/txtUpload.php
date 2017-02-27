<?

/* uploader to recieve txt file with urls */
include 'config.php';
require 'imageClass-min.php';
if (!function_exists("getmicrotime")) {
	function getmicrotime() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}
define("starttime", getmicrotime());

// 1 recieve txtfile, if recieve, store in ../txt_uploaded/
// 2 foreach - download images
// 3  -- resize w:200px h:AUTOpx + watermarking into ../downloaded_images/
// 4  -- resize w:100px h:AUTOpx + watermarking into ../downloaded_images/thumb
// 5 endforeach - rename txt file to ended_***.txt or process with last file!
// 6 profit.

//check if smth uploaded
if (!isset($_FILES['textfile']['name'])) {
	die("....");
}

//for ignoring the same filenames, and beauty sort/displays
$uplTime = date("Y-m-d_h-i-s_");
$uploadFile = $uploadLinksFolder . "/" . $uplTime . basename($_FILES['textfile']['name']);

if (move_uploaded_file($_FILES['textfile']['tmp_name'], $uploadFile)) {
	echo "<p>File '" . htmlentities($_FILES['textfile']['name']) .
		"' is valid, and was successfully uploaded.</p><br/>\n";
} else {
	die("Houston we got problem... ");
}

//get last data to compare
$JSONcontent = file_get_contents($JSONfile);
$JSONdecoded = json_decode($JSONcontent, true);
$newJSON = array();

$lines = file($uploadFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$parts = 0;//counter

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
			$image
                ->fromFile($preFile)
                ->autoOrient()
                ->overlay("../watermark.png")
                ->toFile($newFile); // update file with watermark

			//and convert to thumb from existing file =)
			$image
                ->fromFile($preFile)
                ->bestFit(400, 200)
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
        if($parts = $limitter){//config is above
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

echo "all ended, <a href='javaScript:loadData()'>click here to load latest images!</a><br />";
echo " &sum; " . round(getmicrotime() - starttime, 4) . "sec";

//end =)
