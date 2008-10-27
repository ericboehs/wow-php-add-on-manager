<?php
require('functions.php');
$uploaddir = '/home/ericboehs/ericboehs.com/addons/'.rand().'/';
$uploadfile = $uploaddir.'versions.zip';
mkdir($uploaddir);
if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
  if($zipFilename = getVersionsFromZip($uploadfile, $uploaddir)) {
    header("Location: ".$zipFilename);
  }else{
    echo "All of your Addons are up to date.";
  }
} else {
    echo "Possible file upload attack!\n";
}

?>