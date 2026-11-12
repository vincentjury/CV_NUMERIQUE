<?php
require_once(__DIR__ . '/../core/functions.php');

session_set_cookie_params(0, '/');
session_start();

ob_start();

register_shutdown_function(function() {
    $error = error_get_last();
    // Liste des erreurs fatales qui coupent le script
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['browser_console_logs'])) {
            $_SESSION['browser_console_logs'] = [];
        }
        
        // On formate proprement le message d'erreur
        $messageErreur = "💥 [FATAL ERROR] " . $error['message'] . " dans " . basename($error['file']) . " à la ligne " . $error['line'];
        
        // json_encode sécurise à 100% l'injection dans une chaîne JS (anti-bug de caractères spéciaux et de '$')
        $_SESSION['browser_console_logs'][] = "console.error(" . json_encode($messageErreur) . ");";
        
        // Nettoyer toute sortie précédente pour éviter la page blanche
        if (ob_get_length()) {
            @ob_clean();
        }
        
        // Affichage de la page de debug épurée
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Debug - Erreur Fatale</title></head>";
        echo "<body style='font-family: monospace; background: #1e1e1e; color: #f8f8f2; padding: 20px;'>";
        echo "<h3 style='color: #ef4444;'>🚨 Erreur fatale PHP interceptée !</h3>";
        echo "<p>Ouvrez la console de votre navigateur (touche <b>F12</b> > onglet <b>Console</b>) pour consulter l'historique complet.</p>";
        echo "<script>";
        echo "console.group('💥 --- DEBUG CONSOLE PHP (ÉCHEC & ERREUR FATALE) ---');\n";
        foreach ($_SESSION['browser_console_logs'] as $logScript) {
            echo "    " . $logScript . "\n";
        }
        echo "console.groupEnd();\n";
        echo "</script>";
        echo "</body></html>";
        
        unset($_SESSION['browser_console_logs']);
    }
});

if (isset($_GET['debug_curl'])) {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

}


function get_tableau_role($parametrage_connection, $nom_table, &$message) {
    $type_connection = '';

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

        $stmt = $pdo_cvnumerique->query("SELECT role_code, libelle FROM " . $nom_table);
        $contenu = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdo_cvnumerique = null;

        return $contenu;
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
        return []; // On retourne un tableau vide pour garder un type de retour cohérent
    }
}

function insert_profile($parametrage_connection, $nom_table, $saisie, $structure_table, $correspondance_role, &$message) {
    $type_connection = '';
    $valeurs_a_inserer=[];
    if ($parametrage_connection['server'] === 'local') {
        $type_connection = 'host';
    }

    $port_connect = '';
    if ($parametrage_connection['port'] !== null) {
        $port_connect = ';port=' . $parametrage_connection['port'];
    }

    $dsn = $parametrage_connection['driver'] . ':' . $type_connection . '=' . $parametrage_connection['domain'] . $port_connect . ';dbname=' . $parametrage_connection['nom_base'] . ';charset=' . $parametrage_connection['charset'];
    

    $position_userrole=array_search('user_role', array_keys($saisie));
    if ($position_userrole !== false) {

        $valeur_a_modifier=$saisie["user_role"];
        $position_userrole_corresp=array_search($valeur_a_modifier, array_column($correspondance_role, 'libelle'));
        if ($position_userrole_corresp !== false) {
            $valeur_a_implemente=$correspondance_role[$position_userrole_corresp]['role_code'];
            $saisie["user_role"]=$valeur_a_implemente;
        } 
        else {$message="role utilisateur inexistant, merci de ressayer ulterieurement"; return;}
    } 
    else { $message="saisie de role utilisateur hs, merci de ressayer ulterieurement"; return;}

    $remplissage_requete=false;
    $colonnes_autorisee=['email','password','username','user_role'];
    $requete_partie_zone=[];
    $requete_partie_value=[];
    $valeurs_insere=[];

    foreach($saisie as $nom_champ=>$valeur_saisie) {
        
    
        if (in_array($nom_champ,$colonnes_autorisee) && !empty($valeur_saisie)) {
            $requete_partie_zone[]=$nom_champ;
            $requete_partie_value[]="?";
            $valeurs_a_inserer[]=$valeur_saisie;
        }
    }

    $requete_partie_zone=implode(", ", $requete_partie_zone);
    $requete_partie_value=implode(", ", $requete_partie_value);

    try {
        $pdo_cvnumerique = new PDO($dsn, $parametrage_connection['user'], $parametrage_connection['pass'], 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $requete="INSERT INTO " . $nom_table . " (" . $requete_partie_zone . ") VALUES (" . $requete_partie_value . ");";
        $insertion= $pdo_cvnumerique->prepare($requete) ;
        $insertion->execute($valeurs_a_inserer);
        $pdo_cvnumerique = null;
    }
    catch (PDOException $e) {
        $sqlState = $e->getCode();
        
        if ($parametrage_connection['driver'] === 'mysql') {
            if ($sqlState === '2002') {
                $message = 'le site est inaccessible (base inaccessible)';
            } elseif ($sqlState === '23000') {
                $message = "le profil existe déja, veuillez vous connecter";
            } else {
                $message = 'Erreur de connexion : ' . $e->getMessage();
            }
        }
        return []; // On retourne un tableau vide pour garder un type de retour cohérent
    }
}

function is_form_valid ($donnees_saisies, $parametres_connections, $nom_table, $structure_table, &$message) {

    
    is_empty($donnees_saisies,$message, ["username"]); 
    if (!empty($message)) {return;}

    $caracteres_invalides=get_caracteres_invalides();

    is_caracteres_valides($donnees_saisies, $caracteres_invalides, $message);
    if (!empty($message)) {return;}

    is_lengh_valid($structure_table, $donnees_saisies, $message);
    if (!empty($message)) {return;}
}


//récuperer les données posté
    $message='';
    $form_donnees=get_form();
    $input_donnees=array_combine($form_donnees["champs_saisis"],$form_donnees["valeurs_saisies"]);
//recupere la structure de la table t_userprofil
    $constantes=get_constante();
    $parametrage_connection=$constantes['config'];
    $nom_table=$constantes['nom_table'];
    $structure_table=get_structure_table($parametrage_connection,$nom_table, $message);
    if ($message !== "") {goto interaction_utilisateur;}
//recupere les données pour correspondance t_role
    $nom_table="t_role";
    $tableau_role=get_tableau_role($parametrage_connection,$nom_table, $message);
    if ($message !== "") {goto interaction_utilisateur;}
//verifier le format de chaque (caracateres interdits, longueur)
    is_form_valid ($input_donnees, $parametrage_connection, $nom_table, $structure_table, $message);
    if ($message !== "") {goto interaction_utilisateur;}

//on insert si format entièrement valide dans t_userprofil
    $nom_table="t_userprofil";
    $input_donnees['password']=password_hash($input_donnees['password'],PASSWORD_DEFAULT);
    $profil=insert_profile($parametrage_connection, $nom_table, $input_donnees, $structure_table, $tableau_role, $message);
    // exit;
    if ($message !== "") {goto interaction_utilisateur;}


interaction_utilisateur:


If (empty($message)) {
    $_SESSION['succes']="Profil créé avec succés";
    unset($_SESSION['erreur']);
    header('Location: ../../index.php');
    exit;
} else {
    $_SESSION['erreur']=$message;
    header('Location: ../../index.php');
    exit;
}


?>