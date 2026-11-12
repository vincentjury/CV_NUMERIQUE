<?php 

require_once(__DIR__ . '/../core/functions.php');

session_set_cookie_params(0, '/');
session_start();

if (!isset($_SESSION['browser_console_logs'])) {
    $_SESSION['browser_console_logs'] = [];
}



if (isset($_GET['debug_curl'])) {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    exit;
}


function read_enregistrement($parametrage_connection, $nom_table, $identification_saisie, &$message) {
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

        $requete_sql= "SELECT * FROM " . $nom_table . " WHERE " . $identification_saisie['identifiant'][0] . " = ?";
        $stmt = $pdo_cvnumerique->prepare($requete_sql);
        $stmt->execute([
            $identification_saisie['identifiant'][1]
        ]);
        
        $utilisateur_recherche = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdo_cvnumerique = null;
        if ($utilisateur_recherche && password_verify($identification_saisie['password'][1], $utilisateur_recherche['password'])) {
            return [
                'exist' => true,
                'utilisateur' => $utilisateur_recherche
            ];
        } else {
            return [
                'exist' => false,
                'utilisateur' => null
            ];
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


function is_form_valid ($donnees_saisies, $parametres_connections, $nom_table, $structure_table, &$message, &$type_identifiant, &$champs_testes) {
    
    $type_identifiant = null;

    $index_position = array_search('type_identifiant', $donnees_saisies["champs_saisis"]);

    
    if ($index_position !== false) {
        $type_identifiant = array_splice($donnees_saisies['valeurs_saisies'], $index_position, 1)[0];
        array_splice($donnees_saisies['champs_saisis'], $index_position, 1);
    } 
    
    $champ_a_tester = array_combine($donnees_saisies['champs_saisis'], $donnees_saisies['valeurs_saisies']);
    
    if ($type_identifiant !== null && isset($champ_a_tester['identifiant'])) {
        $champ_a_tester = [$type_identifiant => $champ_a_tester['identifiant']] + $champ_a_tester;
        unset($champ_a_tester['identifiant']);
    }
    is_empty($champ_a_tester,$message); 
    if (!empty($message)) {return;}

    $caracteres_invalides = get_caracteres_invalides();
    is_caracteres_valides($champ_a_tester, $caracteres_invalides, $message, $type_identifiant);
    if (!empty($message)) {return;}


    is_lengh_valid($structure_table, $champ_a_tester, $message);
    if (!empty($message)) {return;}

    $champs_testes=$champ_a_tester;
}


$message = '';
$type_identifiant=null;
$champs_a_verifier=[];

$donnees = get_constante();
$parametrage_connection = $donnees['config'];
$nom_table = $donnees['nom_table'];


$structure_table = get_structure_table($parametrage_connection, $nom_table, $message);


if ($message !== "") {goto interaction_utilisateur;}


$saisie = get_form();


is_form_valid($saisie, $parametrage_connection, $nom_table, $structure_table, $message, $type_identifiant, $champs_a_verifier);
if ($message !== "") {goto interaction_utilisateur;}


$type_identifiant = $type_identifiant === null ? "email" : $type_identifiant;
$index_identifiant=array_search($type_identifiant, array_column($structure_table, 'Field'));
$index_password = array_search('password', array_column($structure_table, 'Field'));


$identification_saisie = [
    'identifiant' => [$structure_table[$index_identifiant]['Field'], $champs_a_verifier[$type_identifiant]],
    'password'    => [$structure_table[$index_password]['Field'], $champs_a_verifier['password']] 
];

$utilisateur_recherche = read_enregistrement($parametrage_connection, $nom_table, $identification_saisie, $message);


if (!$utilisateur_recherche['exist']) {
    $message = "L'identifiant ou le mot de passe est incorrect";
}

interaction_utilisateur:
if ($message === '') {
    $_SESSION['utilisateur']=$utilisateur_recherche['utilisateur'];
    unset($_SESSION['erreur']);
    header('Location: ../../pages/tableau_bord.php');
    exit;

} else {
    $_SESSION['erreur']=$message;
    header('Location: ../../index.php');
    exit;
}