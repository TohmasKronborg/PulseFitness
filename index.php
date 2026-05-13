<?php
/**
 * @var db $db
 */

require "settings/init.php";

session_start();

if (empty($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userId'];

$user = $db->sql("SELECT * FROM members WHERE member_number = :userId", [":userId" => $userId]);
$name = $user[0]->member_name;
$streak = $user[0]->streak;
$exTotal = $user[0]->total_exercises;
$exCompleted = $user[0]->completed_exercises;
$hoursSpent = $user[0]->hours_spent;
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">
    
    <title>Velkommen <?php echo $name?></title>
    
    <meta name="robots" content="All">
    <meta name="author" content="Udgiver">
    <meta name="copyright" content="Information om copyright">
    
    <link rel="stylesheet" href="css/styles.css" type="text/css">
    <link rel="icon" href="images/LogoBlack.png">
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body class="px-auto">

<?php echo $name, $streak, $exTotal, $exCompleted, $hoursSpent ?>

<script src="scripts/canvas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
