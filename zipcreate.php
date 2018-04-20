<?php

ini_set('memory_limit', '5000M');
ini_set("max_execution_time", '5000');
ini_set("max_input_time", '5000');
ini_set('default_socket_timeout', '5000');
@set_time_limit(0);


function getDirContents($dir)
{
  $handle = opendir($dir);
  if ( !$handle ) return array();
  $contents = array();
  while ( $entry = readdir($handle) )
  {
    if ( $entry=='.' || $entry=='..' ) continue;

    $entry = $dir.DIRECTORY_SEPARATOR.$entry;
    if ( is_file($entry) ) 
    {
      $contents[] = $entry;
    }
    else if ( is_dir($entry) )
    {
      $contents = array_merge($contents, getDirContents($entry));
    }
  }
  closedir($handle);
  return $contents;
}
$zip_location = './img';


if(isset($_GET['zip'])){
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($zip_location));
foreach ($iterator as $key=>$value){
   $files[]= $value;
}

if (! class_exists('ZipArchive')) {
		echo "ERROR: Stopping install process.  Trying to extract without ZipArchive module installed.  Please use the 'Manual Package extraction' mode to extract zip file.";
		exit;
	}
	
	$zip = new ZipArchive();
	if ($zip->open('imgpart2part2.zip',ZipArchive::CREATE) === TRUE) {
		
		
foreach ($files as $filename) {
@set_time_limit(0);
		if ( is_file($filename)) {
			$relativePath = substr($filename, strlen($zip_location) + 1);
			$zip->addFile($filename,$relativePath);
		}else if ( is_dir($filename) ) {
      //$zip->addEmptyDir($filename);
    }
	}	
	$zip->setArchiveComment('Archive created by Taoufiq Ait Ali @2fi98a');

		echo  "<br />".$zip->close();
		echo  "<br /> zip created";
		
	} else {
		echo "error";
	}


}
echo "<h1>add files that you want to zip in this folder</h1>".$zip_location;

echo "<br /><a class='zip' onclick=\"return confirm('Are you sure you want to zip this files?');\" href='?zip=zip&action=zip' title='Unzip'>click here to start ziping</a><br>";


?>