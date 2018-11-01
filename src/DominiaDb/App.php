<?php
/**
 * Created by Pangodream.
 * Date: 01/11/2018
 * Time: 13:06
 */

namespace DominiaDb;
use Dominia\DocParser;
use PhpSimpcli\CliParser;
use Dotenv\Dotenv;
use Dominia\Crawler;

class App
{
    /**
     * @var \DominiaDb\Database;
     */
    private $db = null;
    /**
     * @var Dominia\Crawler
     */
    private $crawler = null;
    /**
     * @var Dominia\DocParser
     */
    private $parser = null;

    /**
     * App constructor.
     */
    public function __construct(){
        $this->loadConfiguration();

        $this->dbConnect();

        $this->crawler = new Crawler();
        $this->crawler->setDocsHome($_ENV['DOCS_HOME']);

        $this->parser = new DocParser();

        $this->evalInvokingOptions();
    }

    /**
     * Evaluate command line arguments and options
     */
    private function evalInvokingOptions(){
        $cp = new CliParser();
        if($cp->get('showconfig')->found){
            $this->showConfig();
        }
        if($cp->get('testdb')->found){
            $this->testDB();
        }
        if($cp->get('createtables')->found){
            $this->createTables($cp->get('createtables')->value);
        }
        if($cp->get('getpdfs')->found){
            $this->getPdfs();
        }
        if($cp->get('processall')->found){
            $this->processAll();
        }
    }

    /**
     * Downloads the pdf documents that are not locally stored (new or missing)
     */
    private function getPdfs(){
        $downloaded = $this->crawler->extract();
        echo "Downloaded ".$downloaded." new pdf files to local store.\n";
    }
    private function processAll(){
        $docs = $this->crawler->listLocalFiles();
        foreach($docs as $doc) {
            $hash = substr($doc['fileName'], 0, 32);
            var_dump($this->db->existsFeed($hash));die();
        }
    }
    /**
     * Initializes database tables
     * When $force contains the value 'force' it drops tables when exist
     * @param $force
     */
    private function createTables($force){
        if($this->dbConnect()){
            if($this->db->existTables($_ENV['DB_SCHEMA']) && $force != 'force'){
                echo "The database schema is not empty.\n"
                      ."You can override tables information specifying the 'force' modifier after 'createtables' option (-createtables force)\n";
            }else{
                $this->db->createTables($_ENV['DB_SCHEMA']);
                echo "Tables created succesfully.\n";
            }
        }else{
            echo "Unable to connect to database. Review .env configuration options.\n";
        }
    }
    /**
     * Tests database connection using current configuration
     */
    private function testDB(){
        echo "Testing database connection...\n";
        if($this->dbConnect()){
            echo "Database connection succeed.\n";
        }else{
            echo "Unable to connect to database. Review .env configuration options.\n";
        }
    }

    /**
     *  Connects to database
     * @return bool
     */
    private function dbConnect(){
        if($this->db == null) {
            $this->db = new Database();
            $ret = $this->db->connect($_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_SCHEMA'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASSWORD']);
        }else {
            $ret = true;
        }
        return $ret;
    }
    /**
     * Loads configuration options from .env file
     */
    private function loadConfiguration(){
        //Check if configuration file exists
        if(!file_exists(__DIR__.'/../../.env')){
            echo "Configuration file .env has not been found.\n";
            echo "You should place .env file (or edit and rename .env.example) in the folder\n";
            echo realpath(__DIR__.'/../../')."\n";
            die();
        }else{
            $dotenv = new Dotenv(realpath(__DIR__.'/../../'), '.env');
            $dotenv->load();
        }
    }
    /**
     * Shows current configuration
     */
    private function showConfig(){
        echo "Global\n";
        echo "    Show Credentials:   ".$_ENV['SHOW_CREDENTIALS']."\n";
        echo "Database configuration\n";
        echo "    Host:               ".$_ENV['DB_HOST']."\n";
        echo "    Port:               ".$_ENV['DB_PORT']."\n";
        echo "    Schema:             ".$_ENV['DB_SCHEMA']."\n";
        if($_ENV['SHOW_CREDENTIALS'] == 'true'){
            echo "    User:               ".$_ENV['DB_USER']."\n";
            echo "    Password:           ".$_ENV['DB_PASSWORD']."\n";
        }else{
            echo "    User:               xxxxxxxx\n";
            echo "    Password:           xxxxxxxx\n";
        }
        echo "Files configuration\n";
        echo "    Docs. Home:         ".$_ENV['DOCS_HOME']."\n";
    }
}