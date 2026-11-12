<?php   
    require_once '../back-end/actions/CVA4_loader.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(0, '/');
        session_start();
    }

    if (!isset($_SESSION['browser_console_logs'])) {
        $_SESSION['browser_console_logs'] = [];
    }



    if (isset($_GET['debug_curl'])) {
        echo "<pre>";
        print_r($_POST);
        echo "</pre>";
        exit;
    }

    $nom_entite="JV TECH";

    $cv_data=[];

    $marge_interne = isset($_SESSION['marge_interne']) ? $_SESSION['marge_interne'] : '20px';

    $label_selection_poste="CV à visualiser";
    $label_bouton_validation="Visualiser"; 
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_valider_selection'])) 
        {   
            $_SESSION["CV_poste"]=$_POST["postes"];
        }
    load_data();
    $poste_actif=$_SESSION["CV_poste"] ?? '';

    $cv_data=$_SESSION["import_cv_data"];
    if (!empty($_SESSION['browser_console_logs'])) {
        echo "<script>\n";
        echo "console.group('🚀 --- DEBUG CONSOLE PHP (CV_A4.php) ---');\n";
        foreach ($_SESSION['browser_console_logs'] as $logScript) {
            echo "    " . $logScript . "\n";
        }
        echo "console.groupEnd();\n";
        echo "</script>\n";
        unset($_SESSION['browser_console_logs']);
    }
