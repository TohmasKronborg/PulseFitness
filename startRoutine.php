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

$userId = (int)$_SESSION['userId'];

$workoutId = $_GET['workout_id'] ?? null;

if (!$workoutId) {
    exit("Missing workout_id");
}

// 1. GET WORKOUT

$workout = $db->sql("
    SELECT 
        w.id,
        w.workout_number,
        w.name,
        w.program_id
    FROM workouts w

    INNER JOIN training_programs tp
        ON tp.id = w.program_id

    WHERE w.id = :workout_id
      AND tp.member_id = :user_id

    LIMIT 1
", [
    ":workout_id" => $workoutId,
    ":user_id" => $userId
]);

if (!$workout) {
    exit("Workout not found");
}

$workout = $workout[0];

// 2. CREATE MISSING SET PROGRESS

$db->sql("
    INSERT IGNORE INTO user_workout_progress (
        user_id,
        workout_exercise_id,
        set_number,
        status
    )

    SELECT
        :user_id,
        we.id,
        seq.set_number,
        CASE
            WHEN seq.set_number = 1 THEN 'active'
            ELSE 'pending'
        END

    FROM workout_exercises we

    JOIN (
        SELECT 1 AS set_number
        UNION ALL SELECT 2
        UNION ALL SELECT 3
        UNION ALL SELECT 4
        UNION ALL SELECT 5
        UNION ALL SELECT 6
        UNION ALL SELECT 7
        UNION ALL SELECT 8
        UNION ALL SELECT 9
        UNION ALL SELECT 10
    ) seq
        ON seq.set_number <= we.sets

    WHERE we.workout_id = :workout_id
", [
    ":user_id" => $userId,
    ":workout_id" => $workoutId
]);

// 3. GET EXERCISES + SET STATES

$rows = $db->sql("
    SELECT
        we.id AS workout_exercise_id,

        e.id AS exercise_id,
        e.name,
        e.description,

        we.exercise_order,
        we.sets,
        we.reps,
        we.rest_seconds,

        seq.set_number,

        uwp.id AS progress_id,
        COALESCE(uwp.status, 'pending') AS status,
        uwp.completed_at,

        GROUP_CONCAT(DISTINCT mg.name SEPARATOR ', ') AS muscle_groups

    FROM workout_exercises we

    INNER JOIN exercises e
        ON e.id = we.exercise_id

    JOIN (
        SELECT 1 AS set_number
        UNION ALL SELECT 2
        UNION ALL SELECT 3
        UNION ALL SELECT 4
        UNION ALL SELECT 5
        UNION ALL SELECT 6
        UNION ALL SELECT 7
        UNION ALL SELECT 8
        UNION ALL SELECT 9
        UNION ALL SELECT 10
    ) seq
        ON seq.set_number <= we.sets

    LEFT JOIN user_workout_progress uwp
        ON uwp.workout_exercise_id = we.id
        AND uwp.user_id = :user_id
        AND uwp.set_number = seq.set_number

    LEFT JOIN exercise_muscle_groups emg
        ON emg.exercise_id = e.id

    LEFT JOIN muscle_groups mg
        ON mg.id = emg.muscle_group_id

    WHERE we.workout_id = :workout_id

    GROUP BY
        we.id,
        seq.set_number

    ORDER BY
        we.exercise_order ASC,
        seq.set_number ASC
", [
    ":user_id" => $userId,
    ":workout_id" => $workoutId
]);

// 4. GROUP EXERCISES

$exercises = [];

foreach ($rows as $row) {

    $exerciseId = $row->workout_exercise_id;

    if (!isset($exercises[$exerciseId])) {

        $exercises[$exerciseId] = [
            "workout_exercise_id" => $row->workout_exercise_id,
            "exercise_id" => $row->exercise_id,

            "name" => $row->name,
            "description" => $row->description,
            "muscle_groups" => $row->muscle_groups,

            "exercise_order" => $row->exercise_order,

            "sets" => $row->sets,
            "reps" => $row->reps,
            "rest_seconds" => $row->rest_seconds,

            "set_data" => []
        ];
    }

    $exercises[$exerciseId]["set_data"][] = [
        "progress_id" => $row->progress_id,
        "set_number" => $row->set_number,
        "status" => $row->status,
        "completed_at" => $row->completed_at
    ];
}

// 5. CALCULATE WORKOUT PROGRESS

