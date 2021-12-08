<?php
header("Content-Type: application/json");

require_once('lib/mysql.php');

//print($_SERVER['REQUEST_METHOD']."\n");

$error = 0;

$URI = $_SERVER['REQUEST_URI'];
if (strpos($URI, '?') !== false) {
    $URI = explode('?', $URI)[0];
}
$URI = trim(explode('/api/', $URI)[1]);

if (($error = ($URI != '') ? $error : $error + 1) == 0); {
    $URI = explode('/', $URI);

    $context = (isset($URI[0])) ? $URI[0] : '';

    $error = ($context != '') ? $error : $error + 2;
}

if ($error == 0) {
    $db = new DB();

    $error2 = 0;

    // === CONNEXION ======================================

    // --- connexion - check ------------------------------
    if ($context == "connexion") {
        $cmd = (isset($URI[1])) ? $URI[1] : '';
        if (($error2 = ($cmd == 'verification') ? 0 : 1) == 0) {
            $_POST['identifiant'] = 'leng';
            $_POST['mot_de_passe'] = '1234';

            if (($error2 = (isset($_POST['identifiant']) && isset($_POST['mot_de_passe'])) ? 0 : 1) == 0) {
                $SQL = "SELECT `id`,`prenom`,`nom` FROM `utilisateurs` WHERE `identifiant`=? AND `mot_de_passe`=? LIMIT 0,1;";
                $record = $db->SQL($SQL, $_POST);
                if (($error2 = (count($record) == 1) ? $error2 : $error2 + 2) == 0) {
                    print(json_encode($record));
                }
            }
        }
    }

    // --- Utilisateur - desinscription ---------------------

    if($context == "utilisateur" && count($URI) == 3)
    {
        print('test');
        if ($URI[1] == "desinscription") 
            {
                if(($error2 = (ctype_digit($URI[2])) ? 0 : 1) == 0)
                {
                    $uid = $URI[2];
                    
                    $sql= "UPDATE `utilisateurs` SET `status`= \"désinscrit\" WHERE `id`=?;";
                    $return = $db->sql($sql,['id'=>$uid]);
                    print(json_encode(['affected'=>$return]));               
                }
            }
        }
    }

    // === COURRIERS ======================================

    // --- courriers - list -------------------------------
    if ($context == "courriers") {
        $cmd = (isset($URI[1])) ? $URI[1] : '';
        if ($cmd == 'liste') {
            $uid = (isset($URI[2])) ? $URI[2] + 0 : 0;
            if (($error2 = ($uid > 0) ? 0 : 1) == 0) {
                $SQL = "SELECT `id`,`date_modification`,`date_envoi`,`prenom`,`nom`,`denomination`,`code_postal`,`localite`,`status` FROM `list_courriers` WHERE `utilisateur_id`=? AND `status` <> \"Supprimé\" ORDER BY `date_modification` DESC, `date_envoi` DESC;";
                $records = $db->SQL($SQL, ['utilisateur_id' => $uid]);
                print(json_encode($records));
            }
        }
    }

    // --- courriers - select 1 record --------------------
    if($context == "courrier" && count($URI) == 2)
    {
        $uid = (isset($URI[1])) ? $URI[1] +0 : 0;
        if(ctype_digit($URI[1])) 
        {
            if(($error2 = ($uid > 0) ? 0 : 1) == 0)
            {
                $id = (isset($URI[2])) ? $URI[2] +0 : 0;
                if(($error2 = ($id > 0) ? $error2 : $error2 +2) == 0)
                {
                    $SQL = "SELECT `objet`, `offre`, `date_envoi`, `date_relance`, `paragraphe1`, `paragraphe2`, `paragraphe3`, `paragraphe4`, `nosref`, `vosref`, `annonce`, `destinataire_id`, `status` FROM courriers WHERE `utilisateur_id`=? AND `id`=? LIMIT 0,1;";  
                    $record = $db->SQL($SQL, ['utilisateur_id'=>$uid, 'id'=>$id]);
                    if(count($record) == 1)
                    {
                        print(json_encode($record));
                    }
                }
            }
        }  
    }

    // --- courriers - select multiple records ------------
    if($context == "courriers" && count($URI) == 3)
    {
        if(ctype_digit($URI[1]) && ctype_digit(str_replace("-", "", $URI[2])))
        {
            $uid = $URI[1];
            if(($error2 = ($uid > 0) ? 0 : 1) == 0)
            {
                $ids = trim($URI[2]);            
                if(($error2 = ($ids != '') ? $error2 : $error2 +1) == 0)
                {
                    $ids = explode('-',$ids);
                    if(($error2 = (count($ids) > 0) ? $error2 : $error2 +2) == 0)
                    {
                        $records = [];
                        foreach($ids as $id)
                        {
                            $SQL = "SELECT `objet`, `offre`, `date_envoi`, `date_relance`, `paragraphe1`, `paragraphe2`, `paragraphe3`, `paragraphe4`, `nosref`, `vosref`, `annonce`, `destinataire_id`, `status` FROM courriers WHERE `utilisateur_id`=? AND `id`=?;";
                            $record = $db->SQL($SQL, ['utilisateur_id'=>$uid,'id'=>$id]);
                            array_push($records,$record[0]);
                        }
                        print(json_encode($records));
                    }
                }
            }
        }
    }

    // --- inscription utilisateur------------

    if ($context == "utilisateur" && count($URI) == 2) 
    {
        error_log($context);
        if ($URI[1] == "inscription")
        {
            error_log($URI[1]);

            if (($error2 = (isset($_POST) ? 0 : 1) == 0)) {
                $SET = $db->arrayToSQL($_POST);
                $SQL = "INSERT INTO `utilisateurs` SET $SET;";
                error_log($SQL);
                $affected = $db->SQL($SQL, $_POST);
            }
            print(json_encode(['Nouvelle utilisateur' => $affected]));
        }
    }

    // --- modififcation utilisateur------------

    // if ($context == "utilisateur" && count($URI) == 4) 
    // {
    //     error_log($context);
    //     if ($URI[1] == "modifier")
    //     {
            
    //         if(($error2 = (ctype_digit($URI[2]) && ctype_digit($URI[3])) ? 0 : 1) == 0)
    //         {
    //             $uid = $URI[2];
    //             $did = $URI[3];
                
    //             $SET = $db->arrayToSQL($_POST);
    //             $sql = "UPDATE utilisateurs SET $SET WHERE id=? AND id=?;";

    //             $return = $db->sql($sql,['utilisateur_id'=>$uid, 'id'=>$did]);
    //         }
    //         print(json_encode(['affected'=>$return]));
    //     }
    // }

    // === DESTINATAIRES ======================================

        // --- destinataire - add ---------------------------------------------
    if ($context == "destinataire" && count($URI) == 3) {
        error_log($context);
        if ($URI[1] == 'ajouter') {
            error_log($URI[1]);
            if (($error2 = (ctype_digit($URI[2])) ? 0 : 1) == 0) {
                error_log($URI[2]);

                $uid = $URI[2];

                $_POST['utilisateur_id'] = $uid;
                /*
                                $_POST['titre'] = 'Monsieur' ;
                                $_POST['nom'] = 'LeBricoleur' ;
                                $_POST['prenom'] = 'Bob' ;
                                $_POST['fonction'] = 'chef' ;
                */
                
                $_POST['denomination'] = 'ok' ;
                /*
                                $_POST['adresse'] = 'chemin de la brousse' ;
                                $_POST['code_postale'] = '01000' ;
                                $_POST['localite'] = 'ici' ;
                                $_POST['telephone'] = '047404740474' ;
                                $_POST['email'] = 'bob@lebricoleur.com' ;
                                $_POST['commentaire'] = 'commentaire inutile' ;
                                $_POST['status'] = 'NULL' ;
                */
                print_r($_POST);

                if (($error2 = (isset($_POST)) ? $error2 : $error2 +2) == 0) {
                    $SET = $db->arrayToSQL($_POST);
                    $SQL = "INSERT INTO destinataires SET $SET;";
                    print_r($SQL."\n");
                    $affected = $db->SQL($SQL, $_POST);
                    print(json_encode(['affected'=>$affected]));
                }
            }
        }
    }

    // --- destinataires - list -------------------------------
    if($context == "destinataires")
    {
        $cmd = (isset($URI[1])) ? $URI[1] : '';
        if($cmd == 'liste')
        {
            $uid = (isset($URI[2])) ? $URI[2] +0 : 0;
            if(($error2 = ($uid > 0) ? 0 : 1) == 0)
            {
                $SQL = "SELECT `id`,`titre`,`prenom`,`nom`,`fonction`,`denomination`,`localite` FROM `destinataires` WHERE `utilisateur_id` = ? AND `status` IS NULL ;";

                $records = $db->SQL($SQL, ['utilisateur_id'=>$uid]);
                print(json_encode($records));
            }
        }
    // --- courrier - add ---------------------------------
    if($context == "courrier") 
    {
        $cmd = (isset($URI[1])) ? $URI[1] : '';
        if($cmd == 'ajouter')
        {
            $uid = (isset($URI[2])) ? $URI[2] +0 : 0;
            if(($error2 = ($uid > 0) ? 0 : 1) == 0)
            {  
                $_POST = [];
                $_POST["destinataire_id"] = 2112;           
                $_POST["objet"] = "objet";
                $_POST["paragraphe1"] = "paragraphe1";
                $_POST["paragraphe2"] = "paragraphe2";
                $_POST["paragraphe3"] = "paragraphe3";
                $_POST["paragraphe4"] = "paragraphe4";
                $_POST["status"] = "Brouillon";

                $date = date('Y-m-d');
                $_POST["date_creation"] = $date;
                $_POST["date_modification"] = $date;
                // $_POST["date_relance"] = ($_POST["date_relance"]=="") ? "NULL" : $_POST["date_relance"];
                // $_POST["date_envoi"] = ($_POST["date_envoi"]=="") ? "NULL" : $_POST["date_envoi"];
                $_POST["date_relance"] = "NULL";
                $_POST["date_envoi"] =  "NULL";

                $_POST["utilisateur_id"] = $uid;

                $SET = $db->arrayToSQL($_POST);
                $SET = str_replace("\"NULL\"","NULL",$SET);
                $SQL ="INSERT INTO `courriers` SET $SET , `utilisateur_id` = ?;";  
                print(json_encode($_POST));
                $affected = $db->SQL($SQL, $_POST);
                print(json_encode(['Affectés'=>$affected]));
            }
        }
    }

    // --- courrier - update ------------------------------
    if($context == "courrier") 
    {
        $cmd = (isset($URI[1])) ? $URI[1] : '';
        if($cmd == 'modifier')
        {
            $id = (isset($URI[2])) ? $URI[2] +0 : 0;
            if(($error2 = ($id > 0) ? 0 : 1) == 0)
            {
                // if($_SERVER['REQUEST_METHOD'] === "PUT")
                // {
                //     parse_str(file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH'] ), $_PUT);
                // }
                // else
                // {
                //     $_PUT=array();
                // }

                $_POST["date_envoi"] = ($_POST["date_envoi"]=="") ? "NULL" : $_POST["date_envoi"];
                $_POST["date_relance"] = ($_POST["date_relance"]=="") ? "NULL" : $_POST["date_relance"];
                $_POST['id'] = $id;
                $SET = $db->arrayToSQL($_POST);
                $SET = str_replace("\"NULL\"","NULL",$SET);
                $SQL = "UPDATE `courriers` SET $SET WHERE `id`=?;"; 
                $affected = $db->SQL($SQL, $_POST);
                print(json_encode(['affected'=>$affected]));
            }
        } 
    }

    // --- courrier - delete 1 ----------------------------
    if($context == "courrier") 
    {
        $cmd = (isset($URI[1])) ? $URI[1] : '';
        if($cmd == 'supprimer')
        {
            $id = (isset($URI[2])) ? $URI[2] +0 : 0;
            if(($error2 = ($id > 0) ? 0 : 1) == 0)
            {
                //error("ok");
                $SQL = "SELECT `titre`,`prenom`,`nom`,`fonction`,`denomination`,`adresse`, `code_postal`, `localite`, `telephone`, `email`, `commentaire` FROM destinataires WHERE `utilisateur_id`=? AND `id`=? LIMIT 0,1;";
                $record = $db->SQL($SQL, ['utilisateur_id' => $uid, 'id' => $id]);
                if (count($record) == 1) {
                    print(json_encode($record));
                }
            }
        }
    }

    // --- destinataires - select multiple records ------------
    if ($context == "destinataires") {
        $uid = (isset($URI[1])) ? $URI[1] + 0 : 0;
        if (($error2 = ($uid > 0) ? $error2 : $error2 + 1) == 0) {
            $ids = trim((isset($URI[2])) ? $URI[2] : '');
            if (($error2 = ($ids != '') ? $error2 : $error2 + 2) == 0) {
                $ids = explode('-', $ids);
                if (($error2 = (count($ids) > 0) ? $error2 : $error2 + 4) == 0) {
                    $records = [];
                    foreach ($ids as $id) {
                        $SQL = "SELECT `titre`,`prenom`,`nom`,`fonction`,`denomination`,`adresse`, `code_postal`, `localite`, `telephone`, `email`, `commentaire` FROM destinataires WHERE `id`=?";
                        $record = $db->SQL($SQL, ['id' => $id]);
                        array_push($records, $record[0]);
                $SQL = "DELETE FROM `courriers` WHERE `id`=?;"; 
                $affected = $db->SQL($SQL, ['id' => $id]);
                print(json_encode(['affected'=>$affected]));
            }
        }
    }
        
    // --- courriers - delete multiple records ------------
    if($context == "courriers")
    {
        $cmd = (isset($URI[1])) ? $URI[1] : '';
        if($cmd == 'supprimer')
        {
            if(ctype_digit(str_replace("-", "", $URI[2])))
            {
                $ids = trim((isset($URI[2])) ? $URI[2] : '');            
                if(($error2 = ($ids != '') ? 0 : 1) == 0)
                {
                    $ids = explode('-',$ids);
                    if(($error2 = (count($ids) > 0) ? $error2 : $error2 +2) == 0)
                    {
                        $affected = 0;
                        foreach($ids as $id)
                        {
                            $SQL = "DELETE FROM `courriers` WHERE `id`=?;"; 
                            $affected += $db->SQL($SQL, ['id' => $id]);
                        }
                        print(json_encode(['affected'=>$affected]));
                    }
                }
            }
        }
    }

    // --- destinataire - modifier --------------------
    if($context == "destinataire" && count($URI) == 4)
    {
        if ($URI[1] == "modifier") 
            {
                
                if(($error2 = (ctype_digit($URI[2]) && ctype_digit($URI[3])) ? 0 : 1) == 0)
                {
                    error_log("test=======================");
                    $uid = $URI[2];
                    $did = $URI[3];
                
                    $_POST["prenom"] = "denis";
                    // $_POST["nom"] = "de lavernette";
                    // $_POST["fonction"] = "chef";
                    // $_POST["denomination"] = "online";
                    // $_POST["localite"] = "online";h
                 

                    if (($error2 = (isset($_POST) ? 0 : 1) == 0)) 
                    {
                        $SET = $db->arrayToSQL($_POST);  
                        $_POST["utilisateur_id"] = $uid;
                        $_POST["id"] = $did;


                        $sql = "UPDATE `destinataires` SET $SET WHERE `utilisateur_id`=? AND `id`=?;";
                        print($sql);
                        $return = $db->sql($sql,$_POST);
                    }
                    print(json_encode(['affected'=>$return]));
                }    
            }
        }
    }

    // --- destinataires - supprimer - 1 ------------
    // --- inscription utilisateur------------

    if ($context == "utilisateur" && count($URI) == 2) 
    {
        error_log($context);
        if ($URI[1] == "inscription")
        {
            error_log($URI[1]);

            if (($error2 = (isset($_POST) ? 0 : 1) == 0)) 
            {
                $SET = $db->arrayToSQL($_POST);
                $SQL = "INSERT INTO `utilisateurs` SET $SET;";
                error_log($SQL);
                $affected = $db->SQL($SQL, $_POST);
            }
            print(json_encode(['Nouvelle utilisateur' => $affected]));
        }
    }

    // --- modififcation utilisateur------------

    // if ($context == "utilisateur" && count($URI) == 4) 
    // {
    //     error_log($context);
    //     if ($URI[1] == "modifier")
    //     {
            
    //         if(($error2 = (ctype_digit($URI[2]) && ctype_digit($URI[3])) ? 0 : 1) == 0)
    //         {
    //             $uid = $URI[2];
    //             $did = $URI[3];
                
    //             $SET = $db->arrayToSQL($_POST);
    //             $sql = "UPDATE utilisateurs SET $SET WHERE id=? AND id=?;";

    //             $return = $db->sql($sql,['utilisateur_id'=>$uid, 'id'=>$did]);
    //         }
    //         print(json_encode(['affected'=>$return]));
    //     }
    // }

    // === DESTINATAIRES ======================================

    // --- destinataire - select 1 record --------------------
    if ($context == "destinataire") 
    {
        $uid = (isset($URI[1])) ? $URI[1] + 0 : 0;
        if (($error2 = ($uid > 0) ? 0 : 1) == 0) 
        {
            $id = (isset($URI[2])) ? $URI[2] + 0 : 0;
            if (($error2 = ($id > 0) ? $error2 : $error2 + 2) == 0) 
            {
                $SQL = "SELECT `titre`,`prenom`,`nom`,`fonction`,`denomination`,`adresse`, `code_postal`, `localite`, `telephone`, `email`, `commentaire` FROM destinataires WHERE `utilisateur_id`=? AND `id`=? LIMIT 0,1;";
                $record = $db->SQL($SQL, ['utilisateur_id' => $uid, 'id' => $id]);
                if (count($record) == 1) 
                {
                    print(json_encode($record));
                }
            }
        }
    }

    // --- destinataires - select multiple records ------------
    if ($context == "destinataires") {
        $uid = (isset($URI[1])) ? $URI[1] + 0 : 0;
        if (($error2 = ($uid > 0) ? $error2 : $error2 + 1) == 0) {
            $ids = trim((isset($URI[2])) ? $URI[2] : '');
            if (($error2 = ($ids != '') ? $error2 : $error2 + 2) == 0) {
                $ids = explode('-', $ids);
                if (($error2 = (count($ids) > 0) ? $error2 : $error2 + 4) == 0) {
                    $records = [];
                  
                    foreach ($ids as $id) 
                    {
                        $SQL = "SELECT `titre`,`prenom`,`nom`,`fonction`,`denomination`,`adresse`, `code_postal`, `localite`, `telephone`, `email`, `commentaire` FROM destinataires WHERE `id`=?";
                        $record = $db->SQL($SQL, ['id' => $id]);
                        array_push($records, $record[0]);
                    }
                    print(json_encode($records));
                }
            }
        }
    }

    // --- destinataire - supprimer - 1 ------------

    if($context == "destinataire" && count($URI) == 4)
    {
        if ($URI[1] == "supprimer") 
        {
            
            if(($error2 = (ctype_digit($URI[2]) && ctype_digit($URI[3])) ? 0 : 1) == 0)
            {
                $uid = $URI[2];
                $did = $URI[3];

                $sql= "UPDATE `destinataires` SET `status`= \"Supprimé\" WHERE `utilisateur_id`=? AND `id`=?;";
                $return = $db->sql($sql,['utilisateur_id'=>$uid, 'id'=>$did]);
                print(json_encode(['affected'=>$return]));               
            }
        }
    }

    /*
    // --- courrier - add ---------------------------------
    if($_GET['context'] == "courrier" && $_GET['cmd'] == "add") 
    {
        $error2 = 0;
        $date = date('Y-m-d');
        $_POST["date_creation"] = $date;
        $_POST["date_modification"] = $date;
        $_POST["date_relance"] = ($_POST["date_relance"]=="") ? "NULL" : $_POST["date_relance"];
        $_POST["date_envoi"] = ($_POST["date_envoi"]=="") ? "NULL" : $_POST["date_envoi"];
        $SET = $db->arrayToSQL($_POST);
        $SET = str_replace("\"NULL\"","NULL",$SET);
        $SQL ="INSERT INTO `courriers` SET $SET , `utilisateur_id` = {$_SESSION['uid']};";  
        $affected = $db->SQL($SQL, $_POST);
        print(json_encode(['Affectés'=>$affected]));
    }

    // --- courrier - update ------------------------------
    if($_GET['context'] == "courrier" && $_GET['cmd'] == "update") 
    {

        if($_SERVER['REQUEST_METHOD'] === "PUT")
        {
            parse_str(file_get_contents('php://input', false , null, -1 , $_SERVER['CONTENT_LENGTH'] ), $_PUT);
        }
        else
        {
            $_PUT=array();
        }

        $error2 = 0;
        $_POST["date_envoi"] = ($_POST["date_envoi"]=="") ? "NULL" : $_POST["date_envoi"];
        $_POST["date_relance"] = ($_POST["date_relance"]=="") ? "NULL" : $_POST["date_relance"];
        $SET= $db->arrayToSQL($_POST);
        $SET = str_replace("\"NULL\"","NULL",$SET);
        $SQL = "UPDATE `courriers` SET $SET WHERE `id`=?;"; 
        $affected = $db->SQL($SQL, $_POST);
        print(json_encode(['affected'=>$affected]));
    }

    // --- courrier - delete 1 ----------------------------
    if($_GET['context'] == "courrier" && $_GET['cmd'] == "delete") 
    {
        $error2 = 0;
        $SQL = "DELETE FROM `courriers` WHERE `id`=?;"; 
        $affected = $db->SQL($SQL, $_POST);
        print(json_encode(['affected'=>$affected]));
    }

    // --- courriers - delete multiple records ------------
    if($_GET['context'] == "courrier" && $_GET['cmd'] == "delete") 
    {
        $error2 = 0;
        $error2 = (isset($_POST['ids'])) ? $error2 : $error2+1; 
        $WHERE = $db->arrayToSQL($_POST,' OR ');
        $SQL = "DELETE FROM `courriers` WHERE $WHERE;"; 
        $affected = $db->SQL($SQL, $_POST);
        print(json_encode(['affected'=>$affected]));
    }*/

    if ($error2 > 0) 
    {
        //print(json_encode(['error2'=>$error2]));
    }
} 
}
// else {
//     print(json_encode(['error' => $error]));
// }
