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
$parts = explode(':', $hoursSpent);

$hours = (int)$parts[0];
$minutes = (int)$parts[1];

$timetotal = $hours + ($minutes / 60);
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

<body class="px-auto overflow-x-hidden" style="max-width: 768px; height: 100vh;">

<nav class="position-relative flex-shrink-1 overflow-x-hidden">
    <img src="images/dims.svg" alt="dims" style="margin-top: -50px; margin-left: -125px;">
    <a href="index.php"><img src="images/LogoWhite.png" alt="WhiteLogo" class="img-fluid position-absolute top-0 start-50 translate-middle mt-5" style="max-width: 100px;"></a>

</nav>

<main class="container-fluid mt-5">
    <div class="row gap-3 justify-content-center">
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-info montserrat fw-bold fs-stats"><?php echo $streak ?></span> <span class="fs-stats2">Rutine streak</span></div>
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-primary montserrat fw-bold fs-stats"><?php echo $exCompleted ?></span> <span class="fs-stats2">Gennemførte Rutiner</span></div>
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-secondary montserrat fw-bold fs-stats"><?php echo $exTotal ?></span> <span class="fs-stats2">Øvelser Lavet</span></div>
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-secondary montserrat fw-bold fs-stats"><?php echo $timetotal ?></span> <span class="fs-stats2">Tid Brugt</span></div>
    </div>
</main>

<script src="scripts/canvas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
