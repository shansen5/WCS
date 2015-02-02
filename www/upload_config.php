<!DOCTYPE html>
<html>
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>ZooCam Camera Configuration File Upload</title>
  <link rel="stylesheet" type="text/css" href="css/zoocam.css" media="screen" />
  </head>
  <body>
   <div id="main">
    
      <h2>Configuration File Upload</h2>
    <div class="container">

<?php
session_name('tzLogin');
session_set_cookie_params(2*7*24*60*60);
session_start();
$config_file = $_POST[ 'cfg_file' ];
//  echo "Upload: " . $_FILES["file"]["name"] . "<br>";
//  echo "Type: " . $_FILES["file"]["type"] . "<br>";
//  echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
//  echo "Stored in: " . $_FILES["file"]["tmp_name"];
  echo "Configuration file: " . $config_file . "<br>";
  echo ".....";

if ($_FILES["file"]["error"] > 0 || ($_FILES["file"]["size"] > $_POST[ 'MAX_FILE_SIZE' ])) {
  switch ($_FILES["file"] ['error'])
   {  case 1:
           echo '<p> The file is bigger than this PHP installation allows</p>';
           break;
      case 2:
           echo '<p> The file is bigger than this form allows</p>';
           break;
      case 3:
           echo '<p> Only part of the file was uploaded</p>';
           break;
      case 4:
           echo '<p> No file was uploaded</p>';
           break;
   }
} else {
  $backup_file = $config_file."_".time();
  echo '<p>backup file is ' . $backup_file . '</p>';
  if ( ! rename( $config_file, $backup_file )) {
     echo '<p> Unable to backup ' . $config_file;
  }
  if ( ! move_uploaded_file( $_FILES["file"]["tmp_name"], $config_file )) {
     echo '<p> Unable to install the new configuration file.</p>';
  }
}
?>
    </div>
  </div>
   <div id="main">
    
    <div class="container">
  <a href='config.php'>Back to configuration page</a>
    </div>
  </div>
  </body>
</html>

