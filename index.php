<?php 
        ini_set('display_errors', 1);
		error_reporting(E_ALL);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!empty($_SESSION['browser_console_logs'])) {
            echo "<script>\n";
            echo "console.group('🚀 --- DEBUG CONSOLE PHP (login.php) ---');\n";
            foreach ($_SESSION['browser_console_logs'] as $logScript) {
                echo "    " . $logScript . "\n";
            }
                echo "console.groupEnd();\n";
                echo "</script>\n";
            unset($_SESSION['browser_console_logs']);
        }

        $titre_page="COMPTE CV NUMERIQUE";
        $nom_entite="JV TECH";

        $label_form_ident="Identifiant";
        $label_form_choix_type="Type identifiant";
        $label_form_mdp="Mot de passe";

        $label_form_signin="Inscription";
        $label_form_fgtmdp="Mot de passe oublié";

        $liste_type_identifiant=["email", "username"];
        
        $message='';
        $color_message='';
        if(isset($_SESSION['erreur'])) 
            {$message=$_SESSION['erreur']; unset($_SESSION['erreur']); $color_message='rgb(255,0,0)';} 
        elseif (isset($_SESSION['succes'])) {$message=$_SESSION['succes']; unset($_SESSION['succes']);$color_message='rgb(0,255,0)';}
        else {$message='';}
       

    ?>

<!DOCTYPE html>
    <html>
        <head>
            <meta charset='utf-8'>
            <title>CV NUMERIQUE</title>
            <link href="./style/style.css" rel="stylesheet">
            <link href="./style/index.css" rel="stylesheet">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@300..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
        </head>
        <body class="font-fira-code ">
            <div class="entete">
                <div class="logo_cv">
                    <img src="./image/logo_lien/logo cv.png" >
                </div>
                <div class="titre_entete">
                    JV TECH
                </div>
            </div>
            <div class="main">
                <form method="post" action="./back-end/actions/login.php">                
                    <div class="message" style="color: <?= $color_message; ?>">
                        <?php 
                            if (!empty($message)) {
                                echo($message);
                            }
                        ?>
                    </div>    
                    <div class="zone_formulaire_identification">
                        <div class="definition_identifiant">
                                <label for="identifiant"> 
                                    <?php 
                                        echo($label_form_ident); 
                                    ?> 
                                </label>
                                <div class="choix_type_identifiant">
                                    <label for="type_identifiant">
                                        <?php echo($label_form_choix_type) ?>
                                    </label>

                                    <select name="type_identifiant" id="type_identifiant">
                                        <?php foreach ($liste_type_identifiant as $type_identifiant): ?>
                                            <option value="<?= htmlspecialchars($type_identifiant)?>">
                                            <?= htmlspecialchars($type_identifiant)?>
                                            </option> 
                                        <?php endforeach; ?>  
                                    </select>
                                </div>
                        </div>
                        <input type="text" name="identifiant" id="identifiant" maxlength=254 placeholder="Identifiant/E-mail">
                    </div>
                    <div class="zone_formulaire_mot_de_passe">
                        <label for="password">
                            <?php 
                                echo($label_form_mdp); 
                            ?> 
                        </label>
                        <input type="password" name="password" id="password" maxlength=254 placeholder="Mot de passe">
                    </div>
                    <div class="zone_formulaire_boutons">
                        <input type="submit" value="S'IDENTIFIER" class="bouton_identification">
                        <a href="./pages/inscription.php" class="bouton_inscription">
                            <?php 
                                echo($label_form_signin); 
                            ?>             
                        </a>
                        <a href="./pages/forgot_password.php" class="bouton_mdp_oublie">
                            <?php 
                                // echo("{$label_form_fgtmdp} ?"); 
                                echo($label_form_fgtmdp . " ?"); 
                            ?>                
                        </a> 
                    </div>
                       
                </form>
            </div>
            <div class="footer">
                <div class="indication_version">
                    v1.0.0
                </div>
            </div>

                     
        </body>
    </html>

