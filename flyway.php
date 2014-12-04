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

    function checkTable($connection) {
        if (!$connection->query("SELECT * FROM flyway_schema IF EXISTS")) {
            if (!$connection->query($this->createStatement)) {
                error("Error creating database");
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    function executeFile($connection, $file) {
        $statement = file_get_contents($this->folderPath . "/" . $file);
        if (!$statement) {
            die('Error opening file');
        } else {
            if (mysqli_multi_query($connection, $statement)) {
                $success = "1";
            } else {
                $success = "0";
                trigger_error('Wrong SQLFile  Error: ' . $connection->errno . ' ' . $connection->error, E_USER_ERROR);
            }
        }

        return $success;
    }

    function isDone($connection, $version) {
        $statements = "SELECT * FROM flyway_schema WHERE version=?";
        $stmt = $connection->prepare($statements);

        $stmt->bind_param("i", $version);

        $stmt->execute() or die(' Error: ' . $connection->errno . ' ' . $connection->error);
    }

    public function migrate() {
        $connection = $this->databaseConnection;
        if ($this->checkTable($connection)) {
            $files = scandir($this->folderPath);
            print_r($files);
            sort($files);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $script = $file;
                    $splitedDot = explode(".", $file);
                    $type = $splitedDot[1];
                    $splited__ = explode("__", $splitedDot[0]);
                    $name = $splited__[1];
                    $version = substr($splited__[0], 1);
                    $intVersion = intval($version);

                    // echo " $script $type $name $version";
                    //$success = $this->executeFile($connection, $file);

                    $info = $connection->info;
                    if (!$info) {
                        $info = "no info";
                    }

                    $success = 0;

                    $infoStateMent = "INSERT INTO flyway_schema VALUES(?,?,?,?,?,?,?,?)";

                    $stmt = $connection->prepare($infoStateMent);

                    if (!$stmt) {
                        trigger_error('Wrong SQL: ' . $infoStateMent . ' Error: ' . $connection->errno . ' ' . $connection->error, E_USER_ERROR);
                    }

                    $stmt->bind_param("iisssssi", $intVersion, $intVersion, $version, $name, $type, $script, $info, $success);

                    $stmt->execute() or die(' Error: ' . $connection->errno . ' ' . $connection->error);
                    printf("%d Fila insertada.\n", $stmt->affected_rows);
                }
            }
        }
    }

}
