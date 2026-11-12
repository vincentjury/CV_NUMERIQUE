<?php 
    ini_set('display_errors', 1);
	error_reporting(E_ALL);

    require_once('../back-end/core/functions.php');
    
    $titre_page="CREATION COMPTE CV NUMERIQUE";
    $nom_entite="JV TECH";
    $label_formulaire=array("E-mail","Mot de passe", "Nom d'utilisateur","Profil");

    function get_role($parametrage_connection, $nom_table) {
        $type_connection = '';
        $requete='';

        if ($parametrage_connection['server'] === 'local') {
            $type_connection = 'host';
        }

        $port_connect = '';
        if ($parametrage_connection['port'] !== null) {
            $port_connect = ';port=' . $parametrage_connection['port'];
        }

        $dsn = $parametrage_connection['driver'] . ':' . $type_connection . '=' . $parametrage_connection['domain'] . $port_connect . ';dbname=' . $parametrage_connection['nom_base'] . ';charset=' . $parametrage_connection['charset'];

        try {
            $pdo_cvnumerique = new PDO($dsn, $parametrage_connection['user'], $parametrage_connection['pass'], 
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            $requete=$pdo_cvnumerique -> query("SELECT libelle FROM " . $nom_table);
            $tableau_role= $requete -> fetchALL(PDO::FETCH_COLUMN);
            
            $pdo_cvnumerique=null;

            return $tableau_role;
        } 

        catch (PDOException $e) {
        $sqlState = $e->getCode();
        if ($parametrage_connection['driver'] === 'mysql') {
            if ($sqlState === 2002) {
                $message = 'le site est inaccessible (base inaccessible)';
            } else {
                $message = 'Erreur de connexion : ' . $e->getMessage();
            }
        }
        return ['Professionel', 'Salarie', 'Entreprise', 'Formateeur'];
        }
    }

        $donnees=get_constante();
        $parametrage_connection=$donnees['config'];
        $nom_table=$donnees['nom_table'];
        $nom_table="t_role";
        $liste_role=get_role($parametrage_connection, $nom_table);
    ?>  


<!DOCTYPE html>  
    <html>
        <head>
            <meta charset="utf-8">
            <title>
                <?php 
                    echo($titre_page);
                ?>
            </title>
            <link href="../style/style.css" rel="stylesheet">
            <link href="../style/inscription.css" rel="stylesheet">
             <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@300..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
        </head>
        <body class="font-fira-code ">
            <div class="entete">
                <div class="logo_cv">
                    <img src="../image/logo_lien/logo cv.png">
                </div>
                <div class="titre_entete">
                    <?php 
                        echo($nom_entite)
                    ?>
                </div>
            </div>
            <div class="main">
                <form method="post" action="../back-end/actions/signin.php">
                    <div class="inscription_email">
                        <label for="email">
                            <?php echo($label_formulaire[0]) ?>
                        </label>
                        <input type="email" name="email" id="email" maxlength=254 placeholder="Adresse <?php echo($label_formulaire[0]) ?> utilisée">
                    </div>
                    <div class="inscription_mot_passe">     
                        <label for="password">
                            <?php echo($label_formulaire[1]) ?>
                        </label>
                        <input type="password" name="password" id="password"  maxlength=254 placeholder="<?php echo($label_formulaire[1]) ?> utilisé">
                    </div>
                    <div class="inscription_username">
                        <label for="username">
                            <?php echo($label_formulaire[2]) ?>
                        </label>
                        <input type="text" name="username" id="username" maxlength=50 placeholder="<?php echo($label_formulaire[2]) ?> (optionnel)" >
                        <!-- maxlength=50 -->
                    </div>
                    <div class="inscription_user_role">
                        <label for="user_role">
                            <?php echo($label_formulaire[3]) ?>
                        </label>
                        <select name="user_role" id="user_role">
                            <?php foreach ($liste_role as $role): ?>
                            <option value="<?= htmlspecialchars($role)?>">
                                <?= htmlspecialchars($role)?>
                            </option> 
                            <?php endforeach; ?>  
                        </select>
                    </div>
                    <div class="inscription_zone_boutons">
                        <div class="bouton_validation">
                            <input type="submit" value="S'INSCRIRE">
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
            </div>
        </body>
    </html> 