<?php
require('functions.php');
$uploaddir = '/tmp/'.rand().'/';
$uploadfile = $uploaddir.'versions.zip';
mkdir($uploaddir);
if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
  if($zipFilename = getVersionsFromZip($uploadfile, $uploaddir)) {
    header("Location: ".$zipFilename);
  }else{
    echo "All of your Addons are up to date.";
  }
} else {
    echo "Let's not and say we did.\n";
}

?>