$totalSets = 0;
$completedSets = 0;

foreach ($exercises as $exercise) {

    foreach ($exercise["set_data"] as $set) {

        $totalSets++;

        if ($set["status"] === "completed") {
            $completedSets++;
        }
    }
}
// 6. HELPER FUNCTIONS

$isWorkoutComplete = (
    $totalSets > 0 &&
    $completedSets === $totalSets
);

function formatTimeMMSS($seconds): string
{
    return sprintf(
        '%02d:%02d',
        floor($seconds / 60),
        $seconds % 60
    );
}
?>

<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">

    <title>Rutine Start</title>

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

<div class="mx-3 my-4">
    <h1 class="montserrat fw-bold">
        Dag <?= (int)$workout->workout_number ?> - <?= htmlspecialchars($workout->name, ENT_QUOTES, 'UTF-8') ?>
    </h1>
    <p class="text-gray m-0"><?= $completedSets ?> / <?= $totalSets ?> Sets Fuldført</p>
</div>

<!-- Tasks Container -->
<div class="container-fluid <?= $isWorkoutComplete ? "mb-0" : "mb-5" ?> ">

    <?php foreach ($exercises as $exercise): ?>

        <?php

        $hasActiveSet = false;

        foreach ($exercise["set_data"] as $set) {
            if ($set["status"] === "active") {
                $hasActiveSet = true;
                break;
            }
        }

        ?>

        <!-- Exercise Card Row -->
        <div class="row bg-white rounded-4 p-3 m-0 justify-content-between mb-4">

            <!-- Exercise Header -->
            <div class="col-12 p-0">
                <h2 class="fs-4 m-0"><b class="montserrat"><?= htmlspecialchars($exercise["name"]) ?></b> — <?= htmlspecialchars($exercise["muscle_groups"]) ?></h2>
                <p class="m-0 text-gray"><?= $exercise["description"] ?></p>
                <p class="m-0 text-gray"><?= $exercise["sets"] ?> set af <?= $exercise["reps"] ?> reps</p>
            </div>

            <hr class="my-2">

            <!-- Sets Indicators -->
            <div class="<?= !empty($hasActiveSet) ? "col-10" : "col-12" ?> p-0 pe-3">
                <?php foreach ($exercise["set_data"] as $set): ?>

                    <?php

                    $status = $set["status"];

                    $cardClass = "bg-light text-dark";

                    if ($status === "active") {
                        $cardClass = "bg-secondary text-white";
                    }

                    if ($status === "completed") {
                        $cardClass = "bg-light text-gray";
                    }

                    if ($status === "resting") {
                        $cardClass = "bg-secondary text-white";
                    }

                    ?>

                    <!-- Active -->
                    <div class="row p-0 m-0 mb-3 justify-content-between">
                        <div class="montserrat p-2 rounded-3 d-flex col-10 <?= $cardClass ?>">
                            <p class="m-0 me-5">
                                <?php if ($status === "completed"): ?>
                                    <s>Set <?= $set["set_number"] ?></s>
                                <?php else: ?>
                                    Set <?= $set["set_number"] ?>
                                <?php endif; ?>
                            </p>
                            <p class="m-0">
                                <?php if ($status === "resting"): ?>
                                    Hvil - <b class="rest-timer" data-rest="<?= $exercise["rest_seconds"] ?>"><?= formatTimeMMSS($exercise["rest_seconds"]) ?></b>
                                <?php else: ?>
                                    <?php if ($status === "completed"): ?>
                                        <s><b><?= $exercise["reps"] ?></b> Reps</s>
                                    <?php else: ?>
                                        <b><?= $exercise["reps"] ?></b> Reps
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Icon -->
                        <div class="col-1 col-sm-2 flex-center p-0">
                            <!-- Arrow -->
                            <?php if ($status === "active"): ?>
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

                            <?php elseif ($status === "resting"): ?>
                                <!-- Clock -->
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

                            <?php elseif ($status === "completed"): ?>

                                <!-- Undo -->
                                <button type="button" class="btn flex-center rounded-3 p-0 undo-set-btn" data-progress-id="<?= $set["progress_id"] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                                         fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                              d="M0.419604 0.419604C0.552277 0.286595 0.709888 0.181067 0.883407 0.109064C1.05693 0.0370616 1.24295 0 1.43081 0C1.61868 0 1.8047 0.0370616 1.97822 0.109064C2.15174 0.181067 2.30935 0.286595 2.44202 0.419604L10.0004 7.98082L17.5587 0.419604C17.6915 0.286811 17.8492 0.181473 18.0227 0.109605C18.1962 0.0377376 18.3821 0.00074779 18.5699 0.00074779C18.7577 0.00074779 18.9437 0.0377376 19.1172 0.109605C19.2907 0.181473 19.4484 0.286811 19.5811 0.419604C19.7139 0.552398 19.8193 0.710047 19.8911 0.88355C19.963 1.05705 20 1.24301 20 1.43081C20 1.61861 19.963 1.80457 19.8911 1.97808C19.8193 2.15158 19.7139 2.30923 19.5811 2.44202L12.0199 10.0004L19.5811 17.5587C19.7139 17.6915 19.8193 17.8492 19.8911 18.0227C19.963 18.1962 20 18.3821 20 18.5699C20 18.7577 19.963 18.9437 19.8911 19.1172C19.8193 19.2907 19.7139 19.4484 19.5811 19.5811C19.4484 19.7139 19.2907 19.8193 19.1172 19.8911C18.9437 19.963 18.7577 20 18.5699 20C18.3821 20 18.1962 19.963 18.0227 19.8911C17.8492 19.8193 17.6915 19.7139 17.5587 19.5811L10.0004 12.0199L2.44202 19.5811C2.30923 19.7139 2.15158 19.8193 1.97808 19.8911C1.80457 19.963 1.61861 20 1.43081 20C1.24301 20 1.05705 19.963 0.88355 19.8911C0.710047 19.8193 0.552398 19.7139 0.419604 19.5811C0.286811 19.4484 0.181473 19.2907 0.109605 19.1172C0.0377376 18.9437 0.00074779 18.7577 0.00074779 18.5699C0.00074779 18.3821 0.0377376 18.1962 0.109605 18.0227C0.181473 17.8492 0.286811 17.6915 0.419604 17.5587L7.98082 10.0004L0.419604 2.44202C0.286595 2.30935 0.181067 2.15174 0.109064 1.97822C0.0370616 1.8047 0 1.61868 0 1.43081C0 1.24295 0.0370616 1.05693 0.109064 0.883407C0.181067 0.709888 0.286595 0.552277 0.419604 0.419604Z"
                                              fill="#E93C79"/>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

            <!-- Complete Set Button -->
                <?php if ($hasActiveSet): ?>

                    <button type="button" class="btn btn-primary col-2 flex-center rounded-3 p-0 complete-set-btn" data-workout-exercise-id="<?= $exercise["workout_exercise_id"] ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 35 35" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                  d="M30.3051 7.97538C30.4069 8.07698 30.4877 8.19768 30.5429 8.33056C30.598 8.46344 30.6264 8.60589 30.6264 8.74976C30.6264 8.89362 30.598 9.03608 30.5429 9.16896C30.4877 9.30184 30.4069 9.42253 30.3051 9.52413L14.9926 24.8366C14.891 24.9385 14.7703 25.0193 14.6374 25.0744C14.5045 25.1296 14.362 25.158 14.2182 25.158C14.0743 25.158 13.9319 25.1296 13.799 25.0744C13.6661 25.0193 13.5454 24.9385 13.4438 24.8366L5.78755 17.1804C5.58218 16.975 5.4668 16.6965 5.4668 16.406C5.4668 16.1156 5.58218 15.837 5.78755 15.6316C5.99293 15.4263 6.27148 15.3109 6.56193 15.3109C6.85238 15.3109 7.13093 15.4263 7.3363 15.6316L14.2182 22.5157L28.7563 7.97538C28.8579 7.87353 28.9786 7.79271 29.1115 7.73757C29.2444 7.68244 29.3868 7.65405 29.5307 7.65405C29.6745 7.65405 29.817 7.68244 29.9499 7.73757C30.0828 7.79271 30.2035 7.87353 30.3051 7.97538V7.97538Z"
                                  fill="white"/>
                        </svg>
                    </button>

                <?php endif; ?>

        </div>

    <?php endforeach; ?>

