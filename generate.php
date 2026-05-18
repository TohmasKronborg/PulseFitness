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

if ($_POST) {

    $difficultyId = (int)($_POST['difficulty'] ?? 0);
    $goalId = (int)($_POST['goal'] ?? 0);
    $equipmentId = (int)($_POST['equipment'] ?? 0);
    $muscleGroupId = (int)($_POST['muscleGroup'] ?? 0);
    $days = (int)($_POST['days'] ?? 0);

    if ($days <= 0 || $difficultyId <= 0 || $goalId <= 0 || $equipmentId <= 0 || $muscleGroupId <= 0) {
        exit("Invalid input");
    }

    $programName = "Genereret Program";

    // DELETE OLD DATA

    $db->sql("
        DELETE we
        FROM workout_exercises we
        INNER JOIN workouts w ON we.workout_id = w.id
        INNER JOIN training_programs tp ON w.program_id = tp.id
        WHERE tp.member_id = :member_id
    ", [":member_id" => $userId]);

    $db->sql("
        DELETE w
        FROM workouts w
        INNER JOIN training_programs tp ON w.program_id = tp.id
        WHERE tp.member_id = :member_id
    ", [":member_id" => $userId]);

    $db->sql("
        DELETE FROM training_programs
        WHERE member_id = :member_id
    ", [":member_id" => $userId]);

    // CREATE PROGRAM

    $db->sql("
        INSERT INTO training_programs
        (member_id, name, workouts_per_week)
        VALUES
        (:member_id, :name, :days)
    ", [
        ":member_id" => $userId,
        ":name" => $programName,
        ":days" => $days
    ]);

    $programRow = $db->sql("
        SELECT id
        FROM training_programs
        WHERE member_id = :member_id
        ORDER BY id DESC
        LIMIT 1
    ", [":member_id" => $userId]);

    $programId = $programRow[0]->id;

    $usedExercises = [];

    // WORKOUT LOOP

    for ($i = 1; $i <= $days; $i++) {

        $db->sql("
            INSERT INTO workouts
            (program_id, workout_number, name)
            VALUES
            (:program_id, :number, :name)
        ", [
            ":program_id" => $programId,
            ":number" => $i,
            ":name" => "Midlertidig"
        ]);

        $workoutRow = $db->sql("
            SELECT id
            FROM workouts
            WHERE program_id = :program_id
            ORDER BY id DESC
            LIMIT 1
        ", [
            ":program_id" => $programId
        ]);

        $workoutId = $workoutRow[0]->id;

        // EQUIPMENT FILTER

        if ($equipmentId == 1) {
            $equipmentSql = "";
            $params = [];
        } else {
            $equipmentSql = "AND ee.equipment_id = :equipment_id";
            $params = [":equipment_id" => $equipmentId];
        }

        // EXCLUDE USED EXERCISES

        $excludeSql = "";
        if (!empty($usedExercises)) {
            $excludeIds = implode(",", array_map("intval", $usedExercises));
            $excludeSql = "AND e.id NOT IN ($excludeIds)";
        }

        // GET CANDIDATES

        $query = "
            SELECT DISTINCT
                e.id,
                e.name,
                e.difficulty_id,
                e.goal_id
            FROM exercises e
            INNER JOIN exercise_equipment ee ON e.id = ee.exercise_id
            INNER JOIN exercise_muscle_groups emg ON e.id = emg.exercise_id
            WHERE 1=1
            $equipmentSql
            $excludeSql
            ORDER BY RAND()
            LIMIT 200
        ";

        $candidates = $db->sql($query, $params);

        if (empty($candidates)) {

            $query = "
                SELECT DISTINCT
                    e.id,
                    e.name,
                    e.difficulty_id,
                    e.goal_id
                FROM exercises e
                INNER JOIN exercise_equipment ee ON e.id = ee.exercise_id
                WHERE 1=1
                $equipmentSql
                ORDER BY RAND()
                LIMIT 200
            ";

            $candidates = $db->sql($query, $params);
        }

        // SCORING

        $scored = [];

        foreach ($candidates as $ex) {

            $score = 0;

            if ($ex->goal_id == $goalId) {
                $score += 5;
            }

            if ($ex->difficulty_id == $difficultyId) {
                $score += 2;
            }

            $muscleMatch = $db->sql("
                SELECT 1
                FROM exercise_muscle_groups
                WHERE exercise_id = :eid
                  AND muscle_group_id = :mg
                LIMIT 1
            ", [
                ":eid" => $ex->id,
                ":mg" => $muscleGroupId
            ]);

            if ($muscleMatch) {
                $score += 4;
            }

            $score += rand(0, 3);

            $scored[] = [
                "id" => $ex->id,
                "name" => $ex->name,
                "score" => $score
            ];
        }

        shuffle($scored);
        usort($scored, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $exerciseCount = min(count($scored), rand(5, 7));
        $selectedExercises = array_slice($scored, 0, $exerciseCount);

        // INSERT EXERCISES

        $exerciseIds = [];
        $order = 1;

        foreach ($selectedExercises as $ex) {

            $exerciseIds[] = (int)$ex['id'];
            $usedExercises[] = (int)$ex['id'];

            // BASE VALUES (static baseline)
            $sets = 3;
            $reps = 10;
            $rest = 90; // NEVER modified

            // GOAL ADJUSTMENTS (NOW WITH -1 / -2 INCLUDED)

            switch ($goalId) {

                case 1: // Styrketræning (heavier load)
                    $sets += 2;
                    $reps -= 2;
                    break;

                case 2: // Muskelopbygning (slight increase volume)
                    $sets += 1;
                    break;

                case 3: // Vægttab (higher reps, slightly less sets)
                    $sets -= 1;
                    $reps += 2;
                    break;

                case 4: // Vedligeholdelse
                    // no change
                    break;

                case 5: // Genoptræning (reduced load)
                    $sets -= 2;
                    $reps -= 2;
                    break;
            }

            // SAFETY LIMITS
            $sets = max(1, min(6, $sets));
            $reps = max(1, min(30, $reps));

            $db->sql("
                INSERT INTO workout_exercises
                (workout_id, exercise_id, exercise_order, sets, reps, rest_seconds)
                VALUES
                (:workout_id, :exercise_id, :exercise_order, :sets, :reps, :rest)
            ", [
                ":workout_id" => $workoutId,
                ":exercise_id" => $ex['id'],
                ":exercise_order" => $order,
                ":sets" => $sets,
                ":reps" => $reps,
                ":rest" => $rest
            ]);

            $order++;
        }

        // WORKOUT NAMING

        $muscleData = [];

        if (!empty($exerciseIds)) {

            $in = implode(',', array_map('intval', $exerciseIds));

            $muscleData = $db->sql("
                SELECT DISTINCT mg.name
                FROM muscle_groups mg
                INNER JOIN exercise_muscle_groups emg
                    ON mg.id = emg.muscle_group_id
                WHERE emg.exercise_id IN ($in)
            ");
        }

        $muscles = [];

        foreach ($muscleData as $m) {
            $muscles[] = $m->name;
        }

        $muscles = array_unique($muscles);

        if (in_array("Bryst", $muscles) && in_array("Arme", $muscles)) {
            $workoutName = "Push Rutine";
        } elseif (in_array("Ryg", $muscles) && in_array("Arme", $muscles)) {
            $workoutName = "Pull Rutine";
        } elseif (in_array("Ben", $muscles)) {
            $workoutName = "Ben dag";
        } elseif (count($muscles) >= 4) {
            $workoutName = "Full Body Rutine";
        } else {
            $workoutName = implode(", ", array_slice($muscles, 0, 2)) . " Rutine";
        }

        $db->sql("
            UPDATE workouts
            SET name = :name
            WHERE id = :id
        ", [
            ":name" => $workoutName,
            ":id" => $workoutId
        ]);
    }

    header("Location: showRoutine.php?program_id=" . $programId);
    exit();
}
?>

