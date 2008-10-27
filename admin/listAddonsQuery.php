<?php
session_start();
$baseURL = "../";
require($baseURL.'functions.php');
require($baseURL.'config.php');
if(isset($_SESSION['addonName']) && isset($_SESSION['curseAddonID'])){
  if(!checkForUpdateCompletion($_SESSION['curseAddonID'])){
    $progressInBytes = checkDownloadProgress($_SESSION['addonName']);
    $progressInKilobytes = round($progressInBytes/1024);
    $addonSize = $_SESSION['addonSize'];
    $addonSizeInKilobytes = round($addonSize/1024);
    echo '<font color="blue">'.$_SESSION['addonName'].' is currently updating. '.$progressInKilobytes.'KB of '.$addonSizeInKilobytes.'KB downloaded so far.</font><br />';
    $downloadFinished = true;
  }else{
    if(!$downloadFinished) echo '<font color="blue">'.$_SESSION['addonName'].' has completed downloading!<br />';
    else echo '<font color="blue">'.$_SESSION['addonName'].' has been updated!<br />';
    md5Addon($_SESSION['addonName']);
    unset($_SESSION['addonName']);
    unset($_SESSION['curseAddonID']);
    unset($_SESSION['addonSize']);
  }
}
if (isset($_SESSION['message']) || isset($message)){
  echo "<font color=\"blue\">".$_SESSION['message'].$message."</font><br />&nbsp;<br />";
  unset($_SESSION['message']);
}
?>