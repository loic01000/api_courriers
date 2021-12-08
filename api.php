<?php
header("Content-Type: application/json");

require_once('lib/mysql.php');

//print($_SERVER['REQUEST_METHOD']."\n");

$error = 0;

$URI = $_SERVER['REQUEST_URI'];
if(strpos($URI,'?') !== false)
{
    $URI = explode('?',$URI)[0];
}
$URI = trim(explode('/api/',$URI)[1]);

if(($error = ($URI != '') ? $error : $error +1) == 0);
{
    $URI = explode('/',$URI);

    $context = (isset($URI[0])) ? $URI[0] : '';

    $error = ($context != '') ? $error : $error +2;
}

if($error == 0)
{
    $db = new DB();

    $error2 = 0;

    // === CONNEXION ======================================

    // --- connexion - check ------------------------------
    if($context == "connexion")
    {
        $cmd = (isset($URI[1])) ? $URI[1] : '';
        if(($error2 = ($cmd == 'verification') ? 0 : 1) == 0)
        {
            $_POST['identifiant'] = 'leng' ;
            $_POST['mot_de_passe'] = '1234' ;

            if(($error2 = (isset($_POST['identifiant']) && isset($_POST['mot_de_passe'])) ? 0 : 1) == 0)
            {  
                $SQL = "SELECT `id`,`prenom`,`nom` FROM `utilisateurs` WHERE `identifiant`=? AND `mot_de_passe`=? LIMIT 0,1;";
                $record = $db->SQL($SQL, $_POST);
                if(($error2 = (count($record) == 1) ? $error2 : $error2 +2) == 0)
                {
                    print(json_encode($record));
                }
            }
        }
    }

    // === COURRIERS ======================================

    // --- courriers - list -------------------------------
    if($context == "courriers")
    {
        $cmd = (isset($URI[1])) ? $URI[1] : '';
        if($cmd == 'liste')
        {
            $uid = (isset($URI[2])) ? $URI[2] +0 : 0;
            if(($error2 = ($uid > 0) ? 0 : 1) == 0)
            {
                $SQL = "SELECT `id`,`date_modification`,`date_envoi`,`prenom`,`nom`,`denomination`,`code_postal`,`localite`,`status` FROM `list_courriers` WHERE `utilisateur_id`=? AND `status` <> \"Supprimé\" ORDER BY `date_modification` DESC, `date_envoi` DESC;";
                $records = $db->SQL($SQL, ['utilisateur_id'=>$uid]);
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

    // === DESTINATAIRES ======================================

    // --- destinataire - select 1 record --------------------
    if($context == "destinataire")
    {
        $uid = (isset($URI[1])) ? $URI[1] +0 : 0;
        if(($error2 = ($uid > 0) ? 0 : 1) == 0)
        {
            $id = (isset($URI[2])) ? $URI[2] +0 : 0;
            if(($error2 = ($id > 0) ? $error2 : $error2 +2) == 0)
            {
                $SQL = "SELECT `titre`,`prenom`,`nom`,`fonction`,`denomination`,`adresse`, `code_postal`, `localite`, `telephone`, `email`, `commentaire` FROM destinataires WHERE `utilisateur_id`=? AND `id`=? LIMIT 0,1;";
                $record = $db->SQL($SQL, ['utilisateur_id'=>$uid, 'id'=>$id]);
                if(count($record) == 1)
                {
                    print(json_encode($record));
                }
            }
        }
    }

    // --- destinataires - select multiple records ------------
    if($context == "destinataires")
    {
        $uid = (isset($URI[1])) ? $URI[1] +0 : 0;
        if(($error2 = ($uid > 0) ? $error2 : $error2 +1) == 0)
        {
            $ids = trim((isset($URI[2])) ? $URI[2] : '');            
            if(($error2 = ($ids != '') ? $error2 : $error2 +2) == 0)
            {
                $ids = explode('-',$ids);
                if(($error2 = (count($ids) > 0) ? $error2 : $error2 +4) == 0)
                {
                    $records = [];
                    foreach($ids as $id)
                    {
                        $SQL = "SELECT `objet`, `offre`, `date_envoi`, `date_relance`, `paragraphe1`, `paragraphe2`, `paragraphe3`, `paragraphe4`, `nosref`, `vosref`, `annonce`, `destinataire_id`, `status` FROM courriers WHERE `id`=?;";
                        $SQL = "SELECT `titre`,`prenom`,`nom`,`fonction`,`denomination`,`adresse`, `code_postal`, `localite`, `telephone`, `email`, `commentaire` FROM destinataires WHERE `id`=?";
                        $record = $db->SQL($SQL, ['id'=>$id]);
                        array_push($records,$record[0]);
                    }
                    print(json_encode($records));
                }
            }
        }
    }

    if($error2 > 0)
    {
        //print(json_encode(['error2'=>$error2]));
    }    
}
else
{
    print(json_encode(['error'=>$error]));
}
