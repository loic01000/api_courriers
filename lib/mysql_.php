<?php

define('DBHOST', 'mysql.xiong.fr');
define('DBPORT', '3306');
define('DBNAME', 'courriers');
define('DBUSER', 'courriers');
define('DBPASSWORD', 'courriers');

class DB
{

    private $connection;

    public $records;
    public $tables;
    public $fieldsTypes;

    public function __construct()
    {
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
    
    public function getTables()
    {
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NUM);

        $sql = 'SHOW TABLES;';

        $result = $this->connection->prepare($sql);
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
        $this->fieldsTypes = [];

        foreach ($this->tables as $table) 
        {
            $sql = "SHOW COLUMNS FROM $table;";
            $result = $this->connection->prepare($sql);
            $result->execute();

            $records = $result->fetchAll();

            foreach ($records as $record) 
            {
                if(!isset($this->fieldsTypes[$record[0]]))
                {
                    $this->fieldsTypes[$record[0]]['Field'] = $record[0];
                    $this->fieldsTypes[$record[0]]['Type'] = $record[1];
                    $this->fieldsTypes[$record[0]]['Null'] = $record[2];
                }
            }
        }
    }

    public function sql($sql, $values=[], $fetch = "NUM")
    {
        if ($fetch == "NUM") 
        {
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NUM);
        }
        else
        {
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        //---------------------------------------

        $valuesTypes=[];
        foreach ($values as $field => $value) 
        {
            $valueTypes[$field] = $this->fieldsTypes[$field];
            print("$field = $value\n");
        }

        //---------------------------------------

        $types= [];

        foreach($valueTypes as $k=>$row) 
        {
            $field = $row['Field'];
            $type = $row['Type'];
            $null = $row['Null'];

            if($null == "YES" && $values[$k] == "NULL")
            {
                $types[$k]= 0;
            }
            else
            {
                $refs=['timestamp'=>2,'datetime'=>2,'varchar'=>2,'text'=>2,'date'=>2,'year'=>2,'json'=>2,'int'=>1];

                foreach ($refs as $key =>$ref) 
                {
                    if(strpos($type,$key)!== false)
                    {
                        $types[$k] = $ref;
                        break;
                    }
                }
            }    
        }

        //---------------------------------------

        $PARAMS=[];
        array_push($PARAMS, PDO::PARAM_NULL);
        array_push($PARAMS, PDO::PARAM_INT);
        array_push($PARAMS, PDO::PARAM_STR);

        $result = $this->connection->prepare($sql);

        $i = 1;
        foreach($values as $k => $v)
        {
            $t = $types[$k];
            $result->bindValue($i,$v,$PARAMS[$t]);
            $i++;
        }
        $result->execute();

        $c = explode(' ',$sql)[0];
        if($c == 'SELECT')
        {
            $this->records = $result->fetchAll();
            return($this->records);
        }
        if($c == 'SHOW')
        {
             $this->records = $result->fetchAll();
             return($this->records);
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

    public function arrayToSql($array)
    {
        $a = [];
        foreach($array as $f=>$v)
        {
            array_push($a,"`$f`=?");
        }
        return(implode(', ',$a));
    }
   
}
