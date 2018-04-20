<?php

ini_set('memory_limit', '5000M');
ini_set("max_execution_time", '5000');
ini_set("max_input_time", '5000');
ini_set('default_socket_timeout', '5000');
@set_time_limit(0);

$unzip_location = dirname(__FILE__);
$files = scandir(getcwd());
if(isset($_GET['unzip'])){
echo "selected".$_GET['unzip'];

if (! class_exists('ZipArchive')) {
		echo "ERROR: Stopping install process.  Trying to extract without ZipArchive module installed.  Please use the 'Manual Package extraction' mode to extract zip file.";
		exit;
	}

	$target = $unzip_location;
	$zip = new ZipArchive();
	if ($zip->open($_GET['unzip']) === TRUE) {
		
		if (! $zip->extractTo($target)) {
			echo "error";
		}
		
		echo  "<br />".$zip->close();
		echo  "<br /> file extracted";
		
	} else {
		echo "error";
	}


}
echo "<h1>select file to unzip:</h1>";
foreach ($files as $filename) {
		if (is_file($filename) && strpos($filename,'.zip') >0) {
			echo "<a class='zip' onclick=\"return confirm('Are you sure you want to unzip this file?');\" href='?unzip=$filename&action=unzip' title='Unzip'>$filename</a><br>";
		} else if (is_file($filename)) {
			echo "<span class='file'>$filename</span><br>";
		}
	}



?>