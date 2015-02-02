<?php
error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', TRUE );
echo "Hello, I'm alive";
echo "<br>";
echo phpversion();
echo "<br>";
// echo sqlite_libversion();
print_r( SQLite3::version());
?>
