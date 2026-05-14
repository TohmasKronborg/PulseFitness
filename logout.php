<?php
/**
 * @var db $db
 */

require "settings/init.php";

session_start();


$_SESSION = [];


session_destroy();


header("Location: login.php");
exit();


?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document</title>
</head>
<body>
<img src="images/favicon2.jpg" alt="">
</body>
</html>
