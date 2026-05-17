<?php
require "settings/init.php";
session_start();

if (empty($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}

$workoutId = $_GET['workout_id'] ?? null;

if (!$workoutId) {
    exit("Missing workout_id");
}

/* Get workout info */
$workout = $db->sql("
    SELECT id, workout_number, name, program_id
    FROM workouts
    WHERE id = :id
    LIMIT 1
", [
    ":id" => $workoutId
]);

if (!$workout) {
    exit("Workout not found");
}

$workout = $workout[0];

/* Get exercises for THIS workout only */
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
    ":workout_id" => $workoutId
]);
?>

<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($workout->name) ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>

<h1>
    Day <?= (int)$workout->workout_number ?> - <?= htmlspecialchars($workout->name) ?>
</h1>

<?php if (empty($exercises)): ?>
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

</body>
</html>