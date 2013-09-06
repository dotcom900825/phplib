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
        // create a new Database instance
        try {
            // Create a new connection.
            // You'll probably want to replace hostname with localhost in the first parameter.
            // The PDO options we pass do the following:
            // \PDO::ATTR_ERRMODE enables exceptions for errors.  This is optional but can be handy.
            // \PDO::ATTR_PERSISTENT disables persistent connections, which can cause concurrency issues in certain cases.  See "Gotchas".
            // \PDO::MYSQL_ATTR_INIT_COMMAND alerts the connection that we'll be passing UTF-8 data.  This may not be required depending on your configuration, but it'll save you headaches down the road if you're trying to store Unicode strings in your database.  See "Gotchas".
            $conn = new \PDO(   'mysql:host='.configs::$productionHost
                .';dbname='.configs::$productionDbname,
                configs::$productionUser,
                configs::$productionPass
            );
            return $conn;
        }
        catch (PDOException $e) {
            mail(configs::$errorReportEmail, "PDO Exception", "Log message on " . date("Y-m-d H:i:s") . "\n" .
                $e->getMessage(), "From: " . (configs::$errorReportEmailFrom));
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


