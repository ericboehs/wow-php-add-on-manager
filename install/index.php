<?php
if(!file_exists('../config.php')){
  echo "Config.php does not exist. Please create one based on the config.php.example";
  exit();
}
if(file_exists('structureImported')){
  echo "Installation complete.  Please remove the install directory.";
  exit();
}
include('../config.php');
$query = "CREATE TABLE IF NOT EXISTS `amz_addonsList` (
  `id` int(11) NOT NULL auto_increment,
  `curseAddonID` int(11) NOT NULL,
  `addonName` varchar(64) NOT NULL,
  `version` varchar(25) default NULL,
  `addonURL` varchar(200) default NULL,
  `lastDownloadID` int(11) default NULL,
  `lastDownloadDateTime` datetime default NULL,
  `lastDownloadDateTimeHuman` varchar(64) default NULL,
  `lastUpdateDateTime` datetime default NULL,
  `lastUpdateDateTimeHuman` varchar(64) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=102" ;
$installSQLResult = mysql_query($query);
if(!$instalSQLRResult) die(mysql_error());
if(!file_exists('../cachedZips/')) mkdir('../cachedZips');
if(!file_exists('../customZips/')) mkdir('../customZips');
touch ('structureImported');
header("Location: ".$_SERVER['PHP_SELF']);
?>
