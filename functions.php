<?php

/*
TODO:
-Make a "favorites" zip section
-Add a cron job for creating those favorites
-Make it XHTML valid
-Fix it to die gracefully if curse isn't providing the xml correctly
-Updated blah, but blah was missing
-Use the server's tmp directory
*/

function getCurseSessionID(){
  /*This function is used to store the Session ID, I got from Curse using a packet sniffer and their
  original curse client.  I've been using this ID for a while and it hasn't expired.  Here's to hoping
  it never will.*/
	return "VNIBQDUBUCEUPATP";
}

function fork($shellCmd){
  $shellCmd = addslashes($shellCmd);
	exec("nice sh -c \"$shellCmd\" > /dev/null 2>&1 &");
}

function fetchAddonXML($curseAddonID){
  /*This will get the XML file that is associated with the curseAddonID.  It contains everything we need
  including the URL to the addon and the addon's zip files.  Eventually I'd like to make this return the
  contents of the file in XML format, rather than a filename of where it's stored.*/
  //This registers the $baseURL and $debug variables as global variables so that they can be used inside
  //(or outside) this function.  If I don't make them global, then only what I return is accessable.
  global $baseURL, $debug;
  $curseSessionID = getCurseSessionID();
  //Create a random file name to store the XML temporarily.  This would be located  up one level if
  //$baseURL was set to "../" and if it was blank then it would use the current directory of the file
  //that included this file, not the directory of this file.
  $filename = $baseURL.rand().'.xml';
  //This is what actually get's the xml file and saves it.
  $ch = curl_init('http://addonservice.curse.com/AddOnService.asmx/GetAddOn?pAddOnId='.$curseAddonID.'&pSession='.$curseSessionID);
  if (!$ch)	die( "Cannot allocate a new PHP-CURL handle" );
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
  $xml = curl_exec($ch);
  curl_close($ch);
  file_put_contents($filename, $xml);
  return $filename;
  //return $xml;
}

function getContentLength($url){
  /*Checks the content length of a file.  Used for ajax progress updates.*/
  $contentLength = shell_exec('curl -s -I '.$url.' | grep Content-Length');
  $contentLength = explode(" ", $contentLength);
  $contentLength = $contentLength[1];
  return $contentLength;
}

function getIdFromURL($url){
  /*Grabs the ID from the "Install via Curse Client" link using a bookmarklet.*/
  global $message;
  $pieces = explode("=", $url);
  if(count($pieces)!=2 && substr($url,0,4) != "psyn"){
		$message .= "Invalid URL passed.";
		return false;
  }
  return trim($pieces[1]);
}

function checkForUpdateCompletion($curseAddonID){
  /*Checks to see if the "InProgress" file exists - used for ajax queries*/
  global $baseURL;
	if(file_exists($baseURL.$curseAddonID.'InProgress')) return false;
	return true;
}

function checkDownloadProgress($addonName){
  global $baseURL;
  $sizeInBytes = shell_exec('ls -l '.$baseURL.'cachedZips/'.$addonName.'.zip | awk \'{print $5}\'');
  return $sizeInBytes;
}

function addonExists($curseAddonID){
	global $debug, $baseURL;
	require($baseURL.'config.php');
	$query = "SELECT id from amz_addonsList WHERE curseAddonID=".$curseAddonID;
	$result = mysql_query($query);
	if(mysql_num_rows($result)) return true;
	return false;
}

function parseXML($xml){
  if(!isset($xml)) return false;
  global $debug, $lastDownloadDateTime, $lastDownloadDateTimeHuman, $addonName, $zipURL, $addonURL, $currentVersion, $currentDownloadID;
  //$line = explode("<name>",$xml);
  ///// YOU WERE HERE
  //die(print_r($line));
  $lastDownloadDateTime = trim(date('Y-m-d H:i:s'));
  $lastDownloadDateTimeHuman = trim(date('M j, Y \a\t g:i a'));
  $addonName = addslashes(trim(shell_exec('cat '.$xml.' | tr -s \'><\' \'\n\' | grep -a -m 1 -A 1 name | tail -1')));
  $zipURL = trim(shell_exec('cat '.$xml.' | tr -s \'><\' \'\n\' | grep -a -B 2 date | grep zip | tail -1'));
  $addonURL = trim(shell_exec('cat '.$xml.' | tr -s \'><\' \'\n\' | grep -a -m 1 aspx'));
  $currentVersion = trim(shell_exec('cat '.$xml.' | tr -s \'><\' \'\n\' | grep -a -A 2 \'file id\' | tail -1'));
  $currentDownloadID = trim(shell_exec('cat '.$xml.' | tr -s \'><\' \'\n\' | grep -a \'file id\' | tail -1 | awk -F\'"\' \'{print $2}\''));
  unlink($xml);
  return true;
}

