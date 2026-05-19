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

$workoutId = $_GET['workout_id'] ?? null;

if (!$workoutId) {
    exit("Missing workout_id");
}

//1. Get workout
$workout = $db->sql("
    SELECT w.id, w.workout_number, w.name, w.program_id
    FROM workouts w
    INNER JOIN training_programs p ON p.id = w.program_id
    WHERE w.id = :id
      AND p.member_id = :user_id
    LIMIT 1
", [
    ":id" => $workoutId,
    ":user_id" => $userId
]);

if (!$workout) {
    exit("Workout not found");
}

$workout = $workout[0];
$programId = $workout->program_id;


// 2. Get exercises for this workout
$exercises = $db->sql("
    SELECT 
        e.id,
        e.name,
        e.description,
        we.exercise_order,
        we.sets,
        we.reps,
        we.rest_seconds,
        e.difficulty_id,
        e.goal_id,
        mg.name AS muscle_group_name
    FROM workout_exercises we

    INNER JOIN exercises e 
        ON e.id = we.exercise_id

    LEFT JOIN exercise_muscle_groups emg
        ON emg.exercise_id = e.id

    LEFT JOIN muscle_groups mg
        ON mg.id = emg.muscle_group_id

    WHERE we.workout_id = :workout_id

    ORDER BY we.exercise_order ASC
", [
    ":workout_id" => $workoutId
]);


// 3. Total amount of workouts
$total_workouts = $db->sql("
    SELECT COUNT(*) AS total_workouts
    FROM workouts w
    INNER JOIN training_programs p ON p.id = w.program_id
    WHERE p.member_id = :user_id
", [
    ":user_id" => $userId
]);

function formatTimeMMSS($seconds) {
    return sprintf('%02d:%02d', floor($seconds / 60), $seconds % 60);
}
?>

<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">

    <title>Tilføj Øvelse</title>

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

<div class="mx-2 my-4">
    <h1 class="montserrat fw-bold">
        Dag <?= (int)$workout->workout_number ?> - <?= htmlspecialchars($workout->name, ENT_QUOTES, 'UTF-8') ?>
    </h1>
    <p class="text-gray"><?= 3 ?> / <?= $total_workouts ?> Øvelser Fuldført</p>
</div>

