<?php

session_name('tzLogin');
session_set_cookie_params(2*7*24*60*60);
session_start();
$config_dir = $_GET[ 'cfg_dir' ];

  header("Content-Type: text/plain");
  header("Content-Disposition: attachment; filename=\"" . $_GET["file"] . "\"");
  readfile( $config_dir . $_GET["file"]);

  file_put_contents( "/tmp/download_config.log", "config_dir is " . $config_dir . "\n",
         FILE_APPEND );
  file_put_contents( "/tmp/download_config.log", "file is " . $_GET[ "file" ] . "\n",
         FILE_APPEND );
?>
