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
<img class="img-fluid" src="https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Fc.tenor.com%2Fs8mNCfzjhpYAAAAC%2Fpropeller-hat.gif&f=1&nofb=1&ipt=6188bbdbea15c79abfe9f3987e009f52b492d8ab8832a8924bb916fce9bd0354" alt="funny">
</body>
</html>
