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

$workouts = $db->sql("SELECT * FROM workouts WHERE program_id = :userId", [":userId" => $userId]);

if (!empty($workouts)) {
    $noWorkout = "";
} else {
    $noWorkout = "Ingen Rutine lavet endnu";
}
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

<body class="overflow-x-hidden mx-auto position-relative flex-column" style="max-width: 768px; height: 100vh; ">

<!-- Dims Nav -->
<nav class="position-relative flex-shrink-1 overflow-x-hidden">
    <img src="images/dims.svg" alt="dims" style="margin-top: -50px; margin-left: -175px;">
    <a href="index.php"><img src="images/LogoWhite.png" alt="WhiteLogo" class="img-fluid position-absolute top-0 start-50 translate-middle mt-5" style="max-width: 100px;"></a>
</nav>

<main class="container-fluid mt-3 mb-4">
    <h2 class="montserrat mb-3 fs-3 text-secondary">Velkommen <br> <b class="fs-1 text-dark"><?php echo $name ?></b></h2>
    <div class="row gap-3 justify-content-center">
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-info montserrat fw-bold fs-stats"><?php echo $streak ?></span> <span class="fs-stats2">Rutine streak</span></div>
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-primary montserrat fw-bold fs-stats"><?php echo $exCompleted ?></span> <span class="fs-stats2">Gennemførte Rutiner</span></div>
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-secondary montserrat fw-bold fs-stats"><?php echo $exTotal ?></span> <span class="fs-stats2">Øvelser Lavet</span></div>
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-secondary montserrat fw-bold fs-stats"><?php echo $timetotal ?></span> <span class="fs-stats2">Tid Brugt</span></div>
    </div>
</main>

<section class="container-fluid">
    <div>
        <h1 class="montserrat fw-bold">Dagens Rutine</h1>
        <p class="text-center mt-5">
            <?php echo $noWorkout ?>
        </p>
    </div>

    <?php

    $nextWorkouts = count($workouts);

    for ($i = 1; $i < $nextWorkouts; $i++) {
        echo $workouts[$i]->name;
        echo "<br>";
    }
    ?>
</section>

<div class="text-center mt-5 fw-bolder">
    <a href="logout.php">log ud</a>
</div>

<!-- Bottom Nav -->
<footer class="mt-auto rounded-top-circle bg-white flex-shrink-0" style="min-height: 85px;">
    <div style="margin-top: -25px;">
        <!-- Button 1 -->
        <div class="flex-column-center gap-1" >
            <a class="p-3 bg-info rounded-circle" href="generate.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45" fill="none">
                    <g clip-path="url(#clip0_135_504)">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M31.644 0.191028C31.9193 0.350604 32.1323 0.598812 32.2482 0.895102C32.3642 1.19139 32.3763 1.51825 32.2824 1.82228L27.2171 18.281H36.5631C36.8377 18.2809 37.1064 18.3612 37.3359 18.5121C37.5654 18.6629 37.7458 18.8777 37.8547 19.1298C37.9635 19.382 37.9962 19.6605 37.9486 19.931C37.901 20.2015 37.7752 20.4521 37.5868 20.652L15.0868 44.5582C14.8691 44.7897 14.5794 44.9407 14.2649 44.9864C13.9504 45.0322 13.6297 44.9701 13.3551 44.8103C13.0804 44.6504 12.868 44.4023 12.7524 44.1062C12.6368 43.8102 12.625 43.4837 12.7187 43.1801L17.784 26.7185H8.43806C8.16341 26.7186 7.89473 26.6383 7.6652 26.4875C7.43567 26.3366 7.25534 26.1219 7.14646 25.8697C7.03759 25.6176 7.00493 25.3391 7.05253 25.0686C7.10013 24.7981 7.2259 24.5474 7.41431 24.3476L29.9143 0.44134C30.1318 0.21015 30.421 0.0593143 30.735 0.0133578C31.0491 -0.0325986 31.3694 0.0290272 31.644 0.188215V0.191028Z" fill="white"/>
                    </g>
                    <defs>
                        <clipPath id="clip0_135_504">
                            <rect width="45" height="45" fill="white"/>
                        </clipPath>
                    </defs>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold">Generer</p>
        </div>

    </div>
</footer>

<script src="scripts/canvas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
