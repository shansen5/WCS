<?php
error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', TRUE );
echo "Attempting to create database Friends ...";
$dbhandle = sqlite_open('../db/zoocam.db', 0666, $error);
if (!$dbhandle) {
  echo $error;
  die ($error);
}

$stm = "CREATE TABLE Friends(Id integer PRIMARY KEY," . 
       "Name text UNIQUE NOT NULL, Sex text CHECK(Sex IN ('M', 'F')))";
$ok = sqlite_exec($dbhandle, $stm, $error);

if (!$ok)
   die("Cannot execute query. $error");

echo "Database Friends created successfully";

sqlite_close($dbhandle);
?>
