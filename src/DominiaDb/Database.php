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
    /**
     * @var \PDO
     */
    private $dbh = null;

    /**
     * Checks if a feed exists by the hash of the pdf file
     * @param $hash
     */
    public function getFeedIdByHash($hash){
        $feed_id = false;
        $sql = "select feed_id from dmn_feed where file_hash = :hash";
        $result = $this->execAndFetchAll($sql, array('hash'=>$hash));
        if(isset($result[0])){
            $feed_id = $result[0]['feed_id'];
        }else{
            $feed_id = $this->createFeed($hash);
        }
        return $feed_id;
    }

    /**
     * Creates a feed and returns it's id
     * @param $hash
     * @return null
     */
    public function createFeed($hash){
        $feedId = null;
        $sql = "select coalesce(max(feed_id), 0)+1 as feed_id from dmn_feed";
        $result = $this->execAndFetchAll($sql);
        $feedId = $result[0]['feed_id'];
        $sql = "insert into dmn_feed (feed_id, file_hash) values (:feedId, :fileHash)";
        $this->exec($sql, array('feedId'=>$feedId, 'fileHash'=>$hash));
        return $feedId;
    }

    /**
     * Returns true if the feed has been marked as processed
     * @param $feedId
     * @return bool
     */
    public function isFeedProcessed($feedId){
        $isProcessed = false;
        $sql = "select processed from dmn_feed where feed_id = :feedId";
        $result = $this->execAndFetchAll($sql, array('feedId'=>$feedId));
        if(isset($result[0])){
            if($result[0]['processed'] == '1'){
                $isProcessed = true;
            }
        }
        return $isProcessed;
    }

    /**
     * Marks feed as processed
     * @param $feedId
     */
    public function markFeedAsProcessed($feedId){
        $sql = "update dmn_feed set processed = 1 where feed_id = :feedId";
        $this->exec($sql, array('feedId'=>$feedId));
    }
    /**
     * Deletes all the records belonging to a feed
     * @param $feedId
     */
    public function deleteFeedRecords($feedId){
        $sql = "delete from dmn_record where feed_id = :feedId";
        $this->exec($sql, array('feedId'=>$feedId));
    }

    /**
     * Inserts a single record
     * @param $domainName
     * @param $regDate
     * @param $feedId
     */
    public function createRecord($domainName, $regDate, $feedId){
        $sql = "insert into dmn_record (domain_name, reg_date, feed_id) values (:domainName, :regDate, :feedId)";
        $this->exec($sql, array('domainName'=>$domainName, 'regDate'=>$regDate, 'feedId'=>$feedId));
    }

    /**
     * Insert a block of records to improve performance
     * @param $block
     * @param $feedId
     */
    public function dumpRecords($block, $feedId){
        $sql = "insert into dmn_record (domain_name, reg_date, feed_id) values ";
        $cnt = 0;
        foreach($block as $rec){
            $cnt++;
            $sql .= "('".$rec['domain']."', '".$rec['registerDate']."', ".$feedId.")";
            if($cnt < sizeof($block)){
                $sql .= ", ";
            }
        }
        $this->exec($sql);
    }
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
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute($params);
        }catch(\Exception $e){
            echo "DB error: ".$e->getMessage()."\n";
            if(strpos($e->getMessage(), "Integrity constraint violation") === false){
                die();
            }
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
            die("DB error: ".$e->getMessage()."\n");
            $ret = false;
        }
        return $ret;
    }
}