</div>

<?php if ($isWorkoutComplete): ?>

    <form method="POST" action="routineDone.php" class="mx-2 mb-5">
        <input type="hidden" name="workout_id" value="<?= (int)$workoutId ?>">

        <button type="submit" class="fs-2 btn btn-success w-100 py-3 rounded-4 montserrat fw-bold">
            Afslut Træning
        </button>
    </form>

<?php endif; ?>

<!-- Bottom Nav -->
<footer class="mt-auto rounded-top-circle bg-white position-sticky bottom-0" style="min-height: 85px;">
    <div class="d-flex justify-content-around" style="margin-top: -25px; margin-bottom: 25px;">
        <!-- Home button -->
        <div class="flex-column-center gap-1">
            <a class="p-4 bg-info rounded-circle" href="index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                     fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M0.419604 0.419604C0.552277 0.286595 0.709888 0.181067 0.883407 0.109064C1.05693 0.0370616 1.24295 0 1.43081 0C1.61868 0 1.8047 0.0370616 1.97822 0.109064C2.15174 0.181067 2.30935 0.286595 2.44202 0.419604L10.0004 7.98082L17.5587 0.419604C17.6915 0.286811 17.8492 0.181473 18.0227 0.109605C18.1962 0.0377376 18.3821 0.00074779 18.5699 0.00074779C18.7577 0.00074779 18.9437 0.0377376 19.1172 0.109605C19.2907 0.181473 19.4484 0.286811 19.5811 0.419604C19.7139 0.552398 19.8193 0.710047 19.8911 0.88355C19.963 1.05705 20 1.24301 20 1.43081C20 1.61861 19.963 1.80457 19.8911 1.97808C19.8193 2.15158 19.7139 2.30923 19.5811 2.44202L12.0199 10.0004L19.5811 17.5587C19.7139 17.6915 19.8193 17.8492 19.8911 18.0227C19.963 18.1962 20 18.3821 20 18.5699C20 18.7577 19.963 18.9437 19.8911 19.1172C19.8193 19.2907 19.7139 19.4484 19.5811 19.5811C19.4484 19.7139 19.2907 19.8193 19.1172 19.8911C18.9437 19.963 18.7577 20 18.5699 20C18.3821 20 18.1962 19.963 18.0227 19.8911C17.8492 19.8193 17.6915 19.7139 17.5587 19.5811L10.0004 12.0199L2.44202 19.5811C2.30923 19.7139 2.15158 19.8193 1.97808 19.8911C1.80457 19.963 1.61861 20 1.43081 20C1.24301 20 1.05705 19.963 0.88355 19.8911C0.710047 19.8193 0.552398 19.7139 0.419604 19.5811C0.286811 19.4484 0.181473 19.2907 0.109605 19.1172C0.0377376 18.9437 0.00074779 18.7577 0.00074779 18.5699C0.00074779 18.3821 0.0377376 18.1962 0.109605 18.0227C0.181473 17.8492 0.286811 17.6915 0.419604 17.5587L7.98082 10.0004L0.419604 2.44202C0.286595 2.30935 0.181067 2.15174 0.109064 1.97822C0.0370616 1.8047 0 1.61868 0 1.43081C0 1.24295 0.0370616 1.05693 0.109064 0.883407C0.181067 0.709888 0.286595 0.552277 0.419604 0.419604Z"
                          fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0 text-nowrap">Hjem</p>
        </div>
    </div>
</footer>

<script>
    // Complete Sets
    document.querySelectorAll('.complete-set-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.workoutExerciseId;

            const res = await fetch('completeSet.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'workout_exercise_id=' + id
            });

            const data = await res.json();

            if (data.success) location.reload();
        });
    });

    // Undo Sets
    document.querySelectorAll('.undo-set-btn').forEach(btn => {
        btn.addEventListener('click', async () => {

            const id = btn.dataset.progressId;

            const res = await fetch('undoSet.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'progress_id=' + encodeURIComponent(id)
            });

            const data = await res.json();

            if (data.success) location.reload();
        });
    });

    // Timer (don't work)
    let timers = {};

    function startTimer(id, seconds) {
        if (timers[id]) clearInterval(timers[id]);

        let t = seconds;

        timers[id] = setInterval(() => {
            const el = document.querySelector('[data-timer="'+id+'"]').parentElement;

            if (t <= 0) {
                clearInterval(timers[id]);
                location.reload();
            }

            if (el) el.innerHTML = "Rest: " + t + "s";

            t--;
        }, 1000);
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