?>
<!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8">
            <title>CV numerique JURY Vincent</title>
            <link href="../style/style.css" rel="stylesheet">
            <link href="../style/CV_A4.css" rel="stylesheet">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@300..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
        </head>
        <body>
            <div class="entete">
                <div class="zone_entete">
                    <div class="entite">
                        <div class="logo_cv">
                            <img src="../image/logo_lien/logo cv.png">
                        </div>
                        <div class="titre_entete">
                            <?php 
                                echo($nom_entite);
                            ?>
                        </div>
                    </div>
                    <div class="bouton_deconnexion">
                        <a href="../index.php">
                            Deconnexion
                        </a>
                    </div>
                </div>
            </div>
            <div class="main">
                <div class="zone_select_poste">
                    <form method="POST" action="" class="formulaire_selection_poste">
                        <label for="postes_select" class="label_selection_poste"><?php echo($label_selection_poste);?></label>
                        <select name="postes" id="postes_select" class="selection_poste">
                            <?php if (isset($_SESSION["liste_poste"])) : ?>
                                <?php foreach($_SESSION["liste_poste"] as $poste) : ?>
                                    <option value="<?php echo $poste; ?>" <?php echo ($poste_actif === $poste) ? 'selected' : ''; ?>>
                                        <?php echo $poste; ?>
                                    </option>
                                <?php endforeach;?>
                            <?php endif;?>  
                        </select>
                        <input type="submit" name='btn_valider_selection' value=<?php echo($label_bouton_validation) ?> class="bouton_selection_poste">
                    </form>
                </div>
                <div class="format_A4">
                    <div class="sidebar">
                        <div class="zone_photo">
                            <img src="../image/photo_cv.jpg">
                        </div>
                        <div class="zone_presentation">
                            <div class="zone_apropos" style="margin-bottom : <?php echo($marge_interne)?>">
                                <div class="side_titre1"><?php echo $cv_data["apropos"]["titre"];?></div>
                                <div class="contenu_apropose"><?php echo $cv_data["apropos"]["texte"];?></div>
                            </div>
                            <div class="zone_contact" style="margin-bottom : <?php echo($marge_interne)?>"> 
                                <div class="side_titre1"><?php echo $cv_data["identite"]["titre"];?></div>
                                <div class="contenu_contact">
                                    <ul>
                                        <li><?php echo ("📧 " . $cv_data["identite"]["mail"]);?></li>
                                        <li><?php echo ("📱 " . $cv_data["identite"]["téléphone"]);?></li>
                                        <li><?php echo ("📍 " . $cv_data["identite"]["localisation"]);?></li>
                                        <li><?php echo ("🪪 " . $cv_data["identite"]["permis"]);?></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="zone_competence" style="margin-bottom : <?php echo($marge_interne)?>">
                                <div class="side_titre1"><?php echo $cv_data["competences"]["titre"]?></div>
                                    <div class="contenu_competence">
                                        <?php foreach ($cv_data["competences"]["categories"] as $categorie): ?>
                                        <div class="domaine-competence">
                                            <div class="domaines_competence_titre">
                                                <?php echo $categorie["nom_groupe"]; ?>
                                            </div>

                                            <?php foreach ($categorie["items"] as $item): ?>
                                                <div class="skill-item">
                                                    <span class="liste_competence"><?php echo $item["nom"]; ?></span>
                                                    <div class="jauge_exp">
                                                        <div class="jauge_exp_reel" style="width: <?php echo ($item["niveau"] . "%"); ?>;"></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="zone_outil" style="margin-bottom : <?php echo($marge_interne)?>">
                                <div class="side_titre1"><?php echo $cv_data["outils"]["titre"]?></div>
                                <div class="contenu_outil">
                                    <?php foreach ($cv_data["outils"]["categories"] as $categorie): ?>
                                        <div class="domaine-outils">
                                            <div class="domaines_outils_titre">
                                                <?php echo $categorie["nom_groupe"]; ?>
                                            </div>

                                            <?php foreach ($categorie["items"] as $item): ?>
                                                <div class="skill-item">
                                                    <span class="liste_outil"><?php echo $item["nom"]; ?></span>
                                                    <div class="jauge_exp">
                                                        <div class="jauge_exp_reel" style="width: <?php echo ($item["niveau"] . "%"); ?>;"></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="zone_langue" style="margin-bottom : <?php echo($marge_interne)?>">
                                <div class="side_titre1"><?php echo $cv_data["langues"]["titre"]?></div>
                                <div class="contenu_langue">
                                    <?php foreach ($cv_data["langues"]["items"] as $item): ?>
                                        <div class="skill-item">
                                            <span class="liste_langue"><?php echo $item["nom"]; ?></span>
                                            <div class="jauge_exp">
                                                <div class="jauge_exp_reel" style="width: <?php echo ($item["niveau"] . "%"); ?>;"></div>
                                            </div>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            </div>
                            <div class="zone_softskill">                           
                                <div class="side_titre1"><?php echo $cv_data["softskills"]["titre"]?></div>
                                <div class="contenu_softskill">
                                    <?php foreach ($cv_data["softskills"]["items"] as $item): ?>
                                        <div class="skill-item">
                                            <span class="liste_softskill"><?php echo $item; ?></span>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mainbar">
                        <div class="zone_presentation">
                            <div class="identite_denomination">
                                <?php echo ($cv_data["identite"]["nom"] . " " . $cv_data["identite"]["prenom"]) ?>
                            </div>
                            <div class="poste">
                                <?php echo ($cv_data["identite"]["poste"] . " " . $cv_data["identite"]["experience"]) ?>
                            </div>

                        </div>
                        <div class="zone_experience">
                            <div class="titre_zone_main">
                                <?php echo ($cv_data["experiences"]["titre"]) ?>
                            </div>
                            <?php foreach ($cv_data["experiences"]["items"] as $item): ?>
                                <div class="encart_experience_detail">
                                    <div class="infos_experience_poste_periode">
                                        <span class="titre_poste"><?php echo ($item["poste"]) ?></span>
                                        <span class="periode"><?php echo ($item["date_embauche"] . " - " . $item["date_debauche"]) ?></span>
                                    </div>
                                    <div class="infos_employeur">
                                        <span>
                                            <?php 
                                                if (empty($item["client"])) {$entreprise=strtoupper($item["employeur"]);}
                                                else {$entreprise=strtoupper($item["employeur"]) . " - " . $item["client"];}
                                                echo($entreprise);
                                            ?>
                                        </span>
                                        <span><?php echo($item["lieu_emploi"] . "(" . $item["departement_emploi"] . ")") ; ?></span>
                                    </div>
                                    <div class="liste_activite">
                                        <?php foreach($item["missions"] as $mission): ?>
                                            <li><?php echo($mission);?></li>
                                            
                                        <?php endforeach ?>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <div class="zone_formation">
                            <div class="titre_zone_main">
                                <?php echo ($cv_data["formations"]["titre"]) ?>
                            </div>
                            <?php foreach ($cv_data["formations"]["items"] as $item): ?>
                                <div class="encart_formation_detail">
                                    <div class="infos_formation_poste_periode">
                                        <span class="titre_formation"><?php echo ($item["diplome"]) ?></span>
                                        <span class="periode"><?php echo ($item["date_debut_formation"] . " - " . $item["date_fin_formation"]) ?></span>
                                    </div>
                                    <div class="infos_formateur">
                                        <span><?php echo($item["organisme_formation"]);?></span>
                                        <span><?php echo($item["lieu_formation"] . "(" . $item["departement_formation"] . ")") ; ?></span>
                                    </div>
                                    <div class="liste_activite">
                                        <?php foreach($item["details"] as $detail): ?>
                                            <li><?php echo($detail);?></li>  
                                        <?php endforeach ?>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer">
                <div class="indication_version">
                    v1.0.0
                </div>
                <div class="lien_media">
                    
                    <a href="https://github.com/vincentjury" target="_blank">
                        <img src="../image/logo_lien/github.png">
                    </a>
                    <a href="www.linkedin.com/in/vincent-jury-241896322" target="_blank">
                        <img src="../image/logo_lien/linkedin.png">
                    </a>
                    
                </div>
            </div>     
        </body>
    </html>