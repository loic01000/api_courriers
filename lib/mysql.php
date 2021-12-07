<?php
error_log('mysq.php');

define('DBHOST', 'mysql.xiong.fr');
define('DBPORT', '3306');
define('DBNAME', 'courriers');
define('DBUSER', 'courriers');
define('DBPASSWORD', 'courriers');

class DB
{

    private $connection;

    public $tables;
    public $records;
    public $affected;
    public $fieldsTypes;

    public function __construct()
    {
        // error_log('DB->__construct');

        try 
        {
            $this->connection = new PDO('mysql:host='.DBHOST."; port=".DBPORT."; dbname=".DBNAME.";", DBUSER, DBPASSWORD);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } 
        catch (PDOException $e) 
        {
            $msg = 'Erreur PDO...';
            die($msg);
        }
        $this->getTables();
        $this->getFieldsTypes();
    }

    private function getPDOType($type)
    {
        // error_log("DB->getPDOType(\"$type\");");
        
        $SQLTypes=['timestamp'=>2,'datetime'=>2,'varchar'=>2,'text'=>2,'date'=>2,'year'=>2,'json'=>2,'int'=>1];
        foreach ($SQLTypes as $SQLType=>$PDOType) 
        {
            if(strpos($type,$SQLType) !== false)
            {
                return($PDOType);
            }
        }
    }

    public function getTables()
    {
        // error_log("DB->getTables();");

        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NUM);

        $SQL = 'SHOW TABLES;';

        $result = $this->connection->prepare($SQL);
        $result->execute();

        $records = $result->fetchAll();
        $this->tables = [];
        foreach($records as $record)
        {
            array_push($this->tables, $record[0]);
        }
    }

    public function getFieldsTypes()
    {
        // error_log("DB->getFieldsTypes();");

        $this->fieldsTypes = [];

        foreach ($this->tables as $table) 
        {
            $PDOTypes=[];
            array_push($PDOTypes, PDO::PARAM_NULL);
            array_push($PDOTypes, PDO::PARAM_INT);
            array_push($PDOTypes, PDO::PARAM_STR);
            
            $SQL = "SHOW COLUMNS FROM $table;";
            $result = $this->connection->prepare($SQL);
            $result->execute();

            $records = $result->fetchAll();

            foreach ($records as $record) 
            {
                if(!isset($this->fieldsTypes[$record[0]]))
                {
                    $this->fieldsTypes[$record[0]]['Field']   = $record[0];
                    $this->fieldsTypes[$record[0]]['Type']    = $record[1];
                    $this->fieldsTypes[$record[0]]['Null']    = $record[2];
                    $this->fieldsTypes[$record[0]]['PDOType'] = $this->getPDOType($record[1]);
                }
            }
        }
    }

    public function SQL($SQL, $values=[], $fetch = "NUM")
    {
        // error_log("DB->SQL(\"$SQL\", ".json_encode($values).", \"$fetch\");");

        $tmp = ($fetch == "NUM") ? $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NUM) : $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        //---------------------------------------
        $valuesTypes=[];
        foreach ($values as $f=>$value) 
        {
            $valuesTypes[$f] = ($this->fieldsTypes[$f]['Null'] == 'YES' && $value=='NULL') ? PDO::PARAM_NULL : $this->fieldsTypes[$f]['PDOType'];
        }

        //---------------------------------------
        $result = $this->connection->prepare($SQL);

        $i = 1;
        foreach($values as $f => $v)
        {
            $t = $valuesTypes[$f];
            $result->bindValue($i,$v,$t);
            $i++;
        }
        $result->execute();

        $c = explode(' ',trim($SQL))[0];
        switch($c)
        {
            case 'SELECT' :
            case 'SHOW' :
            {
                $this->records = $result->fetchAll();
                return($this->records);
            }

            case 'INSERT' :
            case 'UPDATE' :
            case 'DELETE' :
            {
                $this->affected = $result->rowCount();
                return($this->affected);
            }
        }
    }

    public function fieldsToVars()
    {
        $a = [];
        foreach($this->records[0] as $f=>$v)
        {
            array_push($a,"\$$f=\"$v\";");
        }
        return(implode(' ',$a));
    }

    public function arrayToSQL($array,$separator = ', ')
    {
        $a = [];
        foreach($array as $f=>$v)
        {
            array_push($a,"`$f`=?");
        }
        return(implode($separator,$a));
    }
}
