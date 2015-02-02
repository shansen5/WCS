<?php
   class MyDB extends SQLite3
   {
      function __construct()
      {
         $this->open('../db/zoocam.db');
      }
   }

   $db = new MyDB();
   if(!$db){
      echo $db->lastErrorMsg();
   } else {
      echo "Opened database successfully<br>";
   }

   $sql = <<<EOF
     SELECT * FROM users WHERE permissions = 2;
EOF;
   echo "Statement: " . $sql . "<br>";
   $ret = $db->query( $sql );
   while ( $row = $ret->fetchArray( SQLITE3_ASSOC ) ) {
     echo "First = " . $row[ 'first_name' ] . "<br>";
   }
   echo "Operation succeeded<br>";
   $db->close();
?>
