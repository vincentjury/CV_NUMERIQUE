<?php
    
require_once(__DIR__ . '/../core/functions.php');
require_once (__DIR__ . '/../../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_set_cookie_params(0, '/');
session_start();

ob_start();

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['browser_console_logs'])) {
            $_SESSION['browser_console_logs'] = [];
        }
        
        $messageErreur = "💥 [FATAL ERROR] " . $error['message'] . " dans " . basename($error['file']) . " à la ligne " . $error['line'];
        
        $_SESSION['browser_console_logs'][] = "console.error(" . json_encode($messageErreur) . ");";
        
        if (ob_get_length()) {
            @ob_clean();
        }
        
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

function is_formulaire_valide ($donnees_formulaire, $structure_table,&$message) {
    is_empty($donnees_formulaire, $message);
    if (!empty($message)) {
        if (str_contains($message, "val_recup")) {$message=str_replace("val_recup", $donnees_formulaire["met_recup"],$message);}
        return;
    }
    $liste_caracteres_invalide=get_caracteres_invalides();

    is_caracteres_valides($donnees_formulaire,$liste_caracteres_invalide,$message,$donnees_formulaire["met_recup"]);
    if (!empty($message)) {
        if (str_contains($message, "val_recup")) {$message=str_replace("val_recup", $donnees_formulaire["met_recup"],$message);}
        return;
    }

    
    $donnees_comparee["champs_saisis"]=$donnees_formulaire["met_recup"];
    $donnees_comparee["valeurs_saisies"]=$donnees_formulaire["val_recup"];
    $cles[]=$donnees_comparee["champs_saisis"];
    $valeurs[]=$donnees_comparee["valeurs_saisies"];

    $donnees_valide_pour_comparaison=array_combine($cles,$valeurs);
    is_lengh_valid($structure_table, $donnees_valide_pour_comparaison, $message);
    if (!empty($message)) {
        if (str_contains($message, "val_recup")) {$message=str_replace("val_recup", $donnees_formulaire["met_recup"],$message);}
        return;
    }
}   

function is_identifiant_exist($parametrage_connection, $nom_table, $structure_table, $donnees_formulaire, &$message, &$mail) {
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

    try {
        $pdo_cvnumerique = new PDO($dsn, $parametrage_connection['user'], $parametrage_connection['pass'], 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $requete = "SELECT * FROM " . $nom_table . " WHERE " . $donnees_formulaire["met_recup"] . " = ? LIMIT 1";

        $statut_recherche= $pdo_cvnumerique->prepare($requete);
        $statut_recherche->execute([
            $donnees_formulaire["val_recup"]
        ]);
        $utilisateur_exist=$statut_recherche->fetch(PDO::FETCH_ASSOC);
        $pdo_cvnumerique = null;

        if ($utilisateur_exist) {
            $message="";
            $mail=$utilisateur_exist['email'];
        }
        else {
            $message="le compte n'existe pas";
        }



    }
    catch (PDOException $e) {
        $sqlState = $e->getCode();
        if ($parametrage_connection['driver'] === 'mysql') {
            if ($sqlState === '2002') {
                $message = 'le site est inaccessible (base inaccessible)';
            } else {
                $message = 'Erreur de connexion : ' . $e->getMessage();
            }
        }
        return [];
    }
}

function update_default_password ($parametrage_connection, $nom_table, $structure_table, $donnees_formulaire, &$password_clair, &$message) {
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

        $password_clair="dftpass123" . $donnees_formulaire["val_recup"]. date('dmY');
        $password_hash=password_hash($password_clair,PASSWORD_DEFAULT);

        $requete = "UPDATE " . $nom_table . " SET password = ? WHERE " . $donnees_formulaire["met_recup"] . " = ? LIMIT 1";
        $statut_update= $pdo_cvnumerique->prepare($requete);
        $statut_update->execute([
            $password_hash,
            $donnees_formulaire["val_recup"]
        ]);

        $pdo_cvnumerique = null;
    }
    catch (PDOException $e) {
        $sqlState = $e->getCode();
        if ($parametrage_connection['driver'] === 'mysql') {
            if ($sqlState === '2002') {
                $message = 'le site est inaccessible (base inaccessible)';
            } else {
                $message = 'Erreur de connexion : ' . $e->getMessage();
            }
        }
        return [];
    }
}

function send_mail($config_smtp ,$destinataire, $nouveau_mot_de_passe, &$message) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $config_smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config_smtp['user'];
        $mail->Password   = $config_smtp['pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;                            

        $mail->setFrom($config_smtp['user'], 'JV TECH');
        $mail->addAddress($destinataire);

        $mail->isHTML(true);
        $mail->Subject = 'Reinitialisation de votre mot de passe - JV TECH';
        $mail->Body    = 'Bonjour,<br><br>Voici votre mot de passe temporaire : <b>' . htmlspecialchars($nouveau_mot_de_passe) . '</b><br><br>Nous vous conseillons de le modifier des votre prochaine connexion.';
        $mail->AltBody = 'Bonjour, voici votre mot de passe temporaire : ' . $nouveau_mot_de_passe;

        $mail->send();
        
    } catch (Exception $e) {
        $message = "L'e-mail n'a pas pu être envoyé. Erreur : {$mail->ErrorInfo}";
    }
}

$message="";
$mail="";
$password="";

$saisie_envoye=get_form();
$donnees_saisies=array_combine($saisie_envoye["champs_saisis"],$saisie_envoye["valeurs_saisies"]);
$donnees_saisies["met_recup"]=mb_strtolower($donnees_saisies["met_recup"], 'UTF-8');


$contante=get_constante();
$parametrage_connection=$contante["config"];
$nom_table_profil=$contante["nom_table"];
$config_smtp=$contante["config-smtp"];

$structure_table_profil=get_structure_table($parametrage_connection,$nom_table_profil,$message);
if ($message !== "") {goto interaction_utilisateur ;}

is_formulaire_valide($donnees_saisies, $structure_table_profil, $message);
if ($message !== "") {goto interaction_utilisateur ;}

is_identifiant_exist($parametrage_connection, $nom_table_profil, $structure_table_profil, $donnees_saisies, $message, $mail);
if ($message !== "") {goto interaction_utilisateur ;}

update_default_password($parametrage_connection, $nom_table_profil, $structure_table_profil, $donnees_saisies, $password, $message);
if ($message !== "") {goto interaction_utilisateur ;}

send_mail($config_smtp ,$mail, $password, $message);
if ($message !== "") {goto interaction_utilisateur ;}


interaction_utilisateur:
if (empty($message)) {
    $_SESSION['succes']="Email de recupération envoyé";
    unset($_SESSION['erreur']);
    header('Location: ../../index.php');
    exit;
}
else {
    $_SESSION['erreur']=$message;
    header('Location: ../../index.php');
    exit;
}





$script_logs = "";
if (!empty($_SESSION['browser_console_logs'])) {
    $logs_js = implode("\n    ", $_SESSION['browser_console_logs']);
    $script_logs = "<script>\n    " . $logs_js . "\n</script>";
    unset($_SESSION['browser_console_logs']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Debug</title>
    <?php echo $script_logs; ?>
</head>
<body>
</body>
</html>