function getVersionsFromZip($userZipLocation, $userExtractLocation){
  global $debug, $baseURL;
  $zipFilename = "AddonPack-".date('Ymd-His');
  if(file_exists($zipFilename)){
    $zipFilename .= "-".rand().".zip";
  }else{
    $zipFilename .= ".zip";
  }
  shell_exec('unzip "'.$userZipLocation.'" -d "'.$userExtractLocation.'"');
  unlink($userZipLocation);
  $directoryListing = scandir($userExtractLocation.'/versions');
  foreach($directoryListing as $thisFilename){
    if($thisFilename != "." || $thisFilename != ".."){
      $pieces = explode(".", $thisFilename);
      if($pieces[0]){
        $addonNames[] = $pieces[0];
        $addonHashes[] = file_get_contents($userExtractLocation.'/versions/'.$thisFilename);
      }
    }
  }
  $i=0;
  foreach($addonNames as $thisAddonName){
    if($addonHashes[$i] != file_get_contents($baseURL.'cachedZips/'.$thisAddonName.'.dir/versions/'.$thisAddonName.'.md5')){
      $updated = true;
      shell_exec('cd "'.$baseURL.'" && cd "cachedZips/'.$thisAddonName.'.dir" && zip -r "../../customZips/'.$zipFilename.'" * && cd ../..');
    }
    $i++;
  }
  shell_exec('rm -rf "'.$userExtractLocation.'"');
  if($updated) return 'customZips/'.$zipFilename;
  return false;
}

function md5Addon($addonName){
  global $debug, $baseURL;
  $md5Hash = md5_file($baseURL.'cachedZips/'.$addonName.'.zip');
  $md5Location = $baseURL.'cachedZips/'.$addonName.'.dir/versions/'.$addonName.'.md5';
  $md5Directory = $baseURL.'cachedZips/'.$addonName.'.dir/versions/';
  if(!file_exists($md5Directory)) mkdir($md5Directory);
  file_put_contents($md5Location, $md5Hash);
  return;
}

function updateNeeded($curseAddonID){
  global $debug, $baseURL, $lastDownloadDateTime, $lastDownloadDateTimeHuman, $addonName, $ourAddonName, $zipURL, $addonURL, $currentVersion, $currentDownloadID;
  require('config.php');
  if(!parseXML(fetchAddonXML($curseAddonID))) return false;
  $query = "SELECT lastDownloadID from amz_addonsList WHERE curseAddonID=".$curseAddonID;
  $result = mysql_query($query);
  while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
    $lastDownloadID = trim($row['lastDownloadID']);
  }
  $query = "UPDATE amz_addonsList SET addonName='$addonName', addonURL='$addonURL', version='$currentVersion', lastUpdateDateTime='$lastDownloadDateTime', lastUpdateDateTimeHuman='$lastDownloadDateTimeHuman' WHERE curseAddonID=$curseAddonID";
  if($debug){ echo $query."<br />"; }
  $updatesult = mysql_query($query);
  if($debug && !$updateResult) die('Invalid query: ' . mysql_error());
  if($currentDownloadID != $lastDownloadID) return true;
  if(!file_exists($baseURL.'cachedZips/'.$addonName.'.zip')) return true;
  if(!file_exists($baseURL.'cachedZips/'.$addonName.'.dir')) fork('unzip -d "'.$baseURL.'cachedZips/'.$addonName.'.dir" "'.$baseURL.'cachedZips/'.$addonName.'.zip"');
  if(!file_exists($baseURL.'cachedZips/'.$addonName.'.dir/versions/'.$addonName.'.md5')) md5Addon($addonName);
  return false;
}

