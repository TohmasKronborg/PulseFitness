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

//1. GET WORKOUT

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

// 2. GET EXERCISES FOR THIS WORKOUT

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
        e.goal_id
    FROM workout_exercises we
    INNER JOIN exercises e ON e.id = we.exercise_id
    WHERE we.workout_id = :workout_id
    ORDER BY we.exercise_order ASC
", [
    ":workout_id" => $workoutId
]);

//3. NAVIGATION (PREV / NEXT / LOOP)

$nav = $db->sql("
    SELECT
        w.id,
        w.workout_number,

        prev.id AS prev_id,
        next.id AS next_id,

        bounds.first_id,
        bounds.last_id

    FROM workouts w
    INNER JOIN training_programs p ON p.id = w.program_id

    LEFT JOIN workouts prev
        ON prev.program_id = w.program_id
       AND prev.workout_number = (
            SELECT MAX(workout_number)
            FROM workouts
            WHERE program_id = w.program_id
              AND workout_number < w.workout_number
       )

    LEFT JOIN workouts next
        ON next.program_id = w.program_id
       AND next.workout_number = (
            SELECT MIN(workout_number)
            FROM workouts
            WHERE program_id = w.program_id
              AND workout_number > w.workout_number
       )

    JOIN (
        SELECT
            program_id,
            MIN(id) AS first_id,
            MAX(id) AS last_id
        FROM workouts
        GROUP BY program_id
    ) bounds
      ON bounds.program_id = w.program_id

    WHERE w.id = :id
      AND p.member_id = :user_id
    LIMIT 1
", [
    ":id" => $workoutId,
    ":user_id" => $userId
]);

if (!$nav) {
    exit("Navigation failed");
}

$nav = $nav[0];

$prevId = $nav->prev_id ?? $nav->last_id;
$nextId = $nav->next_id ?? $nav->first_id;

// 4. TOTAL WORKOUT COUNT (FOR THIS USER)

$total_workouts = $db->sql("
    SELECT COUNT(*) AS total_workouts
    FROM workouts w
    INNER JOIN training_programs p ON p.id = w.program_id
    WHERE p.member_id = :user_id
", [
    ":user_id" => $userId
]);

$total_workouts = $total_workouts[0]->total_workouts;
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

<h1 class="montserrat fw-bold my-5 mb-4 ms-3">
    Dag <?= (int)$workout->workout_number ?> - <?= htmlspecialchars($workout->name) ?>
</h1>

<!-- Indicators -->
<div class="container">
    <div class="row">
        <?php for ($i = 1; $i <= $total_workouts; $i++): ?>
            <div class="showRoutineDayIndicator rounded-4 col <?= ($i == $workout->workout_number) ? 'bg-primary' : 'bg-white' ?>"></div>
        <?php endfor; ?>
    </div>
    <p class="mt-2">Dag <?= (int)$workout->workout_number ?> ud af <?= $total_workouts ?></p>
</div>


<div class="container p-0">

    <div class="row bg-white rounded-4 p-3 mx-0 mb-5">
        <?php if (empty($exercises)): ?>
            <p>No exercises found.</p>
        <?php else: ?>

            <div class="table-responsive mt-3">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Øvelse & Beskrivelse</th>
                        <th class="p-0">Sets & Reps</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($exercises as $index => $ex): ?>
                        <tr>
                            <td class="flex-column">
                                <strong class="fs-5"><?= htmlspecialchars($ex->name) ?></strong>
                                <?= htmlspecialchars($ex->description) ?>
                            </td>
                            <td><?= (int)$ex->sets . " x " . (int)$ex->reps ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>


