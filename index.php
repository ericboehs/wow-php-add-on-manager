<?php
session_start();
require('config.php');
require('functions.php');
$orderBy = "addonName";
$orderDir = "asc";
$query = "SELECT id, curseAddonID, addonName, version, lastDownloadID, lastDownloadDateTime, lastDownloadDateTimeHuman, addonURL FROM amz_addonsList ORDER BY ".$orderBy." ".$orderDir;
$result = mysql_query($query);
if($_POST['formSubmitted']){
	$filename = "AddonPack-".date('Ymd-His');
	if(file_exists($filename))
		$filename .= "-".rand().".zip";
	else
		$filename .= ".zip";
	while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
		if($_POST[$row['id']] == "on"){
			$checkQuery = "SELECT curseAddonID, addonName, lastDownloadID from amz_addonsList WHERE id=".$row['id'];
			$checkResult = mysql_query($checkQuery);
			while($checkRow = mysql_fetch_array($checkResult, MYSQL_ASSOC)){
				$curseAddonID = trim($checkRow['curseAddonID']);
				$addonName = trim($checkRow['addonName']);
			}
			if(updateNeeded($curseAddonID)){
				if(updateAddon($curseAddonID)){
				  while(!checkForUpdateCompletion($curseAddonID)) sleep(2);
				}else{
					die(stripslashes($addonName)." could not be added.");
				}
			}
			if($_POST['onWindows'] == "on"){
			  shell_exec('cd "cachedZips/'.$row['addonName'].'.dir" && zip -r "../../customZips/'.$filename.'" * && cd ../..');
			}else{
			  shell_exec('cat cachedZips/'.$row['addonName'].'.zip >> customZips/'.$filename);
			}
		}
	}
	header("Location: customZips/$filename");
}
?>
<html>
<head>
<title>List Addons</title>
<style type="text/css">
  @import url("style.css");
</style>
</head>
<body>
<?php
if (isset($_SESSION['message'])){
	echo "<font color=\"blue\">".$_SESSION['message']."</font>";
	unset($_SESSION['message']);
}
?>
<p>This page will create a zip file with all of the Addons you select below. One zip file, one download, one extract, every addon updated.</p>
<p>&nbsp;</p>
<p>Check each addon you want to include in your pack.</p>
<p>&nbsp;</p>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<table summary="WoW Addon Manager">
			<caption>WoW Addon Manager</caption>
	<thead>
		<th scope="col" class="checkbox">Add</th>
		<th scope="col" class="addonName">Addon Name</th>
		<th scope="col" class="version">Version</th>
		<th scope="col" class="download">Dld</th>
	</thead>
	<?php
while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
	if($i%2){
		echo '	<tr class="odd">';
	  }else{
		echo '	<tr>';
	  }
	echo '
		<td><input type="checkbox" name="'.$row['id'].'" /></td>
		<td><a href="'.$row['addonURL'].'">'.$row['addonName'].'</a></td>
		<td>'.$row['version'].'</td>
		<td><a href="cachedZips/'.$row['addonName'].'.zip"><img src="images/download.gif" width="25" height="25" border="0"></a></td>
	</tr>
';
	$i++;
}
?>
</table>
&nbsp;<br />
<input type="hidden" name="formSubmitted" value="true" />
<input type="checkbox" name="onWindows" checked /> Using Windows? <br />
<input type="submit" name="submit" value="Download Selected" />
</form>
</body>
</html>
