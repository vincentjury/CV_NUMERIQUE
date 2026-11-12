<?php 
    $titre="Recuperation du mot de passe";
    $nom_entite="JV TECH";
    $label_formulaire=array("Moyen de recuperation", "Information saisie");
    $moyen_recup=array("Email", "Username");
?>
<!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8">
            <title>
                <?php 
                    echo($nom_entite);
                ?>
            </title>
            <link href="../style/style.css" rel="stylesheet">
            <link href="../style/forgot_password.css" rel="stylesheet">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@300..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
        </head>
        <body class="font-fira-code">
            <div class="entete">
                <div class="logo_cv">
                    <img src="../image/logo_lien/logo cv.png">
                </div>
                <div class="titre_entete">
                    <?php 
                        echo($nom_entite);
                    ?>
                </div>
            </div>
            <div class="main">
                <form method="post" action="../back-end/actions/resetpswd.php">
                    <div class="zone_methode_recuperation">
                        <label for="met_recup">
                            <?php 
                                echo($label_formulaire[0])
                            ?>
                        </label>
                        <select name="met_recup" id="met_recup">
                            <?php foreach ($moyen_recup as $med_recup): ?>              
                                <option value="<?php echo htmlspecialchars($med_recup); ?>">
                                    <?php echo htmlspecialchars($med_recup); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="zone_valeur_recuperation">
                        <label for="val_recup">
                            <?php 
                                echo($label_formulaire[1])
                            ?>
                        </label>
                        <input type="text" name="val_recup" id="val_recup" >
                    </div>
                    <div class="zone_bouton">
                        <div class="bouton_valider">
                            <input type="submit" value="Envoyer">
                        </div>
                        <div class="bouton_retour">
                            <a href="../index.php">
                                Retour
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="footer">
                <div class="indication_version">
                    v1.0.0
                </div>
                <!-- <div class="lien_langue">
                    <a href="#lien_transfo_francais">FR</a>
                    <a href="#lien_transfo_anglais">EN</a>
                </div> -->
            </div>
        </body>
    </html>