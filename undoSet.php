<?php
/**
 * @var db $db
 */

require "settings/init.php";
session_start();

$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
    exit(json_encode(["success" => false, "msg" => "Not logged in"]));
}

$progressId = $_POST['progress_id'] ?? null;

if (!$progressId) {
    exit(json_encode(["success" => false, "msg" => "Missing data"]));
}

/*
|-------------------------------------------------------
| 1. FETCH ROW (security check)
|-------------------------------------------------------
*/
$row = $db->sql("
    SELECT *
    FROM user_workout_progress
    WHERE id = :id
      AND user_id = :user_id
", [
    ":id" => $progressId,
    ":user_id" => $userId
]);

if (!$row) {
    exit(json_encode(["success" => false, "msg" => "Not found"]));
}

$row = $row[0];

/*
|-------------------------------------------------------
| 2. RESET CURRENT SET
|-------------------------------------------------------
*/
$db->sql("
    UPDATE user_workout_progress
    SET status = 'active',
        completed_at = NULL
    WHERE id = :id
", [
    ":id" => $progressId
]);


// 3. Safety Ensure only one active set exists
$db->sql("
    UPDATE user_workout_progress
    SET status = 'active',
        completed_at = NULL
    WHERE id = :id
", [
    ":id" => $progressId
]);

/*
|-------------------------------------------------------
| ONLY ensure ONE active set per exercise
| (do NOT reset everything)
|-------------------------------------------------------
*/
$db->sql("
    UPDATE user_workout_progress
    SET status = 'pending'
    WHERE user_id = :user_id
      AND workout_exercise_id = :we_id
      AND status = 'active'
      AND id != :id
", [
    ":user_id" => $userId,
    ":we_id" => $row->workout_exercise_id,
    ":id" => $progressId
]);

echo json_encode(["success" => true]);
