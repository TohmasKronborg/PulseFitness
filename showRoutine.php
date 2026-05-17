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

// 1. Get latest program for user
$program = $db->sql("
    SELECT id, name
    FROM training_programs
    WHERE member_id = :member_id
    ORDER BY id DESC
    LIMIT 1
", [
    ":member_id" => $userId
]);

if (!$program) {
    exit("No program found");
}

$programId = $program[0]->id;

// 2. Get all workouts (days)
$workouts = $db->sql("
    SELECT id, workout_number, name
    FROM workouts
    WHERE program_id = :program_id
    ORDER BY workout_number ASC
", [
    ":program_id" => $programId
]);

$totalWorkouts = $db->sql("
    SELECT COUNT(*) AS total
    FROM workouts
    WHERE program_id = :program_id
", [
    ":program_id" => $programId
]);

$totalWorkouts = $totalWorkouts[0]->total;

?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">

    <title>Færdig Rutine</title>

    <meta name="robots" content="All">
    <meta name="author" content="Udgiver">
    <meta name="copyright" content="Information om copyright">

    <link rel="stylesheet" href="css/styles.css" type="text/css">
    <link rel="icon" href="images/LogoBlack.png">

    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body class="mx-auto flex-column position-relative overflow-x-hidden" style="max-width: 768px; height: 100vh; ">

<!-- Top Nav -->
<nav class="flex-column">
    <div class="bg-info flex-center h-auto">
        <a class="mx-auto my-3" href="index.php"><img src="images/LogoWhite.png" alt="WhiteLogo" class="img-fluid" style="max-width: 100px;"></a>
        <?php include "include/navButtons.php" ?>
    </div>
    <img src="images/VectorInfo.svg" alt="dims">
</nav>

<!-- Routine Days -->
<div class="container">
    <h1 class="montserrat fw-bold mt-3 mb-5 text-center"> <?php echo $totalWorkouts ?> Rutiner genereret</h1>
    <p></p>
    <?php foreach ($workouts as $workout): ?>
        <div class="row bg-white rounded-4 p-3 mx-0 mb-3">
            <h2 class="montserrat fw-bold ">
                Day <?= (int)$workout->workout_number ?> - <?= htmlspecialchars($workout->name) ?>
            </h2>

        <?php
        // 3. Get exercises per workout
        $exercises = $db->sql("
            SELECT
                e.name,
                e.description,
                we.exercise_order,
                we.sets,
                we.reps,
                we.rest_seconds
            FROM workout_exercises we
            INNER JOIN exercises e ON we.exercise_id = e.id
            WHERE we.workout_id = :workout_id
            ORDER BY we.exercise_order ASC
        ", [
            ":workout_id" => $workout->id
        ]);
        ?>

        <?php if (!$exercises): ?>
            <p>No exercises found.</p>
        <?php else: ?>

            <ul>
                <?php foreach ($exercises as $ex): ?>
                    <li>
                        <strong><?= htmlspecialchars($ex->name) ?></strong><br>
                        <?= htmlspecialchars($ex->description) ?><br>
                        Sets: <?= (int)$ex->sets ?> |
                        Reps: <?= (int)$ex->reps ?> |
                        Rest: <?= (int)$ex->rest_seconds ?>s
                    </li>
                <?php endforeach; ?>
            </ul>

        <?php endif; ?>

        </div>
    <?php endforeach; ?>

</div>


