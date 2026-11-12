<?php
// Capture la fin du script, y compris les erreurs fatales dans les fonctions

function get_log($titre, $nom_variable, $valeur) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['browser_console_logs'])) {
        $_SESSION['browser_console_logs'] = [];
    }
    
    // Les deux lignes pour formater proprement le titre et la variable
    $_SESSION['browser_console_logs'][] = "console.log('📍 [TRACE] " . addslashes($titre) . "');";
    $_SESSION['browser_console_logs'][] = "console.log('📦 [VARIABLE] \\$" . $nom_variable . " :', " . json_encode($valeur) . ");";
}

function get_constante(){
    $parametrage_connection = [
        'driver' => 'mysql', 
        'server' => 'local', 
        'domain' => 'sql108.infinityfree.com',
        'port' => null, 
        'nom_base' => 'if0_43423904_cv_numerique',
        'user' => 'if0_43423904', 
        'pass' => 'jvtech332026', 
        'charset' => 'utf8mb4'
    ];

    $nom_table = 't_userprofil';

    $smtp= [
            "host" => "smtp-jv-tech-33.alwaysdata.net",
            "user" => "jv-tech-33@alwaysdata.net",
            "pass" => "JV-tech-33.03112026!!"
        ];

    return ['config' => $parametrage_connection, 'nom_table' => $nom_table, 'config-smtp' => $smtp];
}

function get_caracteres_invalides() {
    return [
        'sql_request' => ['\\'], 
        'email' => [    
            ' ', '!', '#', '$', '%', '^', '&', '*', 
            '(', ')', '+', '=', '{', '}', '[', ']', '|', 
            '\\', ':', ';', '"', "'", '<', '>', '?', '/'
        ],
        'username' => [
            ' ', '!', '@', '#', '$', '%', '^', '&', '*', 
            '(', ')', '+', '=', '{', '}', '[', ']', '|', 
            '\\', ':', ';', '"', "'", '<', '>', ',', '.', '?', '/'
        ]
    ];
}


function get_form() {      
    return ['champs_saisis' => array_keys($_POST), 'valeurs_saisies' => array_values($_POST)];
}