function updateAddon($curseAddonID){
  global $debug, $baseURL, $lastDownloadDateTime, $lastDownloadDateTimeHuman, $addonName, $zipURL, $addonURL, $currentVersion, $currentDownloadID;
  require('config.php');
  if(!parseXML(fetchAddonXML($curseAddonID))) return false;
  $_SESSION['addonName'] = $addonName;
  $_SESSION['curseAddonID'] = $curseAddonID;
  $_SESSION['addonSize'] = getContentLength($zipURL);
  touch($baseURL.$curseAddonID."InProgress");
  fork('wget -O "'.$baseURL.'cachedZips/'.$addonName.'.zip" '.$zipURL.' && rm '.$baseURL.$curseAddonID.'InProgress && rm -rf "'.$baseURL.'cachedZips/'.$addonName.'.dir"; unzip -d "'.$baseURL.'cachedZips/'.$addonName.'.dir" "'.$baseURL.'cachedZips/'.$addonName.'.zip" && md5 "'.$baseURL.'cachedZips/'.$addonName.'.zip" -out "'.$baseURL.'cachedZips/'.$addonName.'.dir/md5checksum.txt"');
  if(addonExists($curseAddonID)){
  	$query = "UPDATE amz_addonsList SET addonName='$addonName', version='$currentVersion', addonURL='$addonURL', lastDownloadID=$currentDownloadID, lastDownloadDateTime='$lastDownloadDateTime', lastDownloadDateTimeHuman='$lastDownloadDateTimeHuman', lastUpdateDateTime='$lastDownloadDateTime', lastUpdateDateTimeHuman='$lastDownloadDateTimeHuman' WHERE curseAddonID=$curseAddonID";
  }else{
    $query = "INSERT INTO amz_addonsList (curseAddonID, addonName, version, addonURL, lastDownloadID, lastDownloadDateTime, lastDownloadDateTimeHuman, lastUpdateDateTime, lastUpdateDateTimeHuman) VALUES ($curseAddonID, '$addonName', '$currentVersion', '$addonURL', $currentDownloadID, '$lastDownloadDateTime', '$lastDownloadDateTimeHuman', '$lastDownloadDateTime', '$lastDownloadDateTimeHuman')";
  }
  $result = mysql_query($query);
  if($debug && !$result) die('Invalid query: ' . mysql_error());
  if(!$result) return false;
  return true;
}

function deleteAddon($curseAddonID){
	global $debug, $baseURL, $addonName, $message;
	if($curseAddonID == null || !is_numeric($curseAddonID)){
		$message .= "Addon ID must contain numbers only.  Please select update from the list below.";
		return false;
	}
	require('config.php');
	$query = "SELECT addonName from amz_addonsList WHERE curseAddonID=".$curseAddonID;
	$result = mysql_query($query);
	if(!result) return false;
	while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
	  $addonName = trim($row['addonName']);
	}
	if(file_exists("$baseURLcachedZips/$addonName.dir")){
	  shell_exec('rm -rf "'.$baseURL.'cachedZips/'.$addonName.'.dir"');
	}
	if(file_exists("$baseURLcachedZips/$addonName.zip")){
	  shell_exec('rm -rf "'.$baseURL.'cachedZips/'.$addonName.'.zip"');
	}
	$query = "DELETE FROM amz_addonsList WHERE id=".$curseAddonID;
	$deleteResult = mysql_query($query);
	if(!$deleteResult) return false;
	return true;
}

function newParseXML(){
	$file = "xml_test.xml";
	function contents($parser, $data){
	    echo $data;
	}
	function startTag($parser, $data){
	    echo "<b>";
	}
	function endTag($parser, $data){
	    echo "</b><br />";
	}
	$xml_parser = xml_parser_create();
	xml_set_element_handler($xml_parser, "startTag", "endTag");
	xml_set_character_data_handler($xml_parser, "contents");
	$fp = fopen($file, "r");
	$data = fread($fp, 80000);
	if(!(xml_parse($xml_parser, $data, feof($fp)))){
	    die("Error on line " . xml_get_current_line_number($xml_parser));
	}
	xml_parser_free($xml_parser);
	fclose($fp);
}

?>
