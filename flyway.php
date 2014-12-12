<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Flyway {

    private $databaseConnection = null;
    private $tableName = "flyway_schema";
    private $folderPath = "migrations";
    private $createStatement = "CREATE TABLE IF NOT EXISTS flyway_schema (
  `version_rank` int(11) NOT NULL,
  `installed_rank` int(11) NOT NULL,
  `version` varchar(50) NOT NULL,
  `description` varchar(200) NOT NULL,
  `type` varchar(20) NOT NULL,
  `script` varchar(1000) NOT NULL,
  `info` varchar(1000) NOT NULL,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY (`version`),
  KEY `schema_version_vr_idx` (`version_rank`),
  KEY `schema_version_ir_idx` (`installed_rank`),
  KEY `schema_version_s_idx` (`success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    function __construct($databaseConnection) {
        $this->databaseConnection = $databaseConnection;
    }

    function __contruct() {

    }

    function getDatabaseConnection() {
        return $this->databaseConnection;
    }

    function setDatabaseConnection($databaseConnection) {
        $this->databaseConnection = $databaseConnection;
    }

    function error($String) {
        echo $String;
    }
    
    function output($string){
        echo "<br>$string";
    }

    function checkTable($connection) {
        $query = $connection->query("SELECT * FROM flyway_schema IF EXISTS");
        $this->output("Checking flyway_schema table");
        if (!$query) {
            //$query->free_result();
            $this->output( "Flyway_schema doesn't exist");
            $queryCreateTable = $connection->query($this->createStatement);
             $this->output( "Creating flyway_schema table");
            if (!$queryCreateTable) {
                $this->error("Error creating database");
                $queryCreateTable->free_result();
                return false;
            } else {
                //$queryCreateTable->free_result();
                 $this->output("Flyway_shema table created");
                return true;
            }
        } else {
            $this->output( "Flyway_schema exist");
                    $query->free_result();
            return true;
        }
    }

    function executeFile($connection, $file) {
        $this->output( "Reading script $file");
        $statement = file_get_contents($this->folderPath . "/" . $file);
        if (!$statement) {
            die('Error opening file');
        } else {
            $this->output( "Executing Script..");  
        
            $query = $connection->multi_query($statement);
            if ($query) {
                 $this->output( "Script successful executed"); 
                do {
                    if ($res = $connection->store_result()) {
                        var_dump($res->fetch_all(MYSQLI_ASSOC));
                        $res->free();
                    }
                } while ($connection->more_results() && $connection->next_result());
                $success = 1;
            } else {
                $this->output( "Script unsuccessful executed"); 
                $success = 0;
                trigger_error('Wrong SQLFile  Error: ' . $connection->errno . ' ' . $connection->error, E_USER_ERROR);
            }
        }

        //$connection->close();
        return $success;
    }

    function last($connection) {
        $this->output( "Reading last script executed"); 
        $version = 0;
        $statements = "SELECT * FROM flyway_schema ORDER BY version_rank DESC LIMIT 1;";
        $result = $connection->query($statements);
        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                $version = $row['version'];
            }
        }
        $result->free_result();
       $this->output( "Last script executed: $version"); 
        return $version;
    }

    public function migrate() {
       $this->output( "Creating connection...");
        $connection = $this->databaseConnection;

        if ($this->checkTable($connection)) {
            $files = scandir($this->folderPath);
            //print_r($files);
            sort($files);
            $last = $this->last($connection);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $script = $file;
                    $splitedDot = explode(".", $file);
                    $type = $splitedDot[1];
                    $splited__ = explode("__", $splitedDot[0]);
                    $name = $splited__[1];
                    $version = substr($splited__[0], 1);
                    $intVersion = intval($version);

                    if ($intVersion > $last) {

                        // echo " $script $type $name $version";
                        $success = $this->executeFile($connection, $file);

                        $info = $connection->info;
                        if (!$info || $info = "") {
                            $info = "no info";
                        }
                        $this->output( "Inserting row schema version $intVersion output $success into flyway_schema"); 

                        //$success = 0;

                        $infoStateMent = "INSERT INTO flyway_schema VALUES(?,?,?,?,?,?,?,?)";


                        $stmt = $connection->prepare($infoStateMent);

                        if (!$stmt) {
                            trigger_error('Wrong SQL: ' . $infoStateMent . ' Error: ' . $connection->errno . ' ' . $connection->error, E_USER_ERROR);
                        }

                        $stmt->bind_param("iisssssi", $intVersion, $intVersion, $version, $name, $type, $script, $info, $success);

                        $stmt->execute() or die(' Error: ' . $connection->errno . ' ' . $connection->error);
                        $this->output("Affected rows flyway_schema: ". $stmt->affected_rows);

                        
                        $stmt->close();
                    }
                }
            }
        }
    }

}