<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">

    <title>Generer rutine</title>

    <meta name="robots" content="All">
    <meta name="author" content="Udgiver">
    <meta name="copyright" content="Information om copyright">

    <link rel="stylesheet" href="css/styles.css" type="text/css">
    <link rel="icon" href="images/LogoBlack.png">

    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<style>
    .indicatorCircle{
        height: 60px;
        width: 60px;
    }

    .indicatorCircle-active {
        height: 54px;
        width: 54px;
    }

    .step {
        display: none;
    }

    .step.active {
        display: block;
    }
</style>
<body class="mx-auto overflow-x-hidden flex-column position-relative" style="max-width: 768px; height: 100vh">

<!-- Dims Nav -->
<nav class="flex-column">
    <div class="bg-primary flex-center h-auto">
        <a class="mx-auto my-3" href="index.php"><img src="images/LogoWhite.png" alt="WhiteLogo" class="img-fluid" style="max-width: 100px;"></a>
        <?php include "include/navButtons.php" ?>
    </div>
    <img src="images/VectorPrimary.svg" alt="dims">
</nav>

<!-- Stage indicators -->
<div class="montserrat fw-bold d-flex justify-content-around mt-3">
    <div class="indicatorCircle bg-primary text-light flex-center rounded-circle step-indicator">
        <span>1</span>
    </div>
    <div class="indicatorCircle bg-white text-dark flex-center rounded-circle step-indicator">
        <span>2</span>
    </div>
    <div class="indicatorCircle bg-white text-dark flex-center rounded-circle step-indicator">
        <span>3</span>
    </div>
    <div class="indicatorCircle bg-white text-dark flex-center rounded-circle step-indicator">
        <span>4</span>
    </div>
    <div class="indicatorCircle bg-white text-dark flex-center rounded-circle step-indicator">
        <span>5</span>
    </div>
