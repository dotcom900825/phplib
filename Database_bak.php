<?php

/*
 * class name: Database
 * purpose:    return a singleton instance of the database connectivity
 * return:     $instance
 */

class Database extends PDO
{
    //database connection details
    static private $host   = "localhost";
    static private $dbname = "admin_ucsd_cssa_membership_card";
    static private $user   = "admin_wangfute";
    static private $pass   = "wangfute95536";
    private static $instance = null;

    // get the singleton connectivity instance
    static function get()
    {
        // check if there is already a instance stored in $instance
        if (self::$instance!=null) return self::$instance;

        // create a new Database instance
        try
        {
            self::$instance = new Database("mysql:host=".self::$host.
                    ";dbname=".self::$dbname,
                self::$user, self::$pass);

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