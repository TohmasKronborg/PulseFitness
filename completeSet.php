<?php
/**
 * @var db $db
 */
require "settings/init.php";
session_start();

$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
    exit("Not logged in");
}

$workoutExerciseId = $_POST['workout_exercise_id'] ?? null;

if (!$workoutExerciseId) {
    exit("Missing data");
}

/*
|--------------------------------------------------------------------------
| 1. FIND CURRENT ACTIVE SET
|--------------------------------------------------------------------------
*/

$current = $db->sql("
    SELECT *
    FROM user_workout_progress
    WHERE user_id = :user_id
      AND workout_exercise_id = :we_id
      AND status = 'active'
    ORDER BY set_number ASC
    LIMIT 1
", [
    ":user_id" => $userId,
    ":we_id" => $workoutExerciseId
]);

if (!$current) {
    exit("No active set found");
}

$current = $current[0];

/*
|--------------------------------------------------------------------------
| 2. COMPLETE CURRENT SET
|--------------------------------------------------------------------------
*/

$db->sql("
    UPDATE user_workout_progress
    SET status = 'completed',
        completed_at = NOW()
    WHERE id = :id
", [
    ":id" => $current->id
]);

/*
|--------------------------------------------------------------------------
| 3. FIND NEXT SET
|--------------------------------------------------------------------------
*/

$next = $db->sql("
    SELECT *
    FROM user_workout_progress
    WHERE user_id = :user_id
      AND workout_exercise_id = :we_id
      AND status = 'pending'
    ORDER BY set_number ASC
    LIMIT 1
", [
    ":user_id" => $userId,
    ":we_id" => $workoutExerciseId
]);

/*
|--------------------------------------------------------------------------
| 4. ACTIVATE NEXT SET (IF EXISTS)
|--------------------------------------------------------------------------
*/

if ($next) {

    $next = $next[0];

    // make next set resting instead of active
    $db->sql("
        UPDATE user_workout_progress
        SET status = 'resting'
        WHERE id = :id
    ", [
        ":id" => $next->id
    ]);
}

echo json_encode([
    "success" => true
]);