<!-- Tasks Container -->
<div class="container-fluid mb-5">
    <!-- Default -->
    <div class="row bg-white rounded-4 p-3 m-0 justify-content-between mb-4">
        <div class="col-12 p-0">
            <h2 class="fs-4 m-0"><b class="montserrat"><?= $exercises[0]->name ?></b> — <?= $exercises[0]->muscle_group_name?></h2>
            <p class="m-0"><?= $exercises[0]->sets ?> set af <?= $exercises[0]->reps ?></p>
        </div>

        <hr class="my-2">

        <!-- Set indicators -->
        <div class="col-11 p-0 pe-3">
            <!-- Active -->
            <div class="row p-0 m-0">
                <div class="montserrat p-0 bg-secondary text-white p-2 rounded-3 d-flex mb-3 col-11">
                    <p class="m-0 me-5">Set 1</p>
                    <p class="m-0"><b><?= $exercises[0]->reps ?></b> Reps</p>
                </div>
                <!-- Arrow -->
                <div class="col-1 d-flex justify-content-center pt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <g clip-path="url(#clip0_135_951)">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M23.999 12.0002C23.999 12.4169 23.841 12.8166 23.5597 13.1112C23.2785 13.4059 22.897 13.5714 22.4992 13.5714H5.12213L11.5624 20.3147C11.7019 20.4608 11.8125 20.6342 11.8879 20.8251C11.9634 21.016 12.0023 21.2205 12.0023 21.4271C12.0023 21.6337 11.9634 21.8383 11.8879 22.0291C11.8125 22.22 11.7019 22.3934 11.5624 22.5395C11.423 22.6856 11.2574 22.8014 11.0752 22.8805C10.893 22.9596 10.6977 23.0002 10.5005 23.0002C10.3033 23.0002 10.108 22.9596 9.92585 22.8805C9.74365 22.8014 9.5781 22.6856 9.43865 22.5395L0.439655 13.1126C0.299981 12.9667 0.189165 12.7933 0.113554 12.6024C0.0379429 12.4115 -0.000976563 12.2069 -0.000976562 12.0002C-0.000976563 11.7936 0.0379429 11.589 0.113554 11.3981C0.189165 11.2072 0.299981 11.0338 0.439655 10.8879L9.43865 1.461C9.72028 1.16598 10.1022 1.00024 10.5005 1.00024C10.8988 1.00024 11.2808 1.16598 11.5624 1.461C11.844 1.75602 12.0023 2.15615 12.0023 2.57337C12.0023 2.99059 11.844 3.39073 11.5624 3.68575L5.12213 10.4291H22.4992C22.897 10.4291 23.2785 10.5946 23.5597 10.8893C23.841 11.1839 23.999 11.5836 23.999 12.0002Z" fill="#525FDD"/>
                        </g>
                        <defs>
                            <clipPath id="clip0_135_951">
                                <rect width="24" height="24" fill="white"/>
                            </clipPath>
                        </defs>
                    </svg>
                </div>
            </div>

            <!-- Inactive -->
            <div class="col-11">
                <div class="montserrat p-0 bg-light text-dark p-2 rounded-3 d-flex mb-3">
                    <p class="m-0 me-5">Set 2</p>
                    <p class="m-0"><b><?= $exercises[0]->reps ?></b> Reps</p>
                </div>
            </div>

            <!-- Inactive -->
            <div class="col-11">
                <div class="montserrat p-0 bg-light text-dark p-2 rounded-3 d-flex">
                    <p class="m-0 me-5">Set 3</p>
                    <p class="m-0"><b><?= $exercises[0]->reps ?></b> Reps</p>
                </div>
            </div>
        </div>

        <!-- Check -->
        <div class="col-1 flex-center bg-primary rounded-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M20.7805 5.46888C20.8503 5.53854 20.9057 5.62131 20.9436 5.71243C20.9814 5.80354 21.0008 5.90123 21.0008 5.99988C21.0008 6.09853 20.9814 6.19621 20.9436 6.28733C20.9057 6.37844 20.8503 6.46121 20.7805 6.53088L10.2805 17.0309C10.2108 17.1007 10.1281 17.1561 10.0369 17.1939C9.94582 17.2318 9.84813 17.2512 9.74948 17.2512C9.65083 17.2512 9.55315 17.2318 9.46203 17.1939C9.37091 17.1561 9.28815 17.1007 9.21848 17.0309L3.96848 11.7809C3.82765 11.64 3.74854 11.449 3.74854 11.2499C3.74854 11.0507 3.82765 10.8597 3.96848 10.7189C4.10931 10.578 4.30032 10.4989 4.49948 10.4989C4.69865 10.4989 4.88965 10.578 5.03048 10.7189L9.74948 15.4394L19.7185 5.46888C19.7882 5.39903 19.8709 5.34362 19.962 5.30581C20.0532 5.268 20.1508 5.24854 20.2495 5.24854C20.3481 5.24854 20.4458 5.268 20.5369 5.30581C20.628 5.34362 20.7108 5.39903 20.7805 5.46888Z"
                      fill="white"/>
            </svg>
        </div>

    </div>

    <!-- Waiting -->
    <div class="row bg-white rounded-4 p-3 m-0 justify-content-between mb-4">
        <div class="col-12 p-0">
            <h2 class="fs-4 m-0"><b class="montserrat"><?= $exercises[2]->name ?></b> — <?= $exercises[2]->muscle_group_name?></h2>
            <p class="m-0"><?= $exercises[2]->sets ?> set af <?= $exercises[2]->reps ?></p>
        </div>

        <hr class="my-2">

        <!-- Set indicators -->
        <div class="col-11 p-0 pe-3">
            <!-- Done -->
            <div class="col-12 p-0">
                <div class="montserrat p-0 bg-light text-gray p-2 rounded-3 d-flex mb-3">
                    <p class="m-0 me-5"><s>Set 2</s></p>
                    <p class="m-0"><s><b><?= $exercises[3]->reps ?></b> Reps</s></p>
                </div>
            </div>

            <!-- Waiting -->
            <div class="row p-0 m-0">
                <div class="montserrat p-0 bg-secondary text-white p-2 rounded-3 d-flex mb-3 col-11">
                    <p class="m-0 me-5">Set 1</p>
                    <p class="m-0">Hvil - <b><?php echo formatTimeMMSS($exercises[2]->rest_seconds); ?></b></p>
                </div>
                <!-- Glock -->
                <div class="col-1 d-flex justify-content-center pt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <g clip-path="url(#clip0_135_1019)">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22.5C14.7848 22.5 17.4555 21.3938 19.4246 19.4246C21.3938 17.4555 22.5 14.7848 22.5 12C22.5 9.21523 21.3938 6.54451 19.4246 4.57538C17.4555 2.60625 14.7848 1.5 12 1.5C9.21523 1.5 6.54451 2.60625 4.57538 4.57538C2.60625 6.54451 1.5 9.21523 1.5 12C1.5 14.7848 2.60625 17.4555 4.57538 19.4246C6.54451 21.3938 9.21523 22.5 12 22.5ZM24 12C24 15.1826 22.7357 18.2348 20.4853 20.4853C18.2348 22.7357 15.1826 24 12 24C8.8174 24 5.76516 22.7357 3.51472 20.4853C1.26428 18.2348 0 15.1826 0 12C0 8.8174 1.26428 5.76516 3.51472 3.51472C5.76516 1.26428 8.8174 0 12 0C15.1826 0 18.2348 1.26428 20.4853 3.51472C22.7357 5.76516 24 8.8174 24 12Z" fill="#525FDD"/>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M11.25 4.5C11.4489 4.5 11.6397 4.57902 11.7803 4.71967C11.921 4.86032 12 5.05109 12 5.25V13.065L16.872 15.849C17.0397 15.9502 17.1612 16.1129 17.2104 16.3024C17.2597 16.492 17.2329 16.6933 17.1358 16.8633C17.0386 17.0333 16.8788 17.1586 16.6905 17.2124C16.5022 17.2661 16.3003 17.2441 16.128 17.151L10.878 14.151C10.7632 14.0854 10.6678 13.9907 10.6014 13.8764C10.535 13.762 10.5 13.6322 10.5 13.5V5.25C10.5 5.05109 10.579 4.86032 10.7197 4.71967C10.8603 4.57902 11.0511 4.5 11.25 4.5Z" fill="#525FDD"/>
                        </g>
                        <defs>
                            <clipPath id="clip0_135_1019">
                                <rect width="24" height="24" fill="white"/>
                            </clipPath>
                        </defs>
                    </svg>
                </div>
            </div>

            <!-- Inactive -->
            <div class="col-11">
                <div class="montserrat p-0 bg-light text-dark p-2 rounded-3 d-flex mb-3">
                    <p class="m-0 me-5">Set 2</p>
                    <p class="m-0"><b><?= $exercises[2]->reps ?></b> Reps</p>
                </div>
            </div>
        </div>

        <!-- Check -->
        <div class="col-1 flex-center bg-primary rounded-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M20.7805 5.46888C20.8503 5.53854 20.9057 5.62131 20.9436 5.71243C20.9814 5.80354 21.0008 5.90123 21.0008 5.99988C21.0008 6.09853 20.9814 6.19621 20.9436 6.28733C20.9057 6.37844 20.8503 6.46121 20.7805 6.53088L10.2805 17.0309C10.2108 17.1007 10.1281 17.1561 10.0369 17.1939C9.94582 17.2318 9.84813 17.2512 9.74948 17.2512C9.65083 17.2512 9.55315 17.2318 9.46203 17.1939C9.37091 17.1561 9.28815 17.1007 9.21848 17.0309L3.96848 11.7809C3.82765 11.64 3.74854 11.449 3.74854 11.2499C3.74854 11.0507 3.82765 10.8597 3.96848 10.7189C4.10931 10.578 4.30032 10.4989 4.49948 10.4989C4.69865 10.4989 4.88965 10.578 5.03048 10.7189L9.74948 15.4394L19.7185 5.46888C19.7882 5.39903 19.8709 5.34362 19.962 5.30581C20.0532 5.268 20.1508 5.24854 20.2495 5.24854C20.3481 5.24854 20.4458 5.268 20.5369 5.30581C20.628 5.34362 20.7108 5.39903 20.7805 5.46888Z"
                      fill="white"/>
            </svg>
        </div>

    </div>

    <!-- Nearly done -->
    <div class="row bg-white rounded-4 p-3 m-0 justify-content-between">
        <div class="col-12 p-0">
            <h2 class="fs-4 m-0"><b class="montserrat"><?= $exercises[3]->name ?></b> — <?= $exercises[3]->muscle_group_name?></h2>
            <p class="m-0"><?= $exercises[3]->sets ?> set af <?= $exercises[3]->reps ?></p>
        </div>

        <hr class="my-2">

        <!-- Set indicators -->
        <div class="col-11 p-0 pe-3">
            <!-- Done -->
            <div class="col-12 p-0">
                <div class="montserrat p-0 bg-light text-gray p-2 rounded-3 d-flex mb-3">
                    <p class="m-0 me-5"><s>Set 2</s></p>
                    <p class="m-0"><s><b><?= $exercises[3]->reps ?></b> Reps</s></p>
                </div>
            </div>

            <!-- Done -->
            <div class="col-12 p-0">
                <div class="montserrat p-0 bg-light text-gray p-2 rounded-3 d-flex mb-3">
                    <p class="m-0 me-5"><s>Set 2</s></p>
                    <p class="m-0"><s><b><?= $exercises[3]->reps ?></b> Reps</s></p>
                </div>
            </div>

            <!-- Active -->
            <div class="row p-0 m-0">
                <div class="montserrat p-0 bg-secondary text-white p-2 rounded-3 d-flex mb-3 col-11">
                    <p class="m-0 me-5">Set 1</p>
                    <p class="m-0"><b><?= $exercises[3]->reps ?></b> Reps</p>
                </div>
                <!-- Arrow -->
                <div class="col-1 d-flex justify-content-center pt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <g clip-path="url(#clip0_135_951)">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M23.999 12.0002C23.999 12.4169 23.841 12.8166 23.5597 13.1112C23.2785 13.4059 22.897 13.5714 22.4992 13.5714H5.12213L11.5624 20.3147C11.7019 20.4608 11.8125 20.6342 11.8879 20.8251C11.9634 21.016 12.0023 21.2205 12.0023 21.4271C12.0023 21.6337 11.9634 21.8383 11.8879 22.0291C11.8125 22.22 11.7019 22.3934 11.5624 22.5395C11.423 22.6856 11.2574 22.8014 11.0752 22.8805C10.893 22.9596 10.6977 23.0002 10.5005 23.0002C10.3033 23.0002 10.108 22.9596 9.92585 22.8805C9.74365 22.8014 9.5781 22.6856 9.43865 22.5395L0.439655 13.1126C0.299981 12.9667 0.189165 12.7933 0.113554 12.6024C0.0379429 12.4115 -0.000976563 12.2069 -0.000976562 12.0002C-0.000976563 11.7936 0.0379429 11.589 0.113554 11.3981C0.189165 11.2072 0.299981 11.0338 0.439655 10.8879L9.43865 1.461C9.72028 1.16598 10.1022 1.00024 10.5005 1.00024C10.8988 1.00024 11.2808 1.16598 11.5624 1.461C11.844 1.75602 12.0023 2.15615 12.0023 2.57337C12.0023 2.99059 11.844 3.39073 11.5624 3.68575L5.12213 10.4291H22.4992C22.897 10.4291 23.2785 10.5946 23.5597 10.8893C23.841 11.1839 23.999 11.5836 23.999 12.0002Z" fill="#525FDD"/>
                        </g>
                        <defs>
                            <clipPath id="clip0_135_951">
                                <rect width="24" height="24" fill="white"/>
                            </clipPath>
                        </defs>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Check -->
        <div class="col-1 flex-center bg-primary rounded-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M20.7805 5.46888C20.8503 5.53854 20.9057 5.62131 20.9436 5.71243C20.9814 5.80354 21.0008 5.90123 21.0008 5.99988C21.0008 6.09853 20.9814 6.19621 20.9436 6.28733C20.9057 6.37844 20.8503 6.46121 20.7805 6.53088L10.2805 17.0309C10.2108 17.1007 10.1281 17.1561 10.0369 17.1939C9.94582 17.2318 9.84813 17.2512 9.74948 17.2512C9.65083 17.2512 9.55315 17.2318 9.46203 17.1939C9.37091 17.1561 9.28815 17.1007 9.21848 17.0309L3.96848 11.7809C3.82765 11.64 3.74854 11.449 3.74854 11.2499C3.74854 11.0507 3.82765 10.8597 3.96848 10.7189C4.10931 10.578 4.30032 10.4989 4.49948 10.4989C4.69865 10.4989 4.88965 10.578 5.03048 10.7189L9.74948 15.4394L19.7185 5.46888C19.7882 5.39903 19.8709 5.34362 19.962 5.30581C20.0532 5.268 20.1508 5.24854 20.2495 5.24854C20.3481 5.24854 20.4458 5.268 20.5369 5.30581C20.628 5.34362 20.7108 5.39903 20.7805 5.46888Z"
                      fill="white"/>
            </svg>
        </div>

    </div>

</div>

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
        <div class="flex-column-center gap-1" >
            <a class="p-3 bg-primary rounded-circle" href="startRoutine.php?workout_id={$first->id}">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
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