<!-- Bottom Nav -->
<footer class="mt-auto rounded-top-circle bg-white position-sticky bottom-0" style="min-height: 85px;">
    <div class="d-flex justify-content-around" style="margin-top: -25px; margin-bottom: 25px;">
        <!-- Add Button -->
        <div class="flex-column-center gap-1" >
            <a class="p-3 bg-secondary rounded-circle" href="addExercise.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45" fill="none">
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
            <p class="text-center montserrat fw-bold m-0">Generer</p>
        </div>

        <!-- Start Button -->
        <div class="flex-column-center gap-1" >
            <a class="p-3 bg-primary rounded-circle" href="generate.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45" fill="none">
                    <path d="M38.3941 4.06112L35.8598 1.58075C34.8178 0.539769 33.467 0 32.0905 0C30.714 0 29.2989 0.539769 28.2569 1.58075C27.5622 2.27474 27.1377 3.1101 26.9061 3.99686C26.533 3.91975 26.1728 3.82979 25.774 3.82979C24.3975 3.82979 22.9824 4.3053 21.9404 5.34628C19.8563 7.42825 19.8563 10.8596 21.9404 12.9416L23.4584 14.4581L14.4533 23.4542L12.9352 21.9377C11.8932 20.8968 10.5425 20.357 9.16595 20.357C7.78945 20.357 6.37436 20.8968 5.33233 21.9377C3.98156 23.2872 3.5313 25.2021 3.9301 26.9499C3.06818 27.1684 2.24485 27.5668 1.56304 28.2479C-0.521012 30.3299 -0.521012 33.7612 1.56304 35.8432L1.61449 35.8946L4.03302 38.375L6.61878 40.9582L9.15309 43.4385C11.2371 45.5205 14.672 45.5205 16.756 43.4385C17.4378 42.7574 17.8624 41.9349 18.1068 41.0738C19.8435 41.4465 21.7217 41.0096 23.0596 39.673C25.1437 37.591 25.1437 34.1596 23.0596 32.0777L21.5416 30.5612L30.5467 21.565L32.0648 23.0815C34.1488 25.1635 37.5836 25.1635 39.6677 23.0815C40.9927 21.745 41.443 19.8558 41.0699 18.1337C41.9447 17.8895 42.7551 17.4654 43.437 16.7842C45.521 14.7023 45.521 11.2709 43.437 9.18892L40.9541 6.65715L38.3684 4.07397L38.3941 4.06112ZM32.0776 3.61131C32.5279 3.61131 32.9653 3.76553 33.3126 4.11252L40.9155 11.7078C41.6231 12.4147 41.6231 13.4814 40.9155 14.1882C40.208 14.895 39.0888 14.895 38.3812 14.1882L30.8298 6.65715C30.1222 5.95031 30.1222 4.83221 30.8298 4.12537C31.1771 3.77838 31.6145 3.62416 32.0648 3.62416L32.0776 3.61131ZM25.774 7.37684C26.2243 7.37684 26.6617 7.58247 27.009 7.94231L37.0948 18.0051C37.8023 18.712 37.8023 19.8301 37.0948 20.5369C36.3872 21.2438 35.3323 21.1795 34.6119 20.4855H34.5605L24.4747 10.4227C23.7672 9.71584 23.8315 8.662 24.5262 7.94231C24.8735 7.60817 25.3238 7.37684 25.7612 7.37684H25.774ZM25.9927 17.0027L28.0253 19.0333L19.0202 28.0294L16.9876 25.9989L25.9927 17.0027ZM9.15309 23.9683C9.60334 23.9683 10.0407 24.1739 10.3881 24.5338H10.4395L20.5253 34.5966C21.2328 35.3034 21.1685 36.3573 20.4738 37.077C19.7663 37.7453 18.6985 37.771 17.991 37.077L16.756 35.8432L16.5244 35.6761L9.15309 28.2479L7.9181 27.0141C7.21055 26.3073 7.21055 25.1892 7.9181 24.4824C8.26544 24.1354 8.70283 23.9812 9.15309 23.9812V23.9683ZM5.33233 30.2656C5.78259 30.2656 6.27144 30.4712 6.63165 30.8311L14.1831 38.3621C14.8907 39.069 14.8907 40.1871 14.1831 40.8939C13.4756 41.6007 12.4207 41.6007 11.7003 40.8939L4.09734 33.2986C3.38979 32.5917 3.38979 31.5251 4.09734 30.8182C4.44468 30.4712 4.88208 30.2528 5.33233 30.2528V30.2656Z"
                          fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0 text-nowrap">Start Rutine</p>
        </div>
    </div>
</footer>

<script src="scripts/canvas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
