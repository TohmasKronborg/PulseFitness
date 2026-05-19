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

<body class="mx-auto flex-column position-relative overflow-x-hidden" style="max-width: 768px; height: 100vh; ">

<!-- Dims Nav -->
<nav class="flex-column">
    <div class="bg-primary flex-center h-auto">
        <a class="mx-auto my-3" href="index.php"><img src="images/LogoWhite.png" alt="WhiteLogo" class="img-fluid" style="max-width: 100px;"></a>
        <?php include "include/navButtons.php" ?>
    </div>
    <img src="images/VectorPrimary.svg" alt="dims">
</nav>

<!-- Stats -->
<main class="container-fluid mt-3 mb-5">
    <h2 class="montserrat mb-3 fs-3 text-secondary">Velkommen <br> <b class="fs-1 text-dark"><?php echo $name ?></b></h2>
    <div class="row gap-3 justify-content-center">
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-info montserrat fw-bold fs-stats"><?php echo $streak ?></span> <span class="fs-stats2">Rutine streak</span></div>
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-primary montserrat fw-bold fs-stats"><?php echo $exCompleted ?></span> <span class="fs-stats2">Gennemførte Rutiner</span></div>
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-secondary montserrat fw-bold fs-stats"><?php echo $exTotal ?></span> <span class="fs-stats2">Øvelser Lavet</span></div>
        <div class="col-5 bg-white rounded-4 p-3 pt-4 flex-column"><span class="text-secondary montserrat fw-bold fs-stats"><?php echo $timetotal ?></span> <span class="fs-stats2">Tid Brugt</span></div>
    </div>
</main>

