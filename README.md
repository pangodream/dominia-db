# dominia-db
Database feeding with spanish domain registers

## Installation
1.- Clone repository to your apps folder

    git clone https://github.com/pangodream/dominia-db
2.- Enter app folder

    cd dominia-db
3.- Install dependencies

    composer install
    
4.- Create a new schema in your MySQL database server

5.- Rename (or copy) .env.example file to .env

6.- Edit .env file and replace Database configuration options with the values of your installation

## Initial testing
1.- Enter app folder

    cd dominia-db
    
2.- Show current config

    php src/dmndb.php -showconfig
    
3.- Check database connection

    php src/dmndb.php -testdb
    
If database connection succeed, then proceed to next step to create database tables

## Creating database tables
1.- Enter app folder

    cd dominia-db
    php src/dmndb.php -createtables
    
If the database is not empty, the tables creation will be aborted. If you want to override (drop) database objects you must specify the force modifier:

    php src/dmndb.php -createtables force
    
        
             
        