<!-- Bottom Nav -->
<footer class="mt-auto rounded-top-circle bg-white position-sticky bottom-0" style="min-height: 85px;">
    <div class="d-flex justify-content-around" style="margin-top: -25px; margin-bottom: 25px;">
        <!-- Button Back -->
        <div class="flex-column-center gap-1">
            <a class="p-3 bg-secondary rounded-circle" href="showRoutineDay.php?workout_id=<?= $prevId ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M42.1875 22.4997C42.1875 22.8727 42.0393 23.2303 41.7756 23.4941C41.5119 23.7578 41.1542 23.9059 40.7813 23.9059L7.61344 23.9059L16.4644 32.7541C16.7284 33.0181 16.8768 33.3763 16.8768 33.7497C16.8768 34.1231 16.7284 34.4813 16.4644 34.7453C16.2003 35.0094 15.8422 35.1577 15.4688 35.1577C15.0953 35.1577 14.7372 35.0094 14.4731 34.7453L3.22313 23.4953C3.09217 23.3647 2.98827 23.2095 2.91737 23.0387C2.84648 22.8678 2.80999 22.6847 2.80999 22.4997C2.80999 22.3147 2.84648 22.1316 2.91737 21.9607C2.98827 21.7899 3.09217 21.6347 3.22313 21.5041L14.4731 10.2541C14.7372 9.99001 15.0953 9.84166 15.4688 9.84166C15.8422 9.84166 16.2003 9.99001 16.4644 10.2541C16.7284 10.5181 16.8768 10.8763 16.8768 11.2497C16.8768 11.6231 16.7284 11.9813 16.4644 12.2453L7.61344 21.0934L40.7813 21.0934C41.1542 21.0934 41.5119 21.2416 41.7756 21.5053C42.0393 21.769 42.1875 22.1267 42.1875 22.4997V22.4997Z"
                          fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0">Forrige</p>
        </div>

        <!-- Button home -->
        <div class="flex-column-center gap-1 align-self-end">
            <a class="p-2 bg-info rounded-circle" href="index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.38948 6.38826C6.45334 6.32423 6.52921 6.27344 6.61273 6.23878C6.69626 6.20412 6.7858 6.18628 6.87623 6.18628C6.96666 6.18628 7.0562 6.20412 7.13972 6.23878C7.22325 6.27344 7.29912 6.32423 7.36298 6.38826L11.0012 10.0279L14.6395 6.38826C14.7034 6.32434 14.7793 6.27363 14.8628 6.23904C14.9463 6.20444 15.0358 6.18664 15.1262 6.18664C15.2166 6.18664 15.3061 6.20444 15.3897 6.23904C15.4732 6.27363 15.5491 6.32434 15.613 6.38826C15.6769 6.45218 15.7276 6.52806 15.7622 6.61158C15.7968 6.6951 15.8146 6.78461 15.8146 6.87501C15.8146 6.96541 15.7968 7.05492 15.7622 7.13844C15.7276 7.22195 15.6769 7.29784 15.613 7.36176L11.9734 11L15.613 14.6383C15.6769 14.7022 15.7276 14.7781 15.7622 14.8616C15.7968 14.9451 15.8146 15.0346 15.8146 15.125C15.8146 15.2154 15.7968 15.3049 15.7622 15.3884C15.7276 15.472 15.6769 15.5478 15.613 15.6118C15.5491 15.6757 15.4732 15.7264 15.3897 15.761C15.3061 15.7956 15.2166 15.8134 15.1262 15.8134C15.0358 15.8134 14.9463 15.7956 14.8628 15.761C14.7793 15.7264 14.7034 15.6757 14.6395 15.6118L11.0012 11.9721L7.36298 15.6118C7.29906 15.6757 7.22317 15.7264 7.13966 15.761C7.05614 15.7956 6.96663 15.8134 6.87623 15.8134C6.78583 15.8134 6.69632 15.7956 6.6128 15.761C6.52928 15.7264 6.4534 15.6757 6.38948 15.6118C6.32556 15.5478 6.27485 15.472 6.24026 15.3884C6.20567 15.3049 6.18786 15.2154 6.18786 15.125C6.18786 15.0346 6.20567 14.9451 6.24026 14.8616C6.27485 14.7781 6.32556 14.7022 6.38948 14.6383L10.0291 11L6.38948 7.36176C6.32545 7.2979 6.27466 7.22203 6.24 7.1385C6.20534 7.05498 6.1875 6.96544 6.1875 6.87501C6.1875 6.78458 6.20534 6.69504 6.24 6.61151C6.27466 6.52799 6.32545 6.45212 6.38948 6.38826Z" fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0">Tilbage</p>
        </div>

        <!-- Button Next -->
        <div class="flex-column-center gap-1">
            <a class="p-3 bg-primary rounded-circle border-0" href="showRoutineDay.php?workout_id=<?= $nextId ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M2.8125 22.5003C2.8125 22.1273 2.96066 21.7697 3.22438 21.5059C3.4881 21.2422 3.84579 21.0941 4.21875 21.0941L37.3866 21.0941L28.5356 12.2459C28.2716 11.9819 28.1232 11.6237 28.1232 11.2503C28.1232 10.8769 28.2716 10.5187 28.5356 10.2547C28.7997 9.99063 29.1578 9.84229 29.5313 9.84229C29.9047 9.84229 30.2628 9.99063 30.5269 10.2547L41.7769 21.5047C41.9078 21.6353 42.0117 21.7905 42.0826 21.9613C42.1535 22.1322 42.19 22.3153 42.19 22.5003C42.19 22.6853 42.1535 22.8684 42.0826 23.0393C42.0117 23.2101 41.9078 23.3653 41.7769 23.4959L30.5269 34.7459C30.2628 35.01 29.9047 35.1583 29.5313 35.1583C29.1578 35.1583 28.7997 35.01 28.5356 34.7459C28.2716 34.4819 28.1232 34.1237 28.1232 33.7503C28.1232 33.3769 28.2716 33.0187 28.5356 32.7547L37.3866 23.9066L4.21875 23.9066C3.84579 23.9066 3.4881 23.7584 3.22438 23.4947C2.96066 23.231 2.8125 22.8733 2.8125 22.5003V22.5003Z"
                          fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0">Næste</p>
        </div>
    </div>
</footer>

<script src="scripts/canvas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
