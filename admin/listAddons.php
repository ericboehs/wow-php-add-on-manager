<?php
session_start();
$baseURL = "../";
require($baseURL.'functions.php');
require($baseURL.'config.php');
$debug = 0;
if($_POST['formSubmitted'] == "true" || ((isset($_GET['id']) && is_numeric($_GET['id'])) || isset($_GET['url']))){
  if(isset($_GET['url'])) $curseAddonID = getIdFromURL($_GET['url']);
  if($_POST['formSubmitted'] == "true") $curseAddonID = trim($_POST['curseAddonID']);
  if(isset($_GET['id'])) $curseAddonID = trim($_GET['id']);
  if(isset($_GET['deleteAddon']) && $_GET['deleteAddon']) $deleteAddon = true;
  if($deleteAddon){
    if(deleteAddon($curseAddonID)){
      $_SESSION['message'] = "Deleted Addon.";
    }else{
      $_SESSION['message'] = "Deletting Addon failed.";
    }
  }else{
    if(updateAddon($curseAddonID)){
      $_SESSION['message'] =  stripslashes($addonName)." was updated.";
    }else{
      $_SESSION['message'] =  stripslashes($addonName)." is up to date as of ".$currentDateTime[1].".";
    }
  }
}
$orderBy = "addonName";
$orderDir = "asc";
if(in_array($_GET['sort'], array("addonName", "version", "lastUpdateDateTime", "lastDownloadDateTime"))) $orderBy = $_GET['sort'];
if($_GET['direction'] == "desc") $orderDir = "desc";
$query = "SELECT id, curseAddonID, addonName, version, addonURL, lastDownloadID, lastDownloadDateTime, lastDownloadDateTimeHuman, lastUpdateDateTime, lastUpdateDateTimeHuman FROM amz_addonsList ORDER BY ".$orderBy." ".$orderDir;
$result = mysql_query($query);

$numRows = mysql_num_rows($result);
echo '<html>
      <head>
      <title>List Addons</title>
      <script type="text/javascript"></script>
      <style type="text/css">
        @import url("'.$baseURL.'style.css");
      </style>
      <script type="text/javascript" src="ajax.js"></script>
      </head>
      <body onload="ajaxFunction();" >
      <div id=\'ajaxDiv\'></div>
	  <form name="addAddon" method="post" action="'.$_SERVER['PHP_SELF'].'">
	  Curse ID: <input name="curseAddonID" value="" size="7" />
	  <input type="hidden" name="formSubmitted" value="true" />
	  <input type="submit" value="Add AddOn" />
	  </form>
	  &nbsp;<br />';
if($numRows != 0){
	if($orderBy=="addonName" && $orderDir=="asc") $addonNameSortURL .= "&direction=desc";
	if($orderBy=="version" && $orderDir=="asc") $versionSortURL .= "&direction=desc";
	if($orderBy=="lastUpdateDateTime" && $orderDir=="asc") $lastUpdateDateTimeSortURL .= "&direction=desc";
	if($orderBy=="lastDownloadDateTime" && $orderDir=="asc") $lastDownloadDateTimeSortURL .= "&direction=desc";
    echo '<table>
          <thead>
            <th scope="col"><a href="'.$_SERVER['PHP_SELF'].'?sort=addonName'.$addonNameSortURL.'">Addon Name</a></th>
            <th scope="col"><a href="'.$_SERVER['PHP_SELF'].'?sort=version'.$versionSortURL.'">Version</a></th>
            <th scope="col"><a href="'.$_SERVER['PHP_SELF'].'?sort=lastUpdateDateTime'.$lastUpdateDateTimeSortURL.'">Last Checked</a></th>
            <th scope="col"><a href="'.$_SERVER['PHP_SELF'].'?sort=lastDownloadDateTime'.$lastDownloadDateTimeSortURL.'">Last Download</a></th>
            <th scope="col">Updt</th>
            <th scope="col">Dwnld</th>
            <th scope="col">Dlt</th>
          </thead>
		  <tbody>';

    while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
      if($i%2){
		echo '<tr class="odd">';
	  }else{
		echo '<tr>';
	  }
      echo "<th scope=\"row\"><a href=\"".$row['addonURL']."\">".$row['addonName']."</a></td>" .
          "<td>".$row['version']."</td>" .
          "<td>".$row['lastUpdateDateTimeHuman']."</td>";
      ////
      if($row['lastDownloadDateTime'] == NULL){
        echo "<td>None</td>" .
            "<td align=\"center\">< <a href=\"".$_SERVER['PHP_SELF']."?id=".$row['curseAddonID']."\"><img src=\"".$baseURL."images/update.gif\" width=\"25\" height=\"25\" border=\"0\"></a></td>";
      }else{
        echo "<td>".$row['lastDownloadDateTimeHuman']."</td>" .
            "<td align=\"center\"><a href=\"".$_SERVER['PHP_SELF']."?id=".$row['curseAddonID']."&sort=".$orderBy."&direction=".$orderDir."\"><img src=\"".$baseURL."images/update.gif\" width=\"25\" height=\"25\" border=\"0\"></a>";
      }
      ////
      echo "<td align=\"center\"><a href=\"../cachedZips/".$row['addonName'].".zip\"><img src=\"".$baseURL."images/download.gif\" width=\"25\" height=\"25\" border=\"0\"></a></td>" .
          "<td align=\"center\"><a href=\"".$_SERVER['PHP_SELF']."?deleteAddon=true&id=".$row['id']."&sort=".$orderBy."&direction=".$orderDir."\"><img src=\"".$baseURL."images/delete.png\" width=\"25\" height=\"25\" border=\"0\"></a></td>" .
           "</tr>";
	$i++;
    }
    mysql_select_db($dbname);
}else{
  echo 'No addons in database.<br />';
}
echo '
</tbody>
</table>
</body>
</html>
';
?>