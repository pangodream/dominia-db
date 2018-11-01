<?php
/**
 * Created by Pangodream.
 * Date: 01/11/2018
 * Time: 13:06
 */

namespace DominiaDb;
use PhpSimpcli\CliParser;
use Dotenv\Dotenv;

class App
{
    /**
     * App constructor.
     */
    public function __construct(){
        $this->loadConfiguration();
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
    }

    /**
     * Tests database connection using current configuration
     */
    private function testDB(){
        $db = new Database();
        $ret = $db->connect($_ENV['DB_HOST'],
                            $_ENV['DB_PORT'],
                            $_ENV['DB_SCHEMA'],
                            $_ENV['DB_USER'],
                            $_ENV['DB_PASSWORD']);
        if($ret){
            echo "Database connection succeed.\n";
        }else{
            echo "Unable to connect to database. Review .env configuration options.\n";
        }
    }
    /**
     * Loads configuration option from .env file
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