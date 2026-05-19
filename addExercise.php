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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $difficulty_id = (int)($_POST['difficulty_id'] ?? 0);
    $goal_id = (int)($_POST['goal_id'] ?? 0);

    $equipment_id = !empty($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : null;
    $muscle_group_id = !empty($_POST['muscle_group_id']) ? (int)$_POST['muscle_group_id'] : null;

    $is_global = !isset($_POST['is_global']) ? 1 : 0;

    if (!isset($userId)) {
        die("User not authenticated");
    }

    if ($name === '' || !$difficulty_id || !$goal_id) {
        die("Missing required fields");
    }

    // escape only name (your DB layer is unsafe for binds)
    $name = addslashes($name);

    // INSERT EXERCISE (RAW SQL ONLY)

    $db->sql("
        INSERT INTO exercises
            (name, description, difficulty_id, goal_id, created_by_member_id, is_global)
        VALUES
            ('$name', '$description', $difficulty_id, $goal_id, $userId, $is_global)
    ");

    // get last inserted id safely (works even with broken wrapper)
    $res = $db->sql("SELECT LAST_INSERT_ID() AS id", []);
    $exerciseId = is_array($res) ? $res[0]->id : $res->id;

    if (!$exerciseId) {
        die("Failed to create exercise");
    }

    // RELATIONS (RAW SQL)

    if ($equipment_id) {
        $db->sql("
            INSERT INTO exercise_equipment
                (exercise_id, equipment_id)
            VALUES
                ($exerciseId, $equipment_id)
        ");
    }

    if ($muscle_group_id) {
        $db->sql("
            INSERT INTO exercise_muscle_groups
                (exercise_id, muscle_group_id)
            VALUES
                ($exerciseId, $muscle_group_id)
        ");
    }

    header("Location: index.php");
    exit();
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

<!-- Top Nav -->
<nav class="flex-column">
    <div class="bg-info flex-center h-auto">
        <a class="mx-auto my-3" href="index.php"><img src="images/LogoWhite.png" alt="WhiteLogo" class="img-fluid" style="max-width: 100px;"></a>
        <?php include "include/navButtons.php" ?>
    </div>
    <img src="images/VectorInfo.svg" alt="dims">
</nav>

<form id="addExerciseForm" method="POST" class="container mb-5">

    <h1 class="montserrat fw-bold mt-4 mb-4">Tilføj Øvelse</h1>

    <!-- Visibility -->
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="is_global" name="is_global" value="1">
        <label class="form-check-label" for="is_global">Er øvelsen kun synlig for dig?</label>
    </div>

    <!-- Name & Description -->
    <div class="row">
        <div class="mb-3 col-sm">
            <label for="exercise_name" class="form-label"></label>
            <input id="exercise_name" class="form-control p-3" type="text" name="name" placeholder="Øvelsens Navn" required>
        </div>

        <div class="mb-3 col-sm">
            <label for="exercise_desc" class="form-label"></label>
            <textarea id="exercise_desc" class="form-control p-3" name="description" placeholder="Beskrivelse af øvelsen" required></textarea>
        </div>


    </div>

    <!-- Difficulty -->
    <div class="row">
        <div class="col-sm-6 mb-3">
            <label for="difficulty_id" class="form-label"></label>
            <select id="difficulty_id" class="form-select p-3" name="difficulty_id" required>
                <option value="">Niveau</option>
                <option value="1">Begynder</option>
                <option value="2">Øvet</option>
                <option value="3">Avanceret</option>
            </select>
        </div>

        <div class="col-sm-6 mb-3">
            <label for="muscle_group_id" class="form-label"></label>
            <select id="muscle_group_id" class="form-select p-3" name="muscle_group_id" required>
                <option value="">Muskelgruppe</option>
                <option value="1">Full body</option>
                <option value="2">Core</option>
                <option value="3">Arme</option>
                <option value="4">Ben</option>
                <option value="5">Bryst</option>
                <option value="6">Skulder</option>
                <option value="7">Ryg</option>
            </select>
        </div>
    </div>

    <!-- Goal + Equipment -->
    <div class="row">
        <div class="col-sm-6 mb-3">
            <label for="goal_id" class="form-label"></label>
            <select id="goal_id" class="form-select p-3" name="goal_id" required>
                <option value="">Fitness mål</option>
                <option value="1">Styrketræning</option>
                <option value="2">Muskelopbygning</option>
                <option value="3">Vægttab</option>
                <option value="4">Fysisk Vedligeholdelse</option>
                <option value="5">Genoptræning</option>
            </select>
        </div>

        <div class="col-sm-6 mb-3">
            <label for="equipment_id" class="form-label"></label>
            <select id="equipment_id" class="form-select p-3" name="equipment_id" required>
                <option value="">Udstyr</option>
                <option value="1">Hele Centret</option>
                <option value="2">Dumbbells & Barbells</option>
                <option value="3">Maskiner</option>
                <option value="4">Træningstilbehør</option>
            </select>
        </div>
    </div>

    <!-- Sets / Reps / Rest -->
    <div class="row">

        <div class="col-sm-4 mb-3">
            <label for="sets" class="form-label"></label>
            <input id="sets" class="form-control p-3" type="number" name="sets" placeholder="Sets">
        </div>

        <div class="col-sm-4 mb-3">
            <label for="reps" class="form-label"></label>
            <input id="reps" class="form-control p-3" type="text" name="reps" placeholder="Reps">
        </div>

        <div class="col-sm-4 mb-3">
            <label for="rest_seconds" class="form-label"></label>
            <input id="rest_seconds" class="form-control p-3" type="number" name="rest_seconds" placeholder="Hviletid (sek)">
        </div>

    </div>

</form>

<!-- Bottom Nav -->
<footer class="mt-auto rounded-top-circle bg-white position-sticky bottom-0" style="min-height: 85px;">
    <div class="d-flex justify-content-around" style="margin-top: -25px; margin-bottom: 25px;">
        <!-- Back BTN -->
        <div class="flex-column-center gap-1" >
            <a class="p-3 bg-secondary rounded-circle" href="index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M42.1875 22.4997C42.1875 22.8727 42.0393 23.2303 41.7756 23.4941C41.5119 23.7578 41.1542 23.9059 40.7813 23.9059L7.61344 23.9059L16.4644 32.7541C16.7284 33.0181 16.8768 33.3763 16.8768 33.7497C16.8768 34.1231 16.7284 34.4813 16.4644 34.7453C16.2003 35.0094 15.8422 35.1577 15.4688 35.1577C15.0953 35.1577 14.7372 35.0094 14.4731 34.7453L3.22313 23.4953C3.09217 23.3647 2.98827 23.2095 2.91737 23.0387C2.84648 22.8678 2.80999 22.6847 2.80999 22.4997C2.80999 22.3147 2.84648 22.1316 2.91737 21.9607C2.98827 21.7899 3.09217 21.6347 3.22313 21.5041L14.4731 10.2541C14.7372 9.99001 15.0953 9.84166 15.4688 9.84166C15.8422 9.84166 16.2003 9.99001 16.4644 10.2541C16.7284 10.5181 16.8768 10.8763 16.8768 11.2497C16.8768 11.6231 16.7284 11.9813 16.4644 12.2453L7.61344 21.0934L40.7813 21.0934C41.1542 21.0934 41.5119 21.2416 41.7756 21.5053C42.0393 21.769 42.1875 22.1267 42.1875 22.4997V22.4997Z"
                          fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0 text-nowrap">Tilbage</p>
        </div>

        <!-- Tilføj Øvelse -->
        <div class="flex-column-center gap-1" >
            <button type="submit" form="addExerciseForm" class="p-3 bg-primary rounded-circle border-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 35 35" fill="none">
                    <g clip-path="url(#clip0_247_303)">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.26643 32.4842C6.0958 33.4554 7.05393 34.2145 7.8983 33.7814L17.5014 28.8464L27.1024 33.7814C27.9467 34.2145 28.9049 33.4554 28.7342 32.4864L26.9186 22.1395L34.6252 14.7983C35.3449 14.1114 34.9708 12.8558 34.0061 12.7201L23.2917 11.1976L18.5142 1.73232C18.4232 1.54045 18.2796 1.37834 18.1002 1.26483C17.9207 1.15132 17.7127 1.09106 17.5003 1.09106C17.288 1.09106 17.08 1.15132 16.9005 1.26483C16.721 1.37834 16.5774 1.54045 16.4864 1.73232L11.7089 11.1998L0.994554 12.7223C0.0298669 12.8579 -0.344196 14.1136 0.375492 14.8004L8.08205 22.1417L6.26643 32.4886V32.4842ZM16.9961 26.4314L8.93299 30.5745L10.4511 21.9186C10.4867 21.7193 10.4728 21.5144 10.4106 21.3218C10.3484 21.1291 10.2399 20.9547 10.0946 20.8139L3.73768 14.7545L12.6014 13.4945C12.785 13.4668 12.9591 13.395 13.1088 13.2853C13.2585 13.1756 13.3794 13.0312 13.4611 12.8645L17.5014 4.86263L21.5396 12.8645C21.6213 13.0312 21.7422 13.1756 21.8919 13.2853C22.0416 13.395 22.2157 13.4668 22.3992 13.4945L31.263 14.7523L24.9061 20.8117C24.7603 20.9527 24.6514 21.1274 24.5892 21.3205C24.5271 21.5135 24.5134 21.719 24.5496 21.9186L26.0677 30.5745L18.0046 26.4314C17.8486 26.351 17.6758 26.309 17.5003 26.309C17.3249 26.309 17.152 26.351 16.9961 26.4314Z" fill="white"/>
                    </g>
                    <defs>
                        <clipPath id="clip0_247_303">
                            <rect width="35" height="35" fill="white"/>
                        </clipPath>
                    </defs>
                </svg>
            </button>
            <p class="text-center montserrat fw-bold m-0">Tilføj</p>
        </div>
    </div>
</footer>

<script src="scripts/canvas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
