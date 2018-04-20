<?php
set_time_limit(0);
ini_set('memory_limit', '500M');
error_reporting(1);
echo "ok";
function getDirContents($dir)
{
  $handle = opendir($dir);
  if ( !$handle ) return array();
  $contents = array();
  while ( $entry = readdir($handle) )
  {
    if ( $entry=='.' || $entry=='..' ) continue;

    $entry = $dir.DIRECTORY_SEPARATOR.$entry;
    if ( is_file($entry) && (strpos($entry,'.php')) )
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

$files=getDirContents('./');
foreach($files as $file){
$cnt=file_get_contents($file);
if (strpos($cnt,'beta')){
echo $file."<br />";

}
}
echo "ok end";

?>