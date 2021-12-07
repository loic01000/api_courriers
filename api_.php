<?php
header("Content-Type: application/json");

require_once('lib/mysql.php');

$error = 0;
$error = (isset($_GET['contexte'])) ? $error : $error+1;
$error = (isset($_GET['cmd'])) ? $error : $error+2;

if ($error == 0)
{
    $db = new DB();

    // === CONNEXION ======================================

    // --- connexion - check ------------------------------
    if ($_GET['context'] == "connection" && $_GET['cmd'] == "select") 
    {
        $error2 = 0;
        $error2 = (isset($_POST['identifiant']) && isset($_POST['mot_de_passe'])) ? $error : $error+1;
        if ($error2 == 0)
        {  
            $SQL = "SELECT `id`,`prenom`,`nom` FROM `utilisateurs` WHERE `identifiant`=? AND `mot_de_passe`=? LIMIT 0,1;";
            $record = $db->SQL($SQL, $_POST);
            print(json_encode($record));
        }
    }

    // === COURRIERS ======================================

    // --- courriers - list -------------------------------
    if ($_GET['context'] == "courriers" && $_GET['cmd'] == "list") 
    {
        $error2 = 0;
        $error2 = (isset($_SESSION["uid"])) ? $error2 : $error2+1;
        $SQL = "SELECT `id`,`date_modification`,`date_envoi`,`prenom`,`nom`,`denomination`,`code_postal`,`localite`,`status` FROM `list_courriers` WHERE `utilisateur_id`={$_SESSION["uid"]} AND `status` <> \"Supprimé\" ORDER BY `date_modification` DESC, `date_envoi` DESC;";
        $records = $db->SQL($SQL);
        print(json_encode($records));
    }

    // --- courriers - select 1 record --------------------
    if ($_GET['context'] == "courrier" && $_GET['cmd'] == "select") 
    {
        $error2 = 0;
        $error2 = (isset($_POST['id'])) ? $error2 : $error2+1; 
        $SQL = "SELECT `objet`, `offre`, `date_envoi`, `date_relance`, `paragraphe1`, `paragraphe2`, `paragraphe3`, `paragraphe4`, `nosref`, `vosref`, `annonce`, `destinataire_id`, `status` FROM courriers WHERE `id`=? LIMIT 0,1;";  
        $record = $db->SQL($SQL, $_POST['id']);
        print(json_encode($record));
    }

    // --- courriers - select multiple records ------------
    if ($_GET['context'] == "courriers" && $_GET['cmd'] == "select")
    {
        $error2 = 0;
        $error2 = (isset($_POST['ids'])) ? $error2 : $error2+1; 
        $WHERE = $db->arrayToSQL($_POST,' OR ');
        $SQL = "SELECT `objet`, `offre`, `date_envoi`, `date_relance`, `paragraphe1`, `paragraphe2`, `paragraphe3`, `paragraphe4`, `nosref`, `vosref`, `annonce`, `destinataire_id`, `status` FROM courriers WHERE $WHERE;";  
        $records = $db->SQL($SQL, $_POST['ids']);
        print(json_encode($records));
    }
    
    // --- courrier - add ---------------------------------
    if ($_GET['context'] == "courrier" && $_GET['cmd'] == "add") 
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
    if ($_GET['contexte'] == "courrier" && $_GET['cmd'] == "update") 
    {
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
    if ($_GET['contexte'] == "courrier" && $_GET['cmd'] == "delete") 
    {
        $error2 = 0;
        $SQL = "DELETE FROM `courriers` WHERE `id`=?;"; 
        $affected = $db->SQL($SQL, $_POST);
        print(json_encode(['affected'=>$affected]));
    }

    // --- courriers - delete multiple records ------------
    if ($_GET['contexte'] == "courrier" && $_GET['cmd'] == "delete") 
    {
        $error2 = 0;
        $error2 = (isset($_POST['ids'])) ? $error2 : $error2+1; 
        $WHERE = $db->arrayToSQL($_POST,' OR ');
        $SQL = "DELETE FROM `courriers` WHERE $WHERE;"; 
        $affected = $db->SQL($SQL, $_POST);
        print(json_encode(['affected'=>$affected]));
    }

    if($error2 > 0)
    {
        print(json_encode(['error2'=>$error2]));
    }    
}
else
{
    print(json_encode(['error'=>$error]));
}
