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
        ini_set('memory_limit',$_ENV['MEMORY_LIMIT']);

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
        if($cp->get('h')->found || $cp->get('help')->found){
            $this->showHelp();
        }
    }

    /**
     * Shows help
     */
    private function showHelp(){
        echo "\n";
        echo "php dmndb.php [options][arguments]\n";
        echo "\n";
        echo "Options:\n";
        echo "  -showconfig            Shows current configuration\n";
        echo "  -testdb                Tests database connection using current configuration\n";
        echo "  -createtables [force]  Creates database tables. If they already exist, override them with force argument\n";
        echo "  -getpdfs               Download new pdfs from www.dominios.es\n";
        echo "  -processall            Parse all pending files and dump records to database\n";
        echo "  -h | -help             Show this help\n";
        echo "\n";
    }
    /**
     * Downloads the pdf documents that are not locally stored (new or missing)
     */
    private function getPdfs(){
        $downloaded = $this->crawler->extract();
        echo "Downloaded ".$downloaded." new pdf files to local store.\n";
    }

    /**
     * Parse and dump all stored pdf files
     */
    private function processAll(){
        echo "Disabling indexes...\n";
        $this->db->disableIndexes('dmn_record');
        $docs = $this->crawler->listLocalFiles();
        foreach($docs as $doc) {
            $ret = $this->processFile($doc);
            //Pdf parser may exhaust memory when invoked repeated times
            gc_collect_cycles();
        }
        echo "Enabling indexes...\n";
        $this->db->enableIndexes('dmn_record');
        echo "Finished!\n";
    }

    /**
     * Parse and dump an specific document
     * Skips if the feed has been done before (marked as processed)
     * @param $doc
     * @throws \Exception
     */
    private function processFile($doc){
        $hash = substr($doc['fileName'], 0, 32);
        echo "Processing file ".$doc['fileName']."...\n";
        $feedId = $this->db->getFeedIdByHash($hash);
        if(!$this->db->isFeedProcessed($feedId)){
            $this->db->deleteFeedRecords($feedId);
            echo "   Parsing pdf...\n";
            $records = $this->parser->pdfToArray($doc['path'].'/'.$doc['fileName']);
            $cnt = 0;
            $block = array();
            echo "   Dumping blocks";
            foreach($records as $record){
                $cnt++;
                if($this->isValidDate($record['registerDate'])) {
                    $block[] = $record;
                    if (($cnt % $_ENV['BLOCK_SIZE']) == 0 || $cnt == sizeof($records)) {
                        echo ".";
                        $this->db->dumpRecords($block, $feedId);
                        $block = array();
                    }
                }
            }
            $this->db->markFeedAsProcessed($feedId);
            echo "\n";
            echo "   File processed. ".sizeof($records)." records.\n";
        }else{
            echo "   File skipped (already processed).\n";
        }
    }

    /**
     * Validates the date to avoid db errors
     * @param $date
     * @return bool
     */
    private function isValidDate($date){
        return checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4));
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
        echo "    Memory limit:       ".$_ENV['MEMORY_LIMIT']."\n";
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
        echo "    Block Size:         ".$_ENV['BLOCK_SIZE']."\n";
        echo "Files configuration\n";
        echo "    Docs. Home:         ".$_ENV['DOCS_HOME']."\n";
    }
}