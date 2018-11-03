# dominia-db
Database feeding with spanish domain registers

## Installation
1.- Clone repository to your apps folder

    git clone https://github.com/pangodream/dominia-db
2.- Enter app folder

    cd dominia-db
3.- Install dependencies

    composer install
    
4.- Create a new schema in your MySQL database server and a user with all permissions granted on that schema

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
    
## Downloading pdf documents
Dominia-db will retrieve the pdf index page from www.dominios.es and find the link to each relevant document. After checking if the document hasn't already been downloaded it will put it in the specified local folder (at .env file)
1.- Enter app folder

    cd dominia-db
    php src/dmndb.php -getpdfs
    
## Processing documents to feed the database
Each pdf that hasn't been completely processed before it's parsed to extract domain names and register date. These records are dumped to the database (table dmn_record).
You can configure how large will the record blocks be. A default value of 1K is already set in the .env file.

1.- Enter app folder

    cd dominia-db
    php src/dmndb.php -processall
    
## Creating a cron entry to keep the database updated
Usually you will need both options above to keep your database updated. Once a month a new pdf will be available to be downloaded, so if you set a cron job to run daily the new pdfs will be downloaded and processedas soon as they are published.
A tipical way to define the cron job could be:
````
* 5 * * * root cd /appfolder && /usr/bin/php src/dmndb.php -getpdfs -processall
````
Note that it's not necessary to invoke the application twice but you can do it in one single call for both options.

## Showing help
You can invoke the application with the option -h (or -help) to show the allowed execution options

1.- Enter app folder

    cd dominia-db
    php src/dmndb.php -help
    
````
php dmndb.php [options][arguments]

Options:
  -showconfig            Shows current configuration
  -testdb                Tests database connection using current configuration
  -createtables [force]  Creates database tables. If they already exist, override them with force argument
  -getpdfs               Downloads new pdfs from www.dominios.es
  -processall            Parses all pending files and dump records to database
  -h | -help             Shows this help
````
      
## Database structure
<table>
   <tr><th colspan="4">Table dmn_feed</th></tr>
   <tr><th>Column name</th><th>Type</th><th>Length</th><th>Null</th></tr>
   <tr style="text-align: left;"><td>feed_id</td><td>INT</td><td>11</td><td>No</td></tr>
   <tr style="text-align: left;"><td>file_hash</td><td>CHAR</td><td>32</td><td>No</td></tr>
   <tr style="text-align: left;"><td>processed</td><td>TINYINT</td><td>4</td><td>Yes</td></tr>
</table>  
<table>
   <tr><th colspan="4">Table dmn_record</th></tr>
   <tr><th>Column name</th><th>Type</th><th>Length</th><th>Null</th></tr>
   <tr style="text-align: left;"><td>domain_name</td><td>VARCHAR</td><td>230</td><td>No</td></tr>
   <tr style="text-align: left;"><td>reg_date</td><td>DATE</td><td>-</td><td>No</td></tr>
   <tr style="text-align: left;"><td>feed_id</td><td>INT</td><td>11</td><td>No</td></tr>
</table>           
             
        
