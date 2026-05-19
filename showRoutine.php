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
<div class="container mb-3">
    <div class="mb-4 mt-3 text-center">
        <h1 class="montserrat fw-bold"> <?php echo $totalWorkouts ?> Rutiner genereret</h1>
        <p class="fs-5 text-gray"><?php echo $totalWorkouts ?> Rutiner fyldt med øvelser som passer dine behov</p>
    </div>
    <?php foreach ($workouts as $workout): ?>
        <div class="row bg-white rounded-4 p-3 mx-0 mb-4">
            <div class="d-flex justify-content-between">
                <h2 class="montserrat fw-bold text-dark">
                    Day <?= (int)$workout->workout_number ?> - <?= htmlspecialchars($workout->name) ?>
                </h2>
            </div>

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
                <p>Ingen Rutiner Fundet</p>
            <?php else: ?>

                <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Øvelser</th>
                            <!-- <th>Beskrivelse</th> -->
                            <th>Sets</th>
                            <th>Reps</th>
                            <th>Hviletid</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($exercises as $index => $ex): ?>
                            <tr>
                                <td><?= (int)$ex->exercise_order ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($ex->name) ?></strong>
                                </td>
                                <!-- <td> --> <?php //= htmlspecialchars($ex->description) ?> <!-- </td> -->
                                <td><?= (int)$ex->sets ?></td>
                                <td><?= (int)$ex->reps ?></td>
                                <td><?= (int)$ex->rest_seconds ?>s</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    <?php endforeach; ?>

</div>


<!-- Bottom Nav -->
<footer class="mt-auto rounded-top-circle bg-white position-sticky bottom-0" style="min-height: 85px;">
    <div class="d-flex justify-content-around" style="margin-top: -25px; margin-bottom: 25px;">
        <!-- Generate Button -->
        <div class="flex-column-center gap-1" >
            <a class="p-3 bg-grays rounded-circle" href="generate.php">
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
            <p class="text-center montserrat fw-bold m-0">Generer Ny</p>
        </div>

        <!-- Add Button -->
        <div class="flex-column-center gap-1" >
            <a class="p-3 bg-secondary rounded-circle" href="index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M13.0694 13.0667C13.2 12.9357 13.3552 12.8318 13.526 12.7609C13.6969 12.6901 13.88 12.6536 14.065 12.6536C14.25 12.6536 14.4331 12.6901 14.604 12.7609C14.7748 12.8318 14.93 12.9357 15.0606 13.0667L22.5025 20.5114L29.9444 13.0667C30.0751 12.936 30.2304 12.8322 30.4012 12.7615C30.572 12.6907 30.7551 12.6543 30.94 12.6543C31.1249 12.6543 31.308 12.6907 31.4788 12.7615C31.6497 12.8322 31.8049 12.936 31.9356 13.0667C32.0664 13.1974 32.1701 13.3527 32.2409 13.5235C32.3116 13.6943 32.348 13.8774 32.348 14.0623C32.348 14.2472 32.3116 14.4303 32.2409 14.6012C32.1701 14.772 32.0664 14.9272 31.9356 15.058L24.4909 22.4998L31.9356 29.9417C32.0664 30.0724 32.1701 30.2277 32.2409 30.3985C32.3116 30.5693 32.348 30.7524 32.348 30.9373C32.348 31.1222 32.3116 31.3053 32.2409 31.4762C32.1701 31.647 32.0664 31.8022 31.9356 31.933C31.8049 32.0637 31.6497 32.1674 31.4788 32.2382C31.308 32.3089 31.1249 32.3454 30.94 32.3454C30.7551 32.3454 30.572 32.3089 30.4012 32.2382C30.2304 32.1674 30.0751 32.0637 29.9444 31.933L22.5025 24.4883L15.0606 31.933C14.9299 32.0637 14.7747 32.1674 14.6038 32.2382C14.433 32.3089 14.2499 32.3454 14.065 32.3454C13.8801 32.3454 13.697 32.3089 13.5262 32.2382C13.3554 32.1674 13.2001 32.0637 13.0694 31.933C12.9386 31.8022 12.8349 31.647 12.7642 31.4762C12.6934 31.3053 12.657 31.1222 12.657 30.9373C12.657 30.7524 12.6934 30.5693 12.7642 30.3985C12.8349 30.2277 12.9386 30.0724 13.0694 29.9417L20.5141 22.4998L13.0694 15.058C12.9384 14.9273 12.8345 14.7721 12.7636 14.6013C12.6927 14.4305 12.6562 14.2473 12.6562 14.0623C12.6562 13.8774 12.6927 13.6942 12.7636 13.5234C12.8345 13.3525 12.9384 13.1973 13.0694 13.0667Z" fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0 text-nowrap">Hjem</p>
        </div>
    </div>
</footer>

<script src="scripts/canvas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