function get_structure_table($parametrage_connection, $nom_table, &$message) {
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

        $stmt = $pdo_cvnumerique->query("DESCRIBE " . $nom_table);
        $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdo_cvnumerique = null;

        return $structure;
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

function is_empty($champ_a_tester, &$message, $liste_champ_non_controle=[]) {
    $liste_champ_vide = [];
    $nombre_champ_ignoree=count($liste_champ_non_controle);

    foreach ($champ_a_tester as $nom_champ => $valeur_saisie) {
        // Sécurité : on ignore si ce n'est pas une chaîne de caractères
        if (!is_string($valeur_saisie)) {
            continue;
        }
        
        if($nombre_champ_ignoree) {
            if (in_array($nom_champ,$liste_champ_non_controle)) {
            continue;}
        }
        if (trim($valeur_saisie) === '') {
            $liste_champ_vide[] = $nom_champ; 
        }
    }
    if (count($liste_champ_vide) > 0) {
        $message = implode(', ', $liste_champ_vide);
        if (count($liste_champ_vide) === 1) {
            $message = $message . " est vide";
        } else {
            $message = $message . " sont vides";
        }
    }

    return $message;
}



function is_caracteres_valides($champ_a_tester, $caracteres_invalides, &$message, $type_identifiant = null) {
    
    $liste_caracteres_sql=implode('', $caracteres_invalides['sql_request']);

    $message_requete="";
    $nombre_champ_incorect_requete=0;
    $caracteres_trouves_sql = "";

    $message_format="";
    $nombre_champ_incorect_format=0;
    $caracteres_trouves_format = "";

    foreach ($champ_a_tester as $nom_champ => $champ_teste) {
        // $liste_caracteres_interdit_select= (array_count($regles_format))  ? $regles_format : [];
        
        if (!is_string($champ_teste)) {
            continue;
        }

        if ($type_identifiant !== null && isset($caracteres_invalides[$type_identifiant])) {
            $regles_format = $caracteres_invalides[$type_identifiant];
        } elseif (($nom_champ ==='email' || $nom_champ ==='username') && !empty($champ_teste)) {
            $regles_format = $caracteres_invalides[$nom_champ];
        } else {
            $regles_format = [];
        }

        $liste_caracteres_interdits= implode("",$regles_format);
        
        
        if (strpbrk($champ_teste, $liste_caracteres_sql)) {
            $nombre_champ_incorect_requete=$nombre_champ_incorect_requete+1;
            $message_requete = (empty($message_requete)) ? $nom_champ : ($message_requete . ", " . $nom_champ); 
            
            for ($position_caractere = 0; $position_caractere < strlen($champ_teste); $position_caractere++) {
                $char = $champ_teste[$position_caractere];
                if (strpos($liste_caracteres_sql, $char) !== false && strpos($caracteres_trouves_sql, $char) === false) {
                    $caracteres_trouves_sql .= (empty($caracteres_trouves_sql)) ? $char : ", " . $char;
                }
            }
            continue;
        }     
        

        elseif (!empty($liste_caracteres_interdits) && strpbrk($champ_teste,$liste_caracteres_interdits) && $nom_champ !=="password") {
            $nombre_champ_incorect_format=$nombre_champ_incorect_format+1;
            $message_format = (empty($message_format)) ? $nom_champ : ($message_format . ", " . $nom_champ);
            
            for ($i = 0; $i < strlen($champ_teste); $i++) {
                $char = $champ_teste[$i];
                if (strpos($liste_caracteres_interdits, $char) !== false && strpos($caracteres_trouves_format, $char) === false) {
                    $caracteres_trouves_format .= (empty($caracteres_trouves_format)) ? $char : ", " . $char;
                }
            }   
            
        }
    }


    if ($nombre_champ_incorect_requete === 1) {
        $message = $message_requete . " est incorrect : caractères interdits pour la requête (caracteres: " . $caracteres_trouves_sql . ").";
    } elseif ($nombre_champ_incorect_requete > 1) {
        $message = $message_requete . " sont incorrects : caractères interdits pour la requête (caracteres: " . $caracteres_trouves_sql . ").";
    }


    if ($nombre_champ_incorect_format === 1) {
        $texte_format = $message_format . " est incorrect : caractères interdits pour le format (caracteres: " . $caracteres_trouves_format . ").";
        $message = empty($message) ? $texte_format : $message . " / " . $texte_format;
    } elseif ($nombre_champ_incorect_format > 1) {
        $texte_format = $message_format . " sont incorrects : caractères interdits pour le format (caracteres: " . $caracteres_trouves_format . ").";
        $message = empty($message) ? $texte_format : $message . " / " . $texte_format;
    }

    return $message;

}

function is_lengh_valid($structure_table, $champ_a_tester, &$message) {
    $nombrechamp_non_valide=0;
   
    foreach ($champ_a_tester as $nom_champ => $champ_teste) {
        
        if (!is_string($champ_teste)) {
            continue;
        }
;

        if ($nom_champ !== 'user_role') {
            //recuperation de la longueur indiqué
            $position_champ=array_search($nom_champ, array_column($structure_table, 'Field'));  
            if ($position_champ !== false) {
                $type_champ=$structure_table[$position_champ]['Type'];
                $longueur_champ_autorise=(int)((explode("(", $type_champ))[1]);
            } else {$longueur_champ_autorise=0;}
            //comparaison de longueur
            if (mb_strlen($champ_teste) > $longueur_champ_autorise) {
                $nombrechamp_non_valide=$nombrechamp_non_valide+1;
                if (empty($message)) {$message=$nom_champ;} else {$message= $message . ", " . $nom_champ;}
            }

            
            
            
        }
    }

    if (empty($message) === false) {
    if ($nombrechamp_non_valide === 1) {$message= $message . " dépasse la longueur autorisé";}
    else {$message= $message . " dépassent la longueur autorisé";}
    }

}


?>