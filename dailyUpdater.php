<?php
$baseURL="/home/ericboehs/ericboehs.com/addons/";
require('config.php');
require('functions.php');
$query = "SELECT id FROM amz_addonsList";
$result = mysql_query($query);
if(!$result) die('Could not get list of Addons!');
while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
	$checkQuery = "SELECT curseAddonID, addonName from amz_addonsList WHERE id=".$row['id'];
	$checkResult = mysql_query($checkQuery);
	while($checkRow = mysql_fetch_array($checkResult, MYSQL_ASSOC)){
		$curseAddonID = trim($checkRow['curseAddonID']);
		$addonName = trim($checkRow['addonName']);
	}
	if(updateNeeded($curseAddonID)){
		if(!updateAddon($curseAddonID)){
			die(stripslashes($addonName)." could not be updated.");
		}
	}
}
//Cleanse the customZips
shell_exec('find '.$baseURL.'customZips/* -mmin +1440 -exec rm {} \;');
?>