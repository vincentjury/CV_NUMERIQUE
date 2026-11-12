<?php
session_start();
$message = '';

$titre_page = "TABLEAU DE BORD";
$nom_entite = "JV TECH";
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset='utf-8'>
        <title><?= $titre_page ?></title>
        <link href="../style/style.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@300..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    </head>
    <body class="font-fira-code">
        <?php
            header("Location: ./CV_A4.php");
            exit;
        ?>
    </body>
</html>