<?php
$workouts = $db->sql("
    SELECT 
        w.id,
        w.name,
        w.workout_number,
        COUNT(we.exercise_id) AS total_exercises,
        SUM(we.rest_seconds) AS total_rest_seconds
    FROM workouts w
    INNER JOIN training_programs tp 
        ON tp.id = w.program_id
    LEFT JOIN workout_exercises we 
        ON we.workout_id = w.id
    WHERE tp.member_id = :member_id
    GROUP BY w.id, w.name, w.workout_number
    ORDER BY w.workout_number ASC
", [
    ":member_id" => $userId
]);
?>
<!-- Nuværende rutine -->
<section class="container-fluid mb-5">
    <div class="row mb-5">
        <h1 class="montserrat fw-bold">Dagens Rutine</h1>

        <?php

        function formatTimeFromSeconds($seconds) {
            $seconds = (int)$seconds;

            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);

            return [$hours, $minutes];
        }

        if (!empty($workouts)) {

            $first = $workouts[0];
            $rest  = array_slice($workouts, 1);

            list($hours, $minutes) = formatTimeFromSeconds($first->total_rest_seconds ?? 0);
            ?>

            <!-- Dagens Rutine -->
            <div class="col-12 d-sm-flex justify-content-between align-items-center bg-white rounded-4 p-3">

                <div>
                    <h3 class="montserrat fw-bold mb-3 fs-4">
                        Dag <?= (int)$first->workout_number ?> - <?= htmlspecialchars($first->name, ENT_QUOTES, 'UTF-8') ?>
                    </h3>

                    <div class="gap-3 mb-3 mb-md-0 d-flex">

                        <div class="d-flex flex-center">
                            <div class="border border-2 border-dark rounded-circle me-2" style="width:10px;height:10px">
                            </div>
                            <span><b>Øvelser:<span class="text-primary"><?= (int)$first->total_exercises ?></span></b></span>
                        </div>

                        <div class="d-flex flex-center">
                            <div class="border border-2 border-dark rounded-circle me-2" style="width:10px;height:10px"></div>
                            <span><b>~<span class="text-primary">
                            <?php if ($hours > 0) {
                                echo "{$hours}t {$minutes}min";
                            } else {
                                echo "{$minutes}min";
                            }?>
                            </span></b></span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-3 justify-content-between">
                    <a href="showRoutineDay.php?workout_id=<?= $first->id ?>" class="btn rounded-3 bg-light px-5">Info</a>
                    <a href="startRoutine.php?workout_id=<?= $first->id ?>" class='btn rounded-3 bg-primary text-white fw-bold px-5'>Start</a>
                </div>

            </div>

        <?php } ?>

    </div>

    <!-- Næste Rutiner -->
    <div class="row">
        <?php if (!empty($rest)) {
            echo '<h2 class="fw-normal">Næste Rutiner</h2>';
        } elseif (empty($workouts)) {
            echo '
                <p class="fw-normal fs-3 text-center mb-5">Ingen rutine lavet endnu</p>
                <div class="bg-white rounded-4 p-3 d-flex justify-content-between">
                    <div class="d-flex flex-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="39" height="39" viewBox="0 0 39 39" fill="none">
                            <g clip-path="url(#clip0_187_269)">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M26.9942 0.162987C27.229 0.299116 27.4108 0.510852 27.5097 0.763606C27.6086 1.01636 27.6189 1.29519 27.5389 1.55454L23.2178 15.5949H31.1905C31.4248 15.5948 31.654 15.6633 31.8498 15.792C32.0456 15.9206 32.1994 16.1038 32.2923 16.3189C32.3852 16.534 32.413 16.7716 32.3724 17.0024C32.3318 17.2331 32.2245 17.4469 32.0638 17.6174L12.8699 38.0109C12.6842 38.2084 12.4371 38.3372 12.1688 38.3762C11.9005 38.4153 11.627 38.3623 11.3927 38.2259C11.1583 38.0896 10.9771 37.8779 10.8785 37.6254C10.78 37.3728 10.7698 37.0943 10.8498 36.8353L15.1708 22.7926H7.19814C6.96385 22.7927 6.73465 22.7241 6.53885 22.5955C6.34305 22.4668 6.18921 22.2836 6.09633 22.0685C6.00345 21.8534 5.97559 21.6158 6.0162 21.3851C6.0568 21.1543 6.16409 20.9405 6.32482 20.77L25.5187 0.376519C25.7042 0.1793 25.9509 0.0506276 26.2188 0.011424C26.4867 -0.0277797 26.76 0.0247909 26.9942 0.160588V0.162987Z" fill="#E93C79"/>
                            </g>
                            <defs>
                                <clipPath id="clip0_187_269">
                                    <rect width="38.3878" height="38.3878" fill="white"/>
                                </clipPath>
                            </defs>
                        </svg>
                        <p class="mb-0 ms-2 montserrat fw-bold fs-4">Generer rutine</p>
                    </div>
                    <a href="generate.php" class="bg-info rounded-3 flex-center gap-3 px-3">
                        <p class="mb-0 montserrat fs-5 text-white">Begynd</p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 38 38" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M2.375 19.0003C2.375 18.6853 2.50011 18.3833 2.72281 18.1606C2.94551 17.9379 3.24756 17.8128 3.5625 17.8128H31.5709L24.0968 10.341C23.8738 10.118 23.7485 9.81562 23.7485 9.50028C23.7485 9.18494 23.8738 8.88251 24.0968 8.65953C24.3197 8.43655 24.6222 8.31128 24.9375 8.31128C25.2528 8.31128 25.5553 8.43655 25.7783 8.65953L35.2783 18.1595C35.3888 18.2698 35.4766 18.4009 35.5364 18.5451C35.5963 18.6894 35.6271 18.8441 35.6271 19.0003C35.6271 19.1565 35.5963 19.3111 35.5364 19.4554C35.4766 19.5997 35.3888 19.7307 35.2783 19.841L25.7783 29.341C25.5553 29.564 25.2528 29.6893 24.9375 29.6893C24.6222 29.6893 24.3197 29.564 24.0968 29.341C23.8738 29.118 23.7485 28.8156 23.7485 28.5003C23.7485 28.1849 23.8738 27.8825 24.0968 27.6595L31.5709 20.1878H3.5625C3.24756 20.1878 2.94551 20.0627 2.72281 19.84C2.50011 19.6173 2.375 19.3152 2.375 19.0003Z" fill="white"/>
                        </svg>
                    </a>
                </div>
            ';
        }
        ?>

        <?php if (!empty($rest)): ?>

            <?php foreach ($rest as $workout): ?>

                <?php
                list($rHours, $rMinutes) = formatTimeFromSeconds($workout->total_rest_seconds ?? 0);
                ?>

                <div class="col-12 d-md-flex justify-content-between align-items-center bg-white rounded-4 p-3 mb-3">

                    <div>
                        <h3 class="montserrat fw-bold mb-3 fs-4">
                            Dag <?= (int)$workout->workout_number ?> - <?= htmlspecialchars($workout->name, ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <div class="gap-3 mb-3 mb-md-0 d-flex">

                            <div class="d-flex flex-center">
                                <div class="border border-2 border-dark rounded-circle me-2"
                                     style="width:10px;height:10px"></div>
                                <span><b>Øvelser:
                            <span class="text-secondary"><?= (int)$workout->total_exercises ?></span>
                        </b></span>
                            </div>

                            <div class="d-flex flex-center">
                                <div class="border border-2 border-dark rounded-circle me-2"
                                     style="width:10px;height:10px"></div>
                                <span><b>Pause:
                            <span class="text-secondary">
                                <?php
                                if ($rHours > 0) {
                                    echo "{$rHours}t {$rMinutes}min";
                                } else {
                                    echo "{$rMinutes}min";
                                }
                                ?>
                            </span>
                        </b></span>
                            </div>

                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-between">
                        <a href="showRoutineDay.php?workout_id=<?= $workout->id ?>" class="btn rounded-3 bg-light px-5">Info</a>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</section>


<!-- Bottom Nav -->
<footer class="mt-auto rounded-top-circle bg-white position-sticky bottom-0" style="min-height: 85px;">
    <div class="d-flex justify-content-around" style="margin-top: -25px; margin-bottom: 25px;">
        <!-- Add Button -->
        <div class="flex-column-center gap-1" >
            <a class="p-3 bg-secondary rounded-circle" href="addExercise.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M22.5 4C23.1133 4 23.7015 4.24364 24.1352 4.67732C24.5689 5.11099 24.8125 5.69919 24.8125 6.3125V20.1875H38.6875C39.3008 20.1875 39.889 20.4311 40.3227 20.8648C40.7564 21.2985 41 21.8867 41 22.5C41 23.1133 40.7564 23.7015 40.3227 24.1352C39.889 24.5689 39.3008 24.8125 38.6875 24.8125H24.8125V38.6875C24.8125 39.3008 24.5689 39.889 24.1352 40.3227C23.7015 40.7564 23.1133 41 22.5 41C21.8867 41 21.2985 40.7564 20.8648 40.3227C20.4311 39.889 20.1875 39.3008 20.1875 38.6875V24.8125H6.3125C5.69919 24.8125 5.11099 24.5689 4.67732 24.1352C4.24364 23.7015 4 23.1133 4 22.5C4 21.8867 4.24364 21.2985 4.67732 20.8648C5.11099 20.4311 5.69919 20.1875 6.3125 20.1875H20.1875V6.3125C20.1875 5.69919 20.4311 5.11099 20.8648 4.67732C21.2985 4.24364 21.8867 4 22.5 4V4Z"
                          fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0 text-nowrap">Tilføj Øvelse</p>
        </div>

        <!-- Generate Button -->
        <div class="flex-column-center gap-1" >
            <a class="p-3 bg-info rounded-circle" href="generate.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
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
            <p class="text-center montserrat fw-bold m-0">Generer</p>
        </div>

        <!-- Start Button -->
        <?php if (empty($first)): ?>
            <div class="flex-column-center gap-1" >
                <button class="btn p-3 btn-grays rounded-circle" id="noRoutine">
                    <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
                        <path d="M38.3941 4.06112L35.8598 1.58075C34.8178 0.539769 33.467 0 32.0905 0C30.714 0 29.2989 0.539769 28.2569 1.58075C27.5622 2.27474 27.1377 3.1101 26.9061 3.99686C26.533 3.91975 26.1728 3.82979 25.774 3.82979C24.3975 3.82979 22.9824 4.3053 21.9404 5.34628C19.8563 7.42825 19.8563 10.8596 21.9404 12.9416L23.4584 14.4581L14.4533 23.4542L12.9352 21.9377C11.8932 20.8968 10.5425 20.357 9.16595 20.357C7.78945 20.357 6.37436 20.8968 5.33233 21.9377C3.98156 23.2872 3.5313 25.2021 3.9301 26.9499C3.06818 27.1684 2.24485 27.5668 1.56304 28.2479C-0.521012 30.3299 -0.521012 33.7612 1.56304 35.8432L1.61449 35.8946L4.03302 38.375L6.61878 40.9582L9.15309 43.4385C11.2371 45.5205 14.672 45.5205 16.756 43.4385C17.4378 42.7574 17.8624 41.9349 18.1068 41.0738C19.8435 41.4465 21.7217 41.0096 23.0596 39.673C25.1437 37.591 25.1437 34.1596 23.0596 32.0777L21.5416 30.5612L30.5467 21.565L32.0648 23.0815C34.1488 25.1635 37.5836 25.1635 39.6677 23.0815C40.9927 21.745 41.443 19.8558 41.0699 18.1337C41.9447 17.8895 42.7551 17.4654 43.437 16.7842C45.521 14.7023 45.521 11.2709 43.437 9.18892L40.9541 6.65715L38.3684 4.07397L38.3941 4.06112ZM32.0776 3.61131C32.5279 3.61131 32.9653 3.76553 33.3126 4.11252L40.9155 11.7078C41.6231 12.4147 41.6231 13.4814 40.9155 14.1882C40.208 14.895 39.0888 14.895 38.3812 14.1882L30.8298 6.65715C30.1222 5.95031 30.1222 4.83221 30.8298 4.12537C31.1771 3.77838 31.6145 3.62416 32.0648 3.62416L32.0776 3.61131ZM25.774 7.37684C26.2243 7.37684 26.6617 7.58247 27.009 7.94231L37.0948 18.0051C37.8023 18.712 37.8023 19.8301 37.0948 20.5369C36.3872 21.2438 35.3323 21.1795 34.6119 20.4855H34.5605L24.4747 10.4227C23.7672 9.71584 23.8315 8.662 24.5262 7.94231C24.8735 7.60817 25.3238 7.37684 25.7612 7.37684H25.774ZM25.9927 17.0027L28.0253 19.0333L19.0202 28.0294L16.9876 25.9989L25.9927 17.0027ZM9.15309 23.9683C9.60334 23.9683 10.0407 24.1739 10.3881 24.5338H10.4395L20.5253 34.5966C21.2328 35.3034 21.1685 36.3573 20.4738 37.077C19.7663 37.7453 18.6985 37.771 17.991 37.077L16.756 35.8432L16.5244 35.6761L9.15309 28.2479L7.9181 27.0141C7.21055 26.3073 7.21055 25.1892 7.9181 24.4824C8.26544 24.1354 8.70283 23.9812 9.15309 23.9812V23.9683ZM5.33233 30.2656C5.78259 30.2656 6.27144 30.4712 6.63165 30.8311L14.1831 38.3621C14.8907 39.069 14.8907 40.1871 14.1831 40.8939C13.4756 41.6007 12.4207 41.6007 11.7003 40.8939L4.09734 33.2986C3.38979 32.5917 3.38979 31.5251 4.09734 30.8182C4.44468 30.4712 4.88208 30.2528 5.33233 30.2528V30.2656Z"
                              fill="white"/>
                    </svg>
                </button>
                <p class="text-center montserrat fw-bold m-0 text-nowrap"><?= !empty($first) ? "Start Rutine" : "Ingen Rutine"?></p>
            </div>
        <?php else: ?>
            <div class="flex-column-center gap-1">
                <a class="p-3 bg-primary rounded-circle" href="startRoutine.php?workout_id=<?= $first->id ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
                        <path d="M38.3941 4.06112L35.8598 1.58075C34.8178 0.539769 33.467 0 32.0905 0C30.714 0 29.2989 0.539769 28.2569 1.58075C27.5622 2.27474 27.1377 3.1101 26.9061 3.99686C26.533 3.91975 26.1728 3.82979 25.774 3.82979C24.3975 3.82979 22.9824 4.3053 21.9404 5.34628C19.8563 7.42825 19.8563 10.8596 21.9404 12.9416L23.4584 14.4581L14.4533 23.4542L12.9352 21.9377C11.8932 20.8968 10.5425 20.357 9.16595 20.357C7.78945 20.357 6.37436 20.8968 5.33233 21.9377C3.98156 23.2872 3.5313 25.2021 3.9301 26.9499C3.06818 27.1684 2.24485 27.5668 1.56304 28.2479C-0.521012 30.3299 -0.521012 33.7612 1.56304 35.8432L1.61449 35.8946L4.03302 38.375L6.61878 40.9582L9.15309 43.4385C11.2371 45.5205 14.672 45.5205 16.756 43.4385C17.4378 42.7574 17.8624 41.9349 18.1068 41.0738C19.8435 41.4465 21.7217 41.0096 23.0596 39.673C25.1437 37.591 25.1437 34.1596 23.0596 32.0777L21.5416 30.5612L30.5467 21.565L32.0648 23.0815C34.1488 25.1635 37.5836 25.1635 39.6677 23.0815C40.9927 21.745 41.443 19.8558 41.0699 18.1337C41.9447 17.8895 42.7551 17.4654 43.437 16.7842C45.521 14.7023 45.521 11.2709 43.437 9.18892L40.9541 6.65715L38.3684 4.07397L38.3941 4.06112ZM32.0776 3.61131C32.5279 3.61131 32.9653 3.76553 33.3126 4.11252L40.9155 11.7078C41.6231 12.4147 41.6231 13.4814 40.9155 14.1882C40.208 14.895 39.0888 14.895 38.3812 14.1882L30.8298 6.65715C30.1222 5.95031 30.1222 4.83221 30.8298 4.12537C31.1771 3.77838 31.6145 3.62416 32.0648 3.62416L32.0776 3.61131ZM25.774 7.37684C26.2243 7.37684 26.6617 7.58247 27.009 7.94231L37.0948 18.0051C37.8023 18.712 37.8023 19.8301 37.0948 20.5369C36.3872 21.2438 35.3323 21.1795 34.6119 20.4855H34.5605L24.4747 10.4227C23.7672 9.71584 23.8315 8.662 24.5262 7.94231C24.8735 7.60817 25.3238 7.37684 25.7612 7.37684H25.774ZM25.9927 17.0027L28.0253 19.0333L19.0202 28.0294L16.9876 25.9989L25.9927 17.0027ZM9.15309 23.9683C9.60334 23.9683 10.0407 24.1739 10.3881 24.5338H10.4395L20.5253 34.5966C21.2328 35.3034 21.1685 36.3573 20.4738 37.077C19.7663 37.7453 18.6985 37.771 17.991 37.077L16.756 35.8432L16.5244 35.6761L9.15309 28.2479L7.9181 27.0141C7.21055 26.3073 7.21055 25.1892 7.9181 24.4824C8.26544 24.1354 8.70283 23.9812 9.15309 23.9812V23.9683ZM5.33233 30.2656C5.78259 30.2656 6.27144 30.4712 6.63165 30.8311L14.1831 38.3621C14.8907 39.069 14.8907 40.1871 14.1831 40.8939C13.4756 41.6007 12.4207 41.6007 11.7003 40.8939L4.09734 33.2986C3.38979 32.5917 3.38979 31.5251 4.09734 30.8182C4.44468 30.4712 4.88208 30.2528 5.33233 30.2528V30.2656Z"
                              fill="white"/>
                    </svg>
                </a>
                <p class="text-center montserrat fw-bold m-0 text-nowrap"><?= !empty($first) ? "Start Rutine" : "Ingen Rutine"?></p>
            </div>
        <?php endif ?>
    </div>
</footer>

<script>
    const noRoutine = document.querySelector("#noRoutine")

    noRoutine.addEventListener("click", function(){ alert("Du har endnu ikke generet nogen rutine"); })
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
