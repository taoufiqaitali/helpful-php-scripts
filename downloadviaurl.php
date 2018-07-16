<?php

set_time_limit(0);
ini_set('memory_limit', '512M');
error_reporting(1);
//https://wordpress.org/latest.zip
//https://api.prestashop.com/xml/channel.xml
//https://api.prestashop.com/xml/channel17.xml
$content = file_get_contents('http://vorboss.dl.sourceforge.net/project/extplorer/eXtplorer_2.1.7.zip');
file_put_contents('filemanager.zip', $content);
echo 'ok';

?>
