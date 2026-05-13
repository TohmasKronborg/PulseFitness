<?php
/**
 * @var db $db
 */

require "settings/init.php";

session_start();
$resultMessage = '';

if (!empty($_POST["data"])) {
    $data = $_POST["data"];

    // Input field
    $memberNumber = trim($data["member_number"]);

    // Correct SQL
    $sql = "SELECT member_id, member_name, member_number FROM members WHERE member_number = :member_number";

    $bind = [":member_number" => $memberNumber];

    $stmt = $db->sql($sql, $bind, false);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Only check if member exists
    if ($user) {
        $_SESSION['userId'] = $user['member_id'];
        $_SESSION['username'] = $user['member_name'];

        header("Location: index.php?=" . urlencode($user['member_name']));
        exit();
    } else {
        $resultMessage = "Ugyldigt medlemsnummer";
    }
}

?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">

    <title>Login</title>

    <meta name="robots" content="All">
    <meta name="author" content="Udgiver">
    <meta name="copyright" content="Information om copyright">

    <link href="css/styles.css" rel="stylesheet" type="text/css">
    <link rel="icon" href="images/LogoBlack.png">

    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body class="position-relative">
<canvas id="funnyBg" class="position-fixed z-n1 start-50 translate-middle-x" style="filter:blur(10px)"></canvas>

<div class="container flex-center vh-100">
    <div class="row mb-5">
        <div class="flex-column-center mb-3">
            <img src="images/Logo.png" class="img-fluid" width="200px" alt="logotest">
            <img src="images/LogoText.png" class="img-fluid" width="225px" alt="logotest">
        </div>

        <form class="mb-5" action="login.php" method="POST">
            <div class="mb-4">
                <label for="memberId" class="form-label"></label>
                <input type="text" class="form-control border-0 rounded-4 fw-bold" id="memberId" name="data[member_number]" placeholder="<?php echo !empty($resultMessage) ? $resultMessage : "Medlemsnummer"; ?>" style="height: 60px;" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-dark text-white rounded-4 px-4 ms-auto fs-5">Log ind</button>
        </form>
    </div>
</div>


<script src="scripts/canvas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
