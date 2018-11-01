<?php
/**
 * Created by Pangodream.
 * Date: 01/11/2018
 * Time: 13:10
 */
namespace DominiaDb;
use \PDO;

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
}