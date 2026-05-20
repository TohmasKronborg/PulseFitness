<?php
/**
 * @var db $db
 */
require "settings/init.php";
session_start();

$userId = $_SESSION['userId'] ?? null;
$progressId = $_POST['progress_id'] ?? null;

if (!$userId || !$progressId) {
    echo json_encode(["success" => false]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 1. Mark rest as completed
|--------------------------------------------------------------------------
*/
$current = $db->sql("
    SELECT *
    FROM user_workout_progress
    WHERE id = :id AND user_id = :user_id
    LIMIT 1
", [
    ":id" => $progressId,
    ":user_id" => $userId
]);

if (!$current) {
    echo json_encode(["success" => false]);
    exit;
}

$current = $current[0];

$db->sql("
    UPDATE user_workout_progress
    SET status = 'completed'
    WHERE id = :id
", [
    ":id" => $progressId
]);

/*
|--------------------------------------------------------------------------
| 2. Activate NEXT pending set (ONLY ONE active per exercise)
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
    ":we_id" => $current->workout_exercise_id
]);

if ($next) {
    $next = $next[0];

    $db->sql("
        UPDATE user_workout_progress
        SET status = 'active'
        WHERE id = :id
    ", [
        ":id" => $next->id
    ]);
}

echo json_encode(["success" => true]);