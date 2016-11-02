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
    if ( is_file($entry) and (strpos($entry,'.php')||strpos($entry,'.tpl')) )
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
$search="test";
$replacewith="test";
$files=getDirContents('./');
foreach($files as $file){
$cnt=file_get_contents($file);
if (strpos($cnt,$search)){
$cnt=str_replace($search,$replacewith,$cnt);
file_put_contents($file,$cnt);
echo $file."<br />";



}
}
echo "ok end";

?>