</div>

<!-- THE form -->
<form method="POST" class="container-fluid mt-4 mb-5" id="workoutForm">

    <!-- Difficulty Radios -->
    <div id="difficulty" class="step">
        <h2 class="montserrat fw-bold">Trænings Niveau</h2>
        <p class="text-gray">Hvad er dit trænings niveau?</p>
        <!-- Beginner -->
        <input type="radio" class="btn-check" name="difficulty" id="beginner" autocomplete="off" value="1">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100" for="beginner">
            <img src="images/icons/Beginner.svg" alt="BegynderIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Begynder</span>
        </label>

        <!-- Øvet -->
        <input type="radio" class="btn-check" name="difficulty" id="advanced" autocomplete="off" value="2">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="advanced">
            <img src="images/icons/Dumbbell.svg" alt="ØvetIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Øvet</span>
        </label>

        <!-- Avanceret -->
        <input type="radio" class="btn-check" name="difficulty" id="avanceret" autocomplete="off" value="3">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="avanceret">
            <img src="images/icons/Group.svg" alt="IntermediatIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Avanceret</span>
        </label>
    </div>

    <!-- Goal Radios -->
    <div id="goal" class="step">
        <h2 class="montserrat fw-bold">Træningsmål</h2>
        <p class="text-gray">Hvad er dit træningsmål?</p>

        <!-- Styrketræning -->
        <input type="radio" class="btn-check" name="goal" id="styrketræning" autocomplete="off" value="1">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100" for="styrketræning">
            <img src="images/icons/anvil.svg" alt="BegynderIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Styrketræning</span>
        </label>

        <!-- Muskelopbygning -->
        <input type="radio" class="btn-check" name="goal" id="muskelopbygning" autocomplete="off" value="2">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="muskelopbygning">
            <img src="images/icons/muscle.svg" alt="ArmIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Muskelopbygning</span>
        </label>

        <!-- Vægttab -->
        <input type="radio" class="btn-check" name="goal" id="vægttab" autocomplete="off" value="3">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="vægttab">
            <img src="images/icons/endocrine.svg" alt="flammeIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Vægttab</span>
        </label>

        <!-- Fysisk vedligeholdelse -->
        <input type="radio" class="btn-check" name="goal" id="fysiskVedligeholdelse" autocomplete="off" value="4">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="fysiskVedligeholdelse">
            <img src="images/icons/treadmil.svg" alt="løbeIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Fysisk Vedligeholdelse</span>
        </label>

        <!-- Genoptræning -->
        <input type="radio" class="btn-check" name="goal" id="genoptræning" autocomplete="off" value="5">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="genoptræning">
            <img src="images/icons/bullseye.svg" alt="skydeIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Genoptræning</span>
        </label>
    </div>

    <!-- Equipment Radios -->
    <div id="equipment" class="step">
        <h2 class="montserrat fw-bold">Udstyr</h2>
        <p class="text-gray">Hvad for noget udstyr vil du bruge?</p>
        <!-- Hele Centret -->
        <input type="radio" class="btn-check" name="equipment" id="heleCentret" autocomplete="off" value="1">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100" for="heleCentret">
            <img src="images/icons/shop.svg" alt="centerIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Hele Centret</span>
        </label>

        <!-- Dumbbells & Barbells -->
        <input type="radio" class="btn-check" name="equipment" id="dumbbells&Barbells" autocomplete="off" value="2">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="dumbbells&Barbells">
            <img src="images/icons/Dumbbell.svg" alt="dumbbellIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Dumbbells & Barbells</span>
        </label>

        <!-- Maskiner -->
        <input type="radio" class="btn-check" name="equipment" id="maskiner" autocomplete="off" value="3">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="maskiner">
            <img src="images/icons/gear.svg" alt="tandhjulIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Maskiner</span>
        </label>

        <!-- Træningstilbehør -->
        <input type="radio" class="btn-check" name="equipment" id="træningstilbehør" autocomplete="off" value="4">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="træningstilbehør">
            <img src="images/icons/bullseye.svg" alt="skydeIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Træningstilbehør</span>
        </label>
    </div>


    <!-- Muscle Group Radios -->
    <div id="muscleGroup" class="step">
        <h2 class="montserrat fw-bold">Muskelgrupper</h2>
        <p class="text-gray">Hvilke muskelgrupper vil du have fokus på?</p>
        <!-- Full Body -->
        <input type="radio" class="btn-check" name="muscleGroup" id="fullBody" autocomplete="off" value="1">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100" for="fullBody">
            <img src="images/icons/bullseye.svg" alt="skydeIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Full Body</span>
        </label>

        <!-- Core -->
        <input type="radio" class="btn-check" name="muscleGroup" id="core" autocomplete="off" value="2">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="core">
            <img src="images/icons/bullseye.svg" alt="skydeIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Core</span>
        </label>

        <!-- Arme -->
        <input type="radio" class="btn-check" name="muscleGroup" id="arme" autocomplete="off" value="3">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="arme">
            <img src="images/icons/muscle.svg" alt="armIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Arme</span>
        </label>

        <!-- ben -->
        <input type="radio" class="btn-check" name="muscleGroup" id="ben" autocomplete="off" value="4">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="ben">
            <img src="images/icons/Leg.svg" alt="benIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Ben</span>
        </label>

        <!-- Bryst -->
        <input type="radio" class="btn-check" name="muscleGroup" id="bryst" autocomplete="off" value="5">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="bryst">
            <img src="images/icons/Chest.svg" alt="brystIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Bryst</span>
        </label>

        <!-- Skulder -->
        <input type="radio" class="btn-check" name="muscleGroup" id="skulder" autocomplete="off" value="6">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="skulder">
            <img src="images/icons/bullseye.svg" alt="skydeIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Skulder</span>
        </label>

        <!-- Ryg -->
        <input type="radio" class="btn-check" name="muscleGroup" id="ryg" autocomplete="off" value="7">

        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="ryg">
            <img src="images/icons/Back.svg" alt="backIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Ryg</span>
        </label>
    </div>

    <!-- Calendar Radios -->
    <div id="days" class="step">
        <h2 class="montserrat fw-bold fs-1 mb-4">Hvor mange dage om ugen vil du træne?</h2>
        <!-- 1 day -->
        <input type="radio" class="btn-check" name="days" id="1day" autocomplete="off" value="1">
        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100" for="1day">
            <img src="images/icons/calendar/calendar-1.svg" alt="calendarIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">Enkel Træning</span>
        </label>

        <!-- 2 days -->
        <input type="radio" class="btn-check" name="days" id="2day" autocomplete="off" value="2">
        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="2day">
            <img src="images/icons/calendar/calendar-2.svg" alt="calendarIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">2 dage om ugen</span>
        </label>

        <!-- 3 days -->
        <input type="radio" class="btn-check" name="days" id="3day" autocomplete="off" value="3">
        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="3day">
            <img src="images/icons/calendar/calendar-3.svg" alt="calendarIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">3 dage om ugen</span>
        </label>

        <!-- 4 days -->
        <input type="radio" class="btn-check" name="days" id="4day" autocomplete="off" value="4">
        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="4day">
            <img src="images/icons/calendar/calendar-4.svg" alt="calendarIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">4 dage om ugen</span>
        </label>

        <!-- 5 days -->
        <input type="radio" class="btn-check" name="days" id="5day" autocomplete="off" value="5">
        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="5day">
            <img src="images/icons/calendar/calendar-5.svg" alt="calendarIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">5 dage om ugen</span>
        </label>

        <!-- 6 days -->
        <input type="radio" class="btn-check" name="days" id="6day" autocomplete="off" value="6">
        <label class="btn p-4 d-flex bg-white rounded-4 align-items-center gap-4 w-100 mt-3" for="6day">
            <img src="images/icons/calendar/calendar-6.svg" alt="calendarIkon" class="img-fluid">
            <span class="fs-4 montserrat fw-bold text-start">6 dage om ugen</span>
        </label>
    </div>
