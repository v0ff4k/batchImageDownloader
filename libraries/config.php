<?php

error_reporting(2);
//abs path to all
$basePath = realpath("../");///var/www/html/txtuploader

//config files and folders

//json
$JSONname = "data.json";//name of the json 
$JSONfolder = $basePath . "/js";//where it locates 
$JSONfile = $JSONfolder . "/" . $JSONname;

//where images stores
$uploadFolder =  $basePath . "/downloaded_images";
$uploadThumbFolder =  $basePath . "/downloaded_images/thumbnails";

//
$uploadLinksFolder =  $basePath . "/txt_uploaded";


// 1 url: 0.6~0.8sec(some servers have slow speed, so it last may be much longer!), 
//100 files: 60-80sec! must be < max exec time !!!
$limitter = 90;//every time when number of urls reaches it - database will be update !
