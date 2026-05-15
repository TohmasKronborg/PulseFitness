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
</style>
<body class="mx-auto overflow-x-hidden flex-column position-relative" style="max-width: 768px; height: 100vh">

<!-- Dims Nav -->
<nav class="mb-4">
    <a href="index.php"><img src="images/LogoWhite.png" alt="WhiteLogo" class="img-fluid position-absolute top-0 start-50 translate-middle mt-5" style="max-width: 100px;"></a>
</nav>

<!-- Stage indicators -->
<div class="montserrat fw-bold d-flex justify-content-around">
    <div class="indicatorCircle bg-primary text-light flex-center rounded-circle">
        <div class="indicatorCircle-active flex-center rounded-circle border border-3 border-light">
            <span>1</span>
        </div>
    </div>
    <div class="indicatorCircle bg-white text-dark flex-center rounded-circle">
        <span>2</span>
    </div>
    <div class="indicatorCircle bg-white text-dark flex-center rounded-circle">
        <span>3</span>
    </div>
    <div class="indicatorCircle bg-white text-dark flex-center rounded-circle">
        <span>4</span>
    </div>
    <div class="indicatorCircle bg-white text-dark flex-center rounded-circle">
        <span>5</span>
    </div>

</div>

<!-- THE form -->
<form action="" class="container-fluid mt-5">
    <div class="p-4 d-flex bg-white rounded-4 align-items-center gap-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="34" height="29" viewBox="0 0 34 29" fill="none">
            <g clip-path="url(#clip0_135_559)">
                <path d="M7.69996 1.78009L4.03996 1.50009C3.16996 1.43009 2.32996 1.86009 1.85996 2.60009C1.35996 3.41009 1.38996 4.44009 1.93996 5.21009L4.56996 8.88009C6.12996 11.0601 8.85996 12.0801 11.47 11.4501L15.49 10.4901L13.42 5.81009C12.4 3.51009 10.21 1.96009 7.69996 1.77009V1.78009Z" stroke="#202437" stroke-width="3" stroke-miterlimit="10" stroke-linecap="round"/>
                <path d="M28.3499 10.2101L30.9099 7.59015C31.5199 6.96015 31.7299 6.05015 31.4599 5.21015C31.1599 4.31015 30.3499 3.67015 29.3999 3.60015L24.8999 3.26015C22.2299 3.06015 19.6999 4.50015 18.4999 6.90015L16.6599 10.6001L21.5799 12.0101C23.9899 12.7001 26.5899 12.0101 28.3499 10.2101Z" stroke="#202437" stroke-width="3" stroke-miterlimit="10" stroke-linecap="round"/>
                <path d="M3 27H31.14" stroke="#202437" stroke-width="3" stroke-miterlimit="10" stroke-linecap="round"/>
                <path d="M16.96 26.6901L16.68 24.3801C16.55 23.3001 16.73 22.2001 17.2 21.2201C17.98 19.5901 17.94 17.6901 17.11 16.0901L16.61 15.1201C16.15 14.2401 15.93 13.2501 15.97 12.2601L16.03 10.5801" stroke="#202437" stroke-width="3" stroke-miterlimit="10" stroke-linecap="round"/>
            </g>
            <defs>
                <clipPath id="clip0_135_559">
                    <rect width="33.21" height="28.36" fill="white"/>
                </clipPath>
            </defs>
        </svg>
        <p class="fs-1 m-0 montserrat fw-bold">Begynder</p>
    </div>
</form>

<!-- Bottom Nav -->
<footer class="mt-auto rounded-top-circle bg-white" style="height: 85px">
    <div class="d-flex justify-content-around" style="margin-top: -25px;">
        <!-- Button Back -->
        <div class="flex-column-center gap-1">
            <a class="p-3 bg-info rounded-circle" href="index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M42.1875 22.4997C42.1875 22.8727 42.0393 23.2303 41.7756 23.4941C41.5119 23.7578 41.1542 23.9059 40.7813 23.9059L7.61344 23.9059L16.4644 32.7541C16.7284 33.0181 16.8768 33.3763 16.8768 33.7497C16.8768 34.1231 16.7284 34.4813 16.4644 34.7453C16.2003 35.0094 15.8422 35.1577 15.4688 35.1577C15.0953 35.1577 14.7372 35.0094 14.4731 34.7453L3.22313 23.4953C3.09217 23.3647 2.98827 23.2095 2.91737 23.0387C2.84648 22.8678 2.80999 22.6847 2.80999 22.4997C2.80999 22.3147 2.84648 22.1316 2.91737 21.9607C2.98827 21.7899 3.09217 21.6347 3.22313 21.5041L14.4731 10.2541C14.7372 9.99001 15.0953 9.84166 15.4688 9.84166C15.8422 9.84166 16.2003 9.99001 16.4644 10.2541C16.7284 10.5181 16.8768 10.8763 16.8768 11.2497C16.8768 11.6231 16.7284 11.9813 16.4644 12.2453L7.61344 21.0934L40.7813 21.0934C41.1542 21.0934 41.5119 21.2416 41.7756 21.5053C42.0393 21.769 42.1875 22.1267 42.1875 22.4997V22.4997Z"
                          fill="white"/>
                </svg>
            </a>
            <p class="text-center montserrat fw-bold m-0">Annuller</p>
        </div>

        <!-- Button Next -->
        <div class="flex-column-center gap-1">
            <button class="p-3 bg-primary rounded-circle border-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M2.8125 22.5003C2.8125 22.1273 2.96066 21.7697 3.22438 21.5059C3.4881 21.2422 3.84579 21.0941 4.21875 21.0941L37.3866 21.0941L28.5356 12.2459C28.2716 11.9819 28.1232 11.6237 28.1232 11.2503C28.1232 10.8769 28.2716 10.5187 28.5356 10.2547C28.7997 9.99063 29.1578 9.84229 29.5313 9.84229C29.9047 9.84229 30.2628 9.99063 30.5269 10.2547L41.7769 21.5047C41.9078 21.6353 42.0117 21.7905 42.0826 21.9613C42.1535 22.1322 42.19 22.3153 42.19 22.5003C42.19 22.6853 42.1535 22.8684 42.0826 23.0393C42.0117 23.2101 41.9078 23.3653 41.7769 23.4959L30.5269 34.7459C30.2628 35.01 29.9047 35.1583 29.5313 35.1583C29.1578 35.1583 28.7997 35.01 28.5356 34.7459C28.2716 34.4819 28.1232 34.1237 28.1232 33.7503C28.1232 33.3769 28.2716 33.0187 28.5356 32.7547L37.3866 23.9066L4.21875 23.9066C3.84579 23.9066 3.4881 23.7584 3.22438 23.4947C2.96066 23.231 2.8125 22.8733 2.8125 22.5003V22.5003Z"
                          fill="white"/>
                </svg>
            </button>
            <p class="text-center montserrat fw-bold m-0">Næste</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
