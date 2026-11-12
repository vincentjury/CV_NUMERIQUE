<?php

    require_once(__DIR__ . '/../core/functions.php');

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(0, '/');
        session_start();
    }


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

    function get_liste_poste($parametrage_connection, &$message) {
        $type_connection = '';

        if ($parametrage_connection['server'] === 'local') {
            $type_connection = 'host';
        }

        $port_connect = '';
        if ($parametrage_connection['port'] !== null) {
            $port_connect = ';port=' . $parametrage_connection['port'];
        }

        $dsn = $parametrage_connection['driver'] . ':' . $type_connection 
        . '=' . $parametrage_connection['domain'] . $port_connect . ';dbname=' 
        . $parametrage_connection['nom_base'] . ';charset=' . $parametrage_connection['charset'];


         try {
            $cv_numerique= new PDO($dsn,$parametrage_connection['user'],$parametrage_connection['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $requete = "SELECT cvdta.nom_poste FROM t_cv_data cvdta ORDER BY cvdta.id_cv_data ASC";
                $recuperation_liste_poste = $cv_numerique->prepare($requete);
                $recuperation_liste_poste->execute([]);
                $liste_poste =  $recuperation_liste_poste->fetchAll(PDO::FETCH_ASSOC);
                $_SESSION["liste_poste"]=[];
                Foreach ($liste_poste as $poste) {
                    $_SESSION["liste_poste"][]= "CV ". ucwords($poste["nom_poste"]);
                }
                $cv_numerique=null;
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
        }
    }

    function read_cv_data($poste, $parametrage_connection,  &$datas , &$message) {
        $type_connection = '';

        if ($parametrage_connection['server'] === 'local') {
            $type_connection = 'host';
        }

        $port_connect = '';
        if ($parametrage_connection['port'] !== null) {
            $port_connect = ';port=' . $parametrage_connection['port'];
        }

        $dsn = $parametrage_connection['driver'] . ':' . $type_connection 
        . '=' . $parametrage_connection['domain'] . $port_connect . ';dbname=' 
        . $parametrage_connection['nom_base'] . ';charset=' . $parametrage_connection['charset'];


        $identite=&$datas["main_side"]["identite"];
        $a_propos=&$datas["side_bar"]["apropos"];
        $competence=&$datas["side_bar"]["competences"];
        $outils=&$datas["side_bar"]["outils"];
        $langues=&$datas["side_bar"]["langues"];
        $softskills=&$datas["side_bar"]["softskills"];
        $experience=&$datas["main_bar"]["experiences"];
        $formation=&$datas["main_bar"]["formations"];

        try {
            $cv_numerique= new PDO($dsn,$parametrage_connection['user'],$parametrage_connection['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

                $requete = "SELECT cvdta.nom_famille, cvdta.prenom, cvdta.experience, cva.contenu FROM t_cv_accroche cva  INNER JOIN t_cv_data cvdta ON cva.id_cv_accroche= cvdta.phrase_accroche WHERE cvdta.nom_poste = ?";
                $recherche_poste_definition = $cv_numerique->prepare($requete);
                $recherche_poste_definition->execute([$poste]);
                $poste_definition = $recherche_poste_definition->fetch(PDO::FETCH_OBJ);
            
                $identite["nom"] = $poste_definition->nom_famille;
                $identite["prenom"] = $poste_definition->prenom;
                $identite["poste"] = ucwords($poste);
                $identite["experience"] = $poste_definition->experience;

                $a_propos["texte"] = $poste_definition->contenu;

                $requete = "SELECT type_contact, libelle FROM t_cv_contact WHERE preference_order = ?";
                $recuperation_liste_contact=$cv_numerique->prepare($requete);
                $recuperation_liste_contact->execute([1]);
                $liste_contact = $recuperation_liste_contact->fetchAll(PDO::FETCH_ASSOC);  
                $listing_media=array_column($liste_contact,"libelle","type_contact");

                Foreach($listing_media as $type_media => $valeur_media) {$identite[$type_media]=$listing_media[$type_media];}
                $requete = "SELECT 
                        cvdc1.nom_domaine_competence AS domaine_1,
                        cvdta.domaine_1_competence_nombre AS nombre_1,
                        cvdc2.nom_domaine_competence AS domaine_2,
                        cvdta.domaine_2_competence_nombre AS nombre_2,
                        cvdc3.nom_domaine_competence AS domaine_3,
                        cvdta.domaine_3_competence_nombre AS nombre_3,
                        cvdc4.nom_domaine_competence AS domaine_4,
                        cvdta.domaine_4_competence_nombre AS nombre_4
                    FROM t_cv_data cvdta
                    LEFT JOIN t_cv_domaine_competence cvdc1 ON cvdta.domaine_competence_1 = cvdc1.id_cv_domaine_competence
                    LEFT JOIN t_cv_domaine_competence cvdc2 ON cvdta.domaine_competence_2 = cvdc2.id_cv_domaine_competence
                    LEFT JOIN t_cv_domaine_competence cvdc3 ON cvdta.domaine_competence_3 = cvdc3.id_cv_domaine_competence
                    LEFT JOIN t_cv_domaine_competence cvdc4 ON cvdta.domaine_competence_4 = cvdc4.id_cv_domaine_competence
                    WHERE cvdta.nom_poste = ?";
                $recuperation_mapping_competence = $cv_numerique->prepare($requete);
                $recuperation_mapping_competence->execute([$poste]);
                $mapping_domaine_competence = $recuperation_mapping_competence->fetch(PDO::FETCH_ASSOC);
                for($nombre_domaine_competence=1;$nombre_domaine_competence<=4;$nombre_domaine_competence+=1) {
                if(
                    empty($mapping_domaine_competence["nombre_" . $nombre_domaine_competence]) 
                    ||  $mapping_domaine_competence["nombre_" . $nombre_domaine_competence] === NULL
                ) 
                {continue;}  

                $competence['categories'][]= ['nom_groupe' => ucwords($mapping_domaine_competence["domaine_" . $nombre_domaine_competence]), 'items' => []];
                $categorie_courante = &$competence['categories'][$nombre_domaine_competence - 1];

                $requete="SELECT 
                            cvc.nom_competence as competence, 
                            cvc.pourcentage_maitrise as maitrise 
                        FROM t_cv_competence cvc 
                        INNER JOIN t_cv_domaine_competence cvdc ON cvdc.id_cv_domaine_competence = cvc.id_domaine 
                        WHERE cvdc.nom_domaine_competence = ?
                        ORDER BY preference_order ASC";
                $recuperation_competence = $cv_numerique->prepare($requete);
                $recuperation_competence->execute([$mapping_domaine_competence["domaine_" . $nombre_domaine_competence]]);
                $liste_competence = $recuperation_competence->fetchAll(PDO::FETCH_ASSOC);
                Foreach($liste_competence as $detail_competence) {
                    $categorie_courante['items'][]=['nom' => ucwords($detail_competence["competence"]), 'niveau' => $detail_competence["maitrise"]];
                }
                }   
            

                $requete = "SELECT 
                        cvdo1.nom_domaine_outil AS domaine_1,
                        cvdta.outil_1_nombre AS nombre_1,
                        cvdo2.nom_domaine_outil AS domaine_2,
                        cvdta.outil_2_nombre AS nombre_2,
                        cvdo3.nom_domaine_outil AS domaine_3,
                        cvdta.outil_3_nombre AS nombre_3,
                        cvdo4.nom_domaine_outil AS domaine_4,
                        cvdta.outil_3_nombre AS nombre_4
                    FROM t_cv_data cvdta
                    LEFT JOIN t_cv_domaine_outil cvdo1 ON cvdta.domaine_outil_1 = cvdo1.id_cv_domaine_outil
                    LEFT JOIN t_cv_domaine_outil cvdo2 ON cvdta.domaine_outil_2 = cvdo2.id_cv_domaine_outil
                    LEFT JOIN t_cv_domaine_outil cvdo3 ON cvdta.domaine_outil_3 = cvdo3.id_cv_domaine_outil
                    LEFT JOIN t_cv_domaine_outil cvdo4 ON cvdta.domaine_outil_4 = cvdo4.id_cv_domaine_outil
                    WHERE cvdta.nom_poste = ?";
                $recuperation_mapping_outil = $cv_numerique->prepare($requete);
                $recuperation_mapping_outil->execute([$poste]);
                $mapping_domaine_outil = $recuperation_mapping_outil->fetch(PDO::FETCH_ASSOC);
                for($nombre_domaine_outil=1; $nombre_domaine_outil<=4; $nombre_domaine_outil+=1) {
                    if( 
                        empty($mapping_domaine_outil["nombre_" . $nombre_domaine_outil]) 
                        ||  $mapping_domaine_outil["nombre_" . $nombre_domaine_outil] === NULL
                    ) 
                    {continue;}  

                    
                    $domaine_outil=$mapping_domaine_outil["domaine_" . $nombre_domaine_outil];
                    if ($domaine_outil === null){continue;}
                    $nom_groupe= mb_strtoupper(mb_substr($domaine_outil, 0, 1, "UTF-8"), "UTF-8") . mb_substr($domaine_outil, 1, mb_strlen($domaine_outil, "UTF-8"), "UTF-8");
                
                
               
                    $outils['categories'][]= ['nom_groupe' => $nom_groupe, 'items' => []];
                    $categorie_courante = &$outils['categories'][$nombre_domaine_outil - 1];

                    $requete="SELECT 
                            cvo.nom_outil as outil, 
                            cvo.pourcentage_maitrise as maitrise 
                        FROM t_cv_outils cvo 
                        INNER JOIN t_cv_domaine_outil cvdo ON cvdo.id_cv_domaine_outil = cvo.id_domaine 
                        WHERE cvdo.nom_domaine_outil = ?
                        ORDER BY preference_order ASC";
                    $recuperation_outil = $cv_numerique->prepare($requete);
                    $recuperation_outil->execute([$mapping_domaine_outil["domaine_" . $nombre_domaine_outil]]);
                    $liste_outils = $recuperation_outil->fetchAll(PDO::FETCH_ASSOC);    
                    Foreach($liste_outils as $detail_outil) {
                        $categorie_courante['items'][]=['nom' => ucwords($detail_outil["outil"]), 'niveau' => $detail_outil["maitrise"]];
                    }
                }

                $requete = "SELECT cvl.nom_langue as langue, cvl.pourcentage_maitrise as maitrise FROM t_cv_langue cvl ORDER BY cvl.preference_order ASC LIMIT 3";
                $recuperation_liste_langue = $cv_numerique->prepare($requete);
                $recuperation_liste_langue->execute([]);
                $liste_langue = $recuperation_liste_langue->fetchAll(PDO::FETCH_ASSOC);
                foreach($liste_langue as $langue) {
                    $langues['items'][]=['nom' => ucwords($langue["langue"]), 'niveau' => $langue["maitrise"]];
                }
                $requete="SELECT libelle_softskill as softskill FROM t_cv_softskill";
                $recuperation_softskill = $cv_numerique->prepare($requete);
                $recuperation_softskill->execute([]);
                $liste_softskills = $recuperation_softskill->fetchAll(PDO::FETCH_ASSOC);
                foreach($liste_softskills as $softskill){
                    $softskills['items'][]=ucwords($softskill["softskill"]);
                }

                $requete="SELECT 
                            experience_1, experience_1_n_activite as nombre_activite_1, 
                            experience_2, experience_2_n_activite as nombre_activite_2, 
                            experience_3, experience_3_n_activite as nombre_activite_3, 
                            experience_4, experience_4_n_activite as nombre_activite_4, 
                            experience_5, experience_5_n_activite as nombre_activite_5
                        FROM t_cv_data 
                        WHERE nom_poste = ?";
                $recuperation_mapping_experience = $cv_numerique->prepare($requete);
                $recuperation_mapping_experience->execute([$poste]);
                $mapping_experience = $recuperation_mapping_experience->fetch(PDO::FETCH_ASSOC);
                For($nombre_experience=1; $nombre_experience<=5; $nombre_experience+=1){
                    if($mapping_experience["experience_" . $nombre_experience] === null || $mapping_experience["nombre_activite_" . $nombre_experience] === 0) 
                        {continue;}
                    
                    $requete="SELECT  
                                    cve.nom_poste as poste, 
                                    cve.date_embauche as embauche, 
                                    cve.date_debauche as debauche,
                                    cve.employeur as employeur, 
                                    cve.client as client, 
                                    cve.lieu_emploi as lieu_poste, 
                                    cve.departement_emploi as dpt_poste,
                                    cve.activite_1 as activite_1, 
                                    cve.activite_2 as activite_2, 
                                    cve.activite_3 as activite_3,
                                    cve.activite_4 as activite_4, 
                                    cve.activite_5 as activite_5, 
                                    cve.activite_6 as activite_6,
                                    cve.activite_7 as activite_7, 
                                    cve.activite_8 as activite_8, 
                                    cve.activite_9 as activite_9, 
                                    cve.activite_10 as activite_10
                                FROM t_cv_experience cve 
                                WHERE cve.id_cv_experience = ?   
                            ";
                    $recuperation_contenu_experience = $cv_numerique->prepare($requete);
                    $recuperation_contenu_experience->execute([$mapping_experience["experience_" . $nombre_experience]]);
                    $contenu_experience = $recuperation_contenu_experience->fetch(PDO::FETCH_ASSOC);
                    
                    if ($contenu_experience["client"] === null) 
                        {$contenu_experience["client"]='';}
                    else {$contenu_experience["client"]=ucwords($contenu_experience["client"]);}

                    $experience['items'][]=[
                        'poste' => ucwords($contenu_experience["poste"]),
                        'date_embauche' => date('m/Y',strtotime($contenu_experience["embauche"])),
                        'date_debauche' => date('m/Y',strtotime($contenu_experience["debauche"])),
                        'employeur' => ucwords($contenu_experience["employeur"]),
                        'client' => $contenu_experience["client"],
                        'lieu_emploi' => ucwords($contenu_experience["lieu_poste"]),
                        'departement_emploi' => $contenu_experience["dpt_poste"]
                        ,
                        'missions' => []
                    ];

                    for($nombre_activite=1; $nombre_activite<=$mapping_experience["nombre_activite_" . $nombre_experience];$nombre_activite+=1)
                        {
                            $mission_detail=$contenu_experience["activite_" . $nombre_activite];
                            
                            $experience['items'][$nombre_experience-1]['missions'][]=
                            mb_strtoupper(mb_substr($mission_detail, 0, 1, "UTF-8"), "UTF-8") . mb_substr($mission_detail, 1, mb_strlen($mission_detail, "UTF-8"), "UTF-8");
                        }
                }
                
                $requete="SELECT  
                                formation_1, formation_1_n_activite as nombre_activite_1,
                                formation_2, formation_2_n_activite as nombre_activite_2,
                                formation_3, formation_3_n_activite as nombre_activite_3,
                                formation_4, formation_4_n_activite as nombre_activite_4,
                                formation_5, formation_5_n_activite as nombre_activite_5
                            FROM t_cv_data
                            WHERE nom_poste = ?
                            ";
                $recuperation_mapping_formation = $cv_numerique->prepare($requete);
                $recuperation_mapping_formation->execute([$poste]);
                $mapping_formation = $recuperation_mapping_formation->fetch(PDO::FETCH_ASSOC);

                For($nombre_formation=1;$nombre_formation<=5;$nombre_formation+=1){
                    if($mapping_formation["formation_" . $nombre_formation] === null || $mapping_formation["nombre_activite_" . $nombre_formation] === null ) 
                        {continue;}

                    $requete="SELECT 
                                cvf.nom_formation,
                                cvf.date_debut_formation as debut_formation,
                                cvf.date_fin_formation as fin_formation,
                                cvf.organisme_formation,
                                cvf.lieu_formation, 
                                cvf.departement_formation as dpt_formation,
                                cvf.activite_1 as activite_1, 
                                cvf.activite_2 as activite_2, 
                                cvf.activite_3 as activite_3,
                                cvf.activite_4 as activite_4, 
                                cvf.activite_5 as activite_5, 
                                cvf.activite_6 as activite_6,
                                cvf.activite_7 as activite_7, 
                                cvf.activite_8 as activite_8, 
                                cvf.activite_9 as activite_9, 
                                cvf.activite_10 as activite_10
                            FROM t_cv_formation cvf
                            WHERE id_cv_formation = ?";
                    $recuperation_detail_formation = $cv_numerique->prepare($requete);
                    $recuperation_detail_formation->execute([$mapping_formation["formation_" . $nombre_formation]]);
                    $detail_formation = $recuperation_detail_formation->fetch(PDO::FETCH_ASSOC);

                    $formation['items'][]=[
                        'diplome' => ucwords($detail_formation["nom_formation"]),
                        'date_debut_formation' => date('m/Y',strtotime($detail_formation["debut_formation"])),
                        'date_fin_formation' => date('m/Y',strtotime($detail_formation["fin_formation"])),
                        'organisme_formation' => ucwords($detail_formation["organisme_formation"]),
                        'lieu_formation' => ucwords($detail_formation["lieu_formation"]),
                        'departement_formation' => $detail_formation["dpt_formation"],
                        'details' => []
                    ];

                    for($nombre_activite_formation=1;$nombre_activite_formation<=$mapping_formation["nombre_activite_" . $nombre_formation];$nombre_activite_formation+=1) 
                    {
                        $detail_activite=$detail_formation["activite_" . $nombre_activite_formation];
                        $formation['items'][$nombre_formation - 1]['details'][]=
                        mb_strtoupper(mb_substr($detail_activite, 0, 1, "UTF-8"), "UTF-8") . 
                        mb_substr($detail_activite, 1, mb_strlen($detail_activite, "UTF-8"), "UTF-8");
                    }
                }

                $cv_numerique=null;
        
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
        }
    }

    function load_data(){
        $message="";
        $data_input=[
            "main_side" => [
                "identite" => []
            ],
            "side_bar" => [
                     "apropos" => [], "competences" => [], "outils" => [], "langues" => [], "softskills" => []
            ],
            "main_bar" => [
                "experiences" => [], "formations" => []      
            ]
        ];

        $data_input["main_side"]["identite"]["titre"]="Contact";
        $data_input["side_bar"]["apropos"]["titre"]="À propos";
        $data_input["side_bar"]["competences"]["titre"]="Compétences";
        $data_input["side_bar"]["outils"]["titre"]="Logiciel";
        $data_input["side_bar"]["langues"]["titre"]="Langues";
        $data_input["side_bar"]["softskills"]["titre"]="Savoir-être";
        $data_input["main_bar"]["experiences"]["titre"]="Expérience Professionnelle";
        $data_input["main_bar"]["formations"]["titre"]="Formations, Certifications & Habilitations";

       
    
            $constante=get_constante();
            $parametrage_connection=$constante['config'];
            $config_smtp=$constante['config-smtp'];

            if (!empty($message)) {goto interaction_utilisateur;}
    

            if (isset($_SESSION["CV_poste"])) {$cv_choisi=$_SESSION["CV_poste"];} else {$cv_choisi=$_SESSION["liste_poste"][0];}

            switch($cv_choisi) {
                case "CV Technicien Essai":
                    $nom_poste="technicien essai";
                    $marge_interne="5px";
                    break;
                case "CV Développeur Ibmi":
                    $nom_poste="développeur ibmi";
                    $marge_interne="10px";
                    break;
                case "CV Développeur Fullstack":
                    $nom_poste="développeur fullstack";
                    $marge_interne="15px";
                    break;
                default:    
            }
    
        $data_recup=$data_input;
    
        read_cv_data($nom_poste,$parametrage_connection, $data_recup,$message);
        if (!empty($message)) {goto interaction_utilisateur;}

    
    
        $data_a_importer = [
            'identite' => $data_recup["main_side"]["identite"],
            'apropos' => $data_recup["side_bar"]["apropos"],
            'competences' => $data_recup["side_bar"]["competences"],
            'outils' => $data_recup["side_bar"]["outils"],
            'langues' => $data_recup["side_bar"]["langues"],
            'softskills' => $data_recup["side_bar"]["softskills"],
            'experiences' => $data_recup["main_bar"]["experiences"],
            'formations' =>  $data_recup["main_bar"]["formations"]
        ];

        interaction_utilisateur:
            if (empty($message)){
                $_SESSION["import_cv_data"]=$data_a_importer;
                $_SESSION["marge_interne"]=$marge_interne;
            } else {
                $_SESSION['erreur']=$message;
                header('Location: ../../index.php');
                exit;
            }
    }
?>