</form>


<!-- Bottom Nav -->
<footer class="mt-auto rounded-top-circle bg-white position-sticky bottom-0" style="min-height: 85px;">
    <div class="d-flex justify-content-around" style="margin-top: -25px; margin-bottom: 25px;">
        <!-- Button Back -->
        <div class="flex-column-center gap-1" id="btnFirstHome">
            <a class="p-3 bg-info rounded-circle" href="index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M42.1875 22.4997C42.1875 22.8727 42.0393 23.2303 41.7756 23.4941C41.5119 23.7578 41.1542 23.9059 40.7813 23.9059L7.61344 23.9059L16.4644 32.7541C16.7284 33.0181 16.8768 33.3763 16.8768 33.7497C16.8768 34.1231 16.7284 34.4813 16.4644 34.7453C16.2003 35.0094 15.8422 35.1577 15.4688 35.1577C15.0953 35.1577 14.7372 35.0094 14.4731 34.7453L3.22313 23.4953C3.09217 23.3647 2.98827 23.2095 2.91737 23.0387C2.84648 22.8678 2.80999 22.6847 2.80999 22.4997C2.80999 22.3147 2.84648 22.1316 2.91737 21.9607C2.98827 21.7899 3.09217 21.6347 3.22313 21.5041L14.4731 10.2541C14.7372 9.99001 15.0953 9.84166 15.4688 9.84166C15.8422 9.84166 16.2003 9.99001 16.4644 10.2541C16.7284 10.5181 16.8768 10.8763 16.8768 11.2497C16.8768 11.6231 16.7284 11.9813 16.4644 12.2453L7.61344 21.0934L40.7813 21.0934C41.1542 21.0934 41.5119 21.2416 41.7756 21.5053C42.0393 21.769 42.1875 22.1267 42.1875 22.4997V22.4997Z"
                          fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0">Annuller</p>
        </div>

        <!-- Button Back 2 -->
        <div class="flex-column-center gap-1 d-none" id="btnBackDiv">
            <button type="button" class="p-3 bg-secondary rounded-circle border-0" href="index.php" id="btnBack">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M42.1875 22.4997C42.1875 22.8727 42.0393 23.2303 41.7756 23.4941C41.5119 23.7578 41.1542 23.9059 40.7813 23.9059L7.61344 23.9059L16.4644 32.7541C16.7284 33.0181 16.8768 33.3763 16.8768 33.7497C16.8768 34.1231 16.7284 34.4813 16.4644 34.7453C16.2003 35.0094 15.8422 35.1577 15.4688 35.1577C15.0953 35.1577 14.7372 35.0094 14.4731 34.7453L3.22313 23.4953C3.09217 23.3647 2.98827 23.2095 2.91737 23.0387C2.84648 22.8678 2.80999 22.6847 2.80999 22.4997C2.80999 22.3147 2.84648 22.1316 2.91737 21.9607C2.98827 21.7899 3.09217 21.6347 3.22313 21.5041L14.4731 10.2541C14.7372 9.99001 15.0953 9.84166 15.4688 9.84166C15.8422 9.84166 16.2003 9.99001 16.4644 10.2541C16.7284 10.5181 16.8768 10.8763 16.8768 11.2497C16.8768 11.6231 16.7284 11.9813 16.4644 12.2453L7.61344 21.0934L40.7813 21.0934C41.1542 21.0934 41.5119 21.2416 41.7756 21.5053C42.0393 21.769 42.1875 22.1267 42.1875 22.4997V22.4997Z"
                          fill="white"/>
                </svg>
            </button>
            <p class="text-center montserrat fw-bold m-0">Tilbage</p>
        </div>

        <!-- Button home -->
        <div class="flex-column-center gap-1 align-self-end d-none" id="btnHome">
            <a class="p-2 bg-info rounded-circle" href="index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.38948 6.38826C6.45334 6.32423 6.52921 6.27344 6.61273 6.23878C6.69626 6.20412 6.7858 6.18628 6.87623 6.18628C6.96666 6.18628 7.0562 6.20412 7.13972 6.23878C7.22325 6.27344 7.29912 6.32423 7.36298 6.38826L11.0012 10.0279L14.6395 6.38826C14.7034 6.32434 14.7793 6.27363 14.8628 6.23904C14.9463 6.20444 15.0358 6.18664 15.1262 6.18664C15.2166 6.18664 15.3061 6.20444 15.3897 6.23904C15.4732 6.27363 15.5491 6.32434 15.613 6.38826C15.6769 6.45218 15.7276 6.52806 15.7622 6.61158C15.7968 6.6951 15.8146 6.78461 15.8146 6.87501C15.8146 6.96541 15.7968 7.05492 15.7622 7.13844C15.7276 7.22195 15.6769 7.29784 15.613 7.36176L11.9734 11L15.613 14.6383C15.6769 14.7022 15.7276 14.7781 15.7622 14.8616C15.7968 14.9451 15.8146 15.0346 15.8146 15.125C15.8146 15.2154 15.7968 15.3049 15.7622 15.3884C15.7276 15.472 15.6769 15.5478 15.613 15.6118C15.5491 15.6757 15.4732 15.7264 15.3897 15.761C15.3061 15.7956 15.2166 15.8134 15.1262 15.8134C15.0358 15.8134 14.9463 15.7956 14.8628 15.761C14.7793 15.7264 14.7034 15.6757 14.6395 15.6118L11.0012 11.9721L7.36298 15.6118C7.29906 15.6757 7.22317 15.7264 7.13966 15.761C7.05614 15.7956 6.96663 15.8134 6.87623 15.8134C6.78583 15.8134 6.69632 15.7956 6.6128 15.761C6.52928 15.7264 6.4534 15.6757 6.38948 15.6118C6.32556 15.5478 6.27485 15.472 6.24026 15.3884C6.20567 15.3049 6.18786 15.2154 6.18786 15.125C6.18786 15.0346 6.20567 14.9451 6.24026 14.8616C6.27485 14.7781 6.32556 14.7022 6.38948 14.6383L10.0291 11L6.38948 7.36176C6.32545 7.2979 6.27466 7.22203 6.24 7.1385C6.20534 7.05498 6.1875 6.96544 6.1875 6.87501C6.1875 6.78458 6.20534 6.69504 6.24 6.61151C6.27466 6.52799 6.32545 6.45212 6.38948 6.38826Z" fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0">Annuller</p>
        </div>

        <!-- Button Next -->
        <div class="flex-column-center gap-1" id="btnNextDiv">
            <button type="button" class="p-3 bg-primary rounded-circle border-0" id="btnNext">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M2.8125 22.5003C2.8125 22.1273 2.96066 21.7697 3.22438 21.5059C3.4881 21.2422 3.84579 21.0941 4.21875 21.0941L37.3866 21.0941L28.5356 12.2459C28.2716 11.9819 28.1232 11.6237 28.1232 11.2503C28.1232 10.8769 28.2716 10.5187 28.5356 10.2547C28.7997 9.99063 29.1578 9.84229 29.5313 9.84229C29.9047 9.84229 30.2628 9.99063 30.5269 10.2547L41.7769 21.5047C41.9078 21.6353 42.0117 21.7905 42.0826 21.9613C42.1535 22.1322 42.19 22.3153 42.19 22.5003C42.19 22.6853 42.1535 22.8684 42.0826 23.0393C42.0117 23.2101 41.9078 23.3653 41.7769 23.4959L30.5269 34.7459C30.2628 35.01 29.9047 35.1583 29.5313 35.1583C29.1578 35.1583 28.7997 35.01 28.5356 34.7459C28.2716 34.4819 28.1232 34.1237 28.1232 33.7503C28.1232 33.3769 28.2716 33.0187 28.5356 32.7547L37.3866 23.9066L4.21875 23.9066C3.84579 23.9066 3.4881 23.7584 3.22438 23.4947C2.96066 23.231 2.8125 22.8733 2.8125 22.5003V22.5003Z"
                          fill="white"/>
                </svg>
            </button>
            <p class="text-center montserrat fw-bold m-0">Næste</p>
        </div>

        <!-- Button Next 2 -->
        <div class="flex-column-center gap-1 d-none" id="btnNext2">
            <button type="submit" form="workoutForm" class="p-3 bg-info rounded-circle border-0" id="btnNext2">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M38.9635 10.2542C39.0944 10.3848 39.1983 10.54 39.2692 10.7109C39.3401 10.8817 39.3766 11.0649 39.3766 11.2498C39.3766 11.4348 39.3401 11.618 39.2692 11.7888C39.1983 11.9596 39.0944 12.1148 38.9635 12.2455L19.276 31.933C19.1453 32.0639 18.9902 32.1678 18.8193 32.2387C18.6485 32.3096 18.4653 32.3461 18.2803 32.3461C18.0954 32.3461 17.9122 32.3096 17.7414 32.2387C17.5705 32.1678 17.4153 32.0639 17.2847 31.933L7.44097 22.0892C7.17691 21.8251 7.02856 21.467 7.02856 21.0936C7.02856 20.7201 7.17691 20.362 7.44097 20.098C7.70502 19.8339 8.06316 19.6856 8.43659 19.6856C8.81002 19.6856 9.16816 19.8339 9.43222 20.098L18.2803 28.9489L36.9722 10.2542C37.1028 10.1232 37.258 10.0193 37.4289 9.94845C37.5997 9.87756 37.7829 9.84106 37.9678 9.84106C38.1528 9.84106 38.336 9.87756 38.5068 9.94845C38.6777 10.0193 38.8328 10.1232 38.9635 10.2542V10.2542Z" fill="white"/>
                </svg>
            </button>
            <p class="text-center montserrat fw-bold m-0">Gennemfør</p>
        </div>
    </div>
</footer>

<script src="scripts/generate.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>