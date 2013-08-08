<?php

require_once ("DebugLog.php");
require_once ("config.php");

/*
* class name: Database
* purpose:    return a singleton instance of the database connectivity
* return:     $instance
*/
class Database extends PDO
{
     // get the singleton connectivity instance
    static function get()
    {
		DebugLog::WriteLogWithFormat("static Database::get()");
        // check if there is already a instance stored in $instance
        if (configs::$instance != null)
            return configs::$instance;

        // create a new Database instance
        try {
            configs::$instance = new Database("mysql:" . "host=" . configs::$host . ";" .
                "dbname=" . configs::$dbname, configs::$user, configs::$pass);
            return configs::$instance;

            // check for connection
            echo "Connected to database";
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return null;
        }
    }

    /*
    static function get($host, $dbname, $user, $pass)
    {
    // check if there is already a instance stored in $instance
    if (null != self::$instance && $host == self::$host && $dbname == self::$dbname) {
    //self::$host = $host;
    //self::$dbname = $dbname;
    self::$user = $user;
    self::$pass = $pass;
    return self::$instance;
    }

    // create a new Database instance
    try
    {
    self::$instance = new Database("mysql:host=".$host.
    ";dbname=".$dbname, $user, $pass);
    self::$host = $host;
    self::$dbname = $dbname;
    self::$user = $user;
    self::$pass = $pass;
    return self::$instance;
    echo "Connected to database"; // check for connection
    }

    // if error occur, print error message
    catch(PDOException $e)
    {
    echo $e->getMessage();
    return null;
    }
    }
    */
}
