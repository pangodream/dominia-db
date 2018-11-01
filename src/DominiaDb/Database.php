<?php
/**
 * Created by Pangodream.
 * Date: 01/11/2018
 * Time: 13:10
 */
namespace DominiaDb;
use \PDO;
use DominiaDb\DDL;

class Database
{
    private $dbh = null;

    /**
     * Connects to database
     * Returns true if succeed, otherwise false
     * @param $host     Database hostname or address
     * @param $port     Listener port
     * @param $schema   Schema name
     * @param $user     User name
     * @param $password Password
     * @return bool
     */
    public function connect($host, $port, $schema, $user, $password){
        $ret = true;
        try {
            $this->dbh = new PDO("mysql:host=" . $host . ";port=" . $port . ";dbname=" . $schema, $user, $password);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch(\Exception $e){
            $ret = false;
        }
        return $ret;
    }

    /**
     * Creates database tables (drop them if exist)
     */
    public function createTables($schema){
        $ddl = new DDL();
        //Create Record table
        echo "Creating Record table...\n";
        $sql = $ddl->getRecordDDL();
        if(!$this->exec($sql)){
            die("Unable to create Record table. Exiting.");
        }
        //Create Feed table
        echo "Creating Feed table...\n";
        $sql = $ddl->getFeedDDL();
        if(!$this->exec($sql)){
            die("Unable to create Feed table. Exiting.");
        }
    }

    /**
     * Checks if the schema contains any table
     * @param $schema
     * @return bool
     */
    public function existTables($schema){
        $exist = false;
        $sql = "select count(*) as tables from information_schema.tables where table_schema = :schemaName";
        $ret = $this->execAndFetchAll($sql, array('schemaName'=>$schema));
        if($ret[0]['tables'] > 0){
            $exist = true;
        }
        return $exist;
    }

    /**
     * Executes a non select sql sentence
     * @param $sql
     * @param array $params
     * @return bool
     */
    private function exec($sql, $params=array()){
        $ret = true;
        try{
            $this->dbh->exec($sql);
        }catch(\Exception $e){
            $ret = false;
        }
        return $ret;
    }
    /**
     * Executes an sql sentence and return all the resulting rows
     * @param $sql
     * @param array $params
     * @return bool
     */
    private function execAndFetchAll($sql, $params=array()){
        try{
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute($params);
            $ret = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(\Exception $e){
            $ret = false;
        }
        return $ret;
    }
}