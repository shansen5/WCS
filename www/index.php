<?php

define('INCLUDE_CHECK',true);

// require 'connect.php';
require 'functions.php';
// That file can be included only if INCLUDE_CHECK is defined


session_name('tzLogin');
// Starting the session

session_set_cookie_params(2*7*24*60*60);
// Making the cookie live for 2 weeks

session_start();

$id_val = isset( $_SESSION[ 'id' ]) ? $_SESSION[ 'id' ] : '';
if($id_val && !isset($_COOKIE['tzRemember']) && !$_SESSION['rememberMe'])
{
    // If you are logged in, but you don't have the tzRemember cookie (browser restart)
    // and you have not checked the rememberMe checkbox:

    $_SESSION = array();
    session_destroy();
    
    // Destroy the session
}


if(isset($_GET['logoff']))
{
    $_SESSION = array();
    session_destroy();
    
    header("Location: index.php");
    exit;
}

$submit_val = isset( $_POST[ 'submit' ]) ? $_POST[ 'submit' ] : '';
if($submit_val=='Login')
{
    // Checking whether the Login form has been submitted
    
    $err = array();
    // Will hold our errors
    
    
    if(!$_POST['username'] || !$_POST['password'])
        $err[] = 'All the fields must be filled in!';
    
    if(!count($err))
    {
ini_set('display_errors', 'On');
        $dbh = new PDO('sqlite:/var/www/db/zoocam.db');

        $result = $dbh->query('SELECT * FROM users WHERE username = "' . $_POST['username'] . '" AND created_date IS NOT NULL;');

        $_POST['rememberMe'] = (int)$_POST['rememberMe'];
        $found = 0;
        $id = 0;
        $permissions = 0;
        foreach ( $result as $res ) {
            if ( $res["password"] == $_POST['password'] ) {
                $found = 1;
                $_SESSION['usr']=$res['first_name'] . $res['last_name'];
                $id = $res['id'];
                $_SESSION['id'] = $id;
                $permissions = $res['permissions'];
                $_SESSION['permissions'] = $permissions;
                setcookie('tzRemember',$_POST['rememberMe']);
                break;
            }
        }
        if ( $found == 0 ) {
            $err[]='Wrong username and/or password!';
        } else {
            $updstmt = $dbh->prepare( 
                'UPDATE users SET last_login_date = date("now"), ' .
                ' last_login_time = time("now") WHERE id = :user_id' );
            $updstmt->bindParam( ':user_id', $id );
            $updstmt->execute();
        }
ini_set('display_errors', 'Off');
    }
    
    if($err)
    $_SESSION['msg']['login-err'] = implode('<br />',$err);
    // Save the error messages in the session

    header("Location: index.php");
    exit;
}
else if($submit_val=='Register')
{
    // If the Register form has been submitted
    
    $err = array();
    
    if(strlen($_POST['username'])<4 || strlen($_POST['username'])>32)
    {
        $err[]='Your username must be between 3 and 32 characters!';
    }
    
    if(preg_match('/[^a-z0-9\-\_\.]+/i',$_POST['username']))
    {
        $err[]='Your username contains invalid characters!';
    }
    
    if(!checkEmail($_POST['email']))
    {
        $err[]='Your email is not valid!';
    }
    
    if(!count($err))
    {
        // If there are no errors
        
        $pass = substr(md5($_SERVER['REMOTE_ADDR'].microtime().rand(1,100000)),0,6);
        // Generate a random password
        
        $_POST['email'] = mysql_real_escape_string($_POST['email']);
        $_POST['username'] = mysql_real_escape_string($_POST['username']);
        // Escape the input data
        
        
        mysql_query("    INSERT INTO tz_members(usr,pass,email,regIP,dt)
                        VALUES(
                        
                            '".$_POST['username']."',
                            '".md5($pass)."',
                            '".$_POST['email']."',
                            '".$_SERVER['REMOTE_ADDR']."',
                            NOW()
                            
                        )");
        
        if(mysql_affected_rows($link)==1)
        {
            send_mail(    'steven.e.hansen@gmail.com',
                        $_POST['email'],
                        'Registration - Your New Password',
                        'Your password is: '.$pass);

            $_SESSION['msg']['reg-success']='We sent you an email with your new password!';
        }
        else $err[]='This username is already taken!';
    }

    if(count($err))
    {
        $_SESSION['msg']['reg-err'] = implode('<br />',$err);
    }    
    
    header("Location: index.php");
    exit;
}

$script = '';

$msg_val = isset( $_SESSION[ 'msg' ]) ? $_SESSION[ 'msg' ] : '';
if($msg_val)
{
    // The script below shows the sliding panel on page load
    
    $script = '
    <script type="text/javascript">
    
        $(function(){
        
            $("div#panel").show();
            $("#toggle a").toggle();
        });
    
    </script>';
    
}
?>

<!DOCTYPE html>
<html>
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Wildlife Cam Images and Videos</title>
    
    <link rel="stylesheet" type="text/css" href="css/zoocam.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="login_panel/css/slide.css" media="screen" />
    
    <script type="text/javascript" src="js/libs/jquery/jquery.js"></script>
    <script type="text/javascript" src="js/libs/jqueryui/jquery-ui-1.10.4.js"></script>
    <script src="js/script.js" type="text/javascript"></script>
    
    <!-- PNG FIX for IE6 -->
    <!-- http://24ways.org/2007/supersleight-transparent-png-in-ie6 -->
    <!--[if lte IE 6]>
        <script type="text/javascript" src="login_panel/js/pngfix/supersleight-min.js"></script>
    <![endif]-->
    
    <script src="login_panel/js/slide.js" type="text/javascript"></script>
    
    <?php echo $script; ?>
</head>

<body onload="setTimeout('init();', 100);">

<!-- Panel -->
<div id="toppanel">
    <div id="panel">
        <div class="content clearfix">
            
            <?php
            $id_val = isset( $_SESSION[ 'id' ]) ? $_SESSION[ 'id' ] : '';
            if(!$id_val):
            
            ?>

            <div class="left">
                <h1>Login to WildlifeCam</h1>
                <p class="grey">Registered users login here to be able to edit camera
configurations or start/stop the camera and video/image capture.</p>
            </div>
            
            <div class="left">
                <!-- Login Form -->
                <form class="clearfix" action="" method="post">
                    <h1>User Login</h1>
                    
                    <?php
                        
                        $msg_val = isset( $_SESSION[ 'msg' ]) ? $_SESSION[ 'msg' ] : '';
                        if($msg_val) 
                        {
                          if($_SESSION['msg']['login-err'])
                          {
                            echo '<div class="err">'.$_SESSION['msg']['login-err'].'</div>';
                            unset($_SESSION['msg']['login-err']);
                          }
                        }
                    ?>
                    
                    <label class="grey" for="username">Username:</label>
                    <input class="field" type="text" name="username" id="username" value="" size="23" />
                    <label class="grey" for="password">Password:</label>
                    <input class="field" type="password" name="password" id="password" size="23" />
                    <label><input name="rememberMe" id="rememberMe" type="checkbox" checked="checked" value="1" /> &nbsp;Remember me</label>
                    <div class="clear"></div>
                    <input type="submit" name="submit" value="Login" class="bt_login" />
                </form>
            </div>
            <div class="left right">            
                <!-- Register Form -->
                <form action="" method="post">
                    <h1>Not registered yet? Submit a request for a login.</h1>        
                    
                    <?php
                        
                        $msg_val = isset( $_SESSION[ 'msg' ]) ? $_SESSION[ 'msg' ] : '';
                        if($msg_val)
                        {
                          if($_SESSION['msg']['reg-err'])
                          {
                            echo '<div class="err">'.$_SESSION['msg']['reg-err'].'</div>';
                            unset($_SESSION['msg']['reg-err']);
                          }
                        
                          if($_SESSION['msg']['reg-success'])
                          {
                            echo '<div class="success">'.$_SESSION['msg']['reg-success'].'</div>';
                            unset($_SESSION['msg']['reg-success']);
                          }
                        }
                    ?>
                            
                    <label class="grey" for="username">Username:</label>
                    <input class="field" type="text" name="username" id="username" value="" size="23" />
                    <label class="grey" for="email">Email:</label>
                    <input class="field" type="text" name="email" id="email" size="23" />
                    <label>A password will be e-mailed to you.</label>
                    <input type="submit" name="submit" value="Register" class="bt_register" />
                </form>
            </div>
            

            <?php
            /* if ( $_SESSION['permissions'] > 1 ) : */
            else :
            ?>
            
            <div class="left">
            
            <?php
            $perms_val = isset( $_SESSION[ 'permissions' ]) ? $_SESSION[ 'permissions' ] : '';
            if ( $perms_val > 1 ) :
            ?>

            <h1>Access camera configuration</h1>
            
            <p><a href="config.php">Use this link to access the configuration page.</a></p>
            <p>- or -</p>

            <?php
            endif;
            ?>

            <p>Use this link to </p>
            <a href="?logoff">Log off</a>
            
            </div>
            
            <div class="left right">
            </div>
            
            <?php
            endif;
            ?>
        </div>
    </div> <!-- /login -->    

    <!-- The tab on top -->    
    <div class="tab">
        <ul class="login">
            <li class="left">&nbsp;</li>
            <li>Hello 
               <?php 
                 $usr_val = isset( $_SESSION[ 'usr' ]) ? $_SESSION[ 'usr' ] : '';
                 echo $usr_val ? $usr_val : 'Guest';
               ?>!</li>
            <li class="sep">|</li>
            <li id="toggle">
                <a id="open" class="open" href="#">
                  <?php 
                    $id_val = isset( $_SESSION[ 'id' ]) ? $_SESSION[ 'id' ] : '';
                    echo $id_val?'Config | Log Off':'Log In | Register';
                  ?></a>
                <a id="close" style="display: none;" class="close" href="#">Close Panel</a>            
            </li>
            <li class="right">&nbsp;</li>
        </ul> 
    </div> <!-- / top -->
    
</div> <!--panel -->

<div class="pageContent">
    <div id="main">
        <div class="container" align="center">
        <h1>WildlifeCam</h1>
        <h2>View and record what you see</h2>
        </div>
        
        <div class="container" align="center">
        
      <h1>Wildlife Cam Images and Videos</h1>
      <?php
        if(isset($_GET["delete"])) {
          unlink("/mnt/wcs_flash/media/" . $_GET["delete"]);
        }
        if(isset($_GET["delete_all"])) {
          $files = scandir("media");
          foreach($files as $file) unlink("/mnt/wcs_flash/media/$file");
        }
        else if(isset($_GET["file"])) {
          if(substr($_GET["file"], -3) == "jpg") {
            // Still image
            echo "<h2>" . $_GET["file"] . "<br>";
            echo "<img src='media/" . $_GET["file"] . "' width='640'>";
          } else {
            // Video
            echo "<h2>Recorded video: ";
            echo $_GET["file"] . "<br>";
            echo "<video width='640' controls><source src='media/" . $_GET["file"] . "' type='video/mp4'>Your browser does not support the video tag.</video>";
          }
          echo "<p>";
          echo "<input type='button' value='Close' onclick='window.location=\"index.php?close=" .$_GET["file"] . "\";'>";
          if ($_SESSION['id'] ) {
            echo "<input type='button' value='Download' onclick='window.open(\"download.php?file=" . $_GET["file"] . "\", \"_blank\");'> ";
            if ($_SESSION['permissions'] > 0 ) {
              echo "<input type='button' value='Delete' onclick='window.location=\"index.php?delete=" . $_GET["file"] . "\";'></p>";
            }
          }
        }
      ?>
      <div><h2>Live Cam<br><img id="mjpeg_dest"></div>
      <?php 
        $id_val = isset( $_POST[ 'id' ]) ? $_POST[ 'id' ] : '';
        $perms_val = isset( $_POST[ 'perms' ]) ? $_POST[ 'perms' ] : '';
        if ($id_val && $perms_val > 0) { 
          echo '<input id="video_button" type="button">';
          echo '<input id="image_button" type="button">';
          echo '<input id="timelapse_button" type="button">';
          echo '<input id="md_button" type="button">';
          echo '<input id="halt_button" type="button">';
        }
      ?>
      <?php
        $id_val = isset( $_SESSION[ 'id' ]) ? $_SESSION[ 'id' ] : '';
        if ($id_val) {
          echo "<h1>Images and Videos</h1>";
          $files = scandir("media");
          if(count($files) == 2) echo "<p>No videos/images saved</p>";
          else {
            foreach($files as $file) {
              if(($file != '.') && ($file != '..')) {
                $fsz = round ((filesize("media/" . $file)) / (1024 * 1024));
                echo "<p><a href='index.php?file=$file'>$file</a> ($fsz MB)</p>";
              }
            }
            $perms_val = isset( $_SESSION[ 'permissions' ]) ? $_SESSION[ 'permissions' ] : '';
            if ($perms_val > 0 ) {
              echo "<p><input type='button' value='Delete all' onclick='if(confirm(\"Delete all?\")) {window.location=\"index.php?delete_all\";}'></p>";
            }
          }
        }
      ?>
    </div>
  </body>
</html>
