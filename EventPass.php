<?php

/*
* Author: Ford Wang
* Date: Dec 16, 2012
*
* EventPass is a class extends from the super class Pass
*/

require_once ("Pass.php");
require_once ("Database.php");
require_once ("DataInterface.php");
require_once ("JsonInterface.php");
require_once ("DebugLog.php");

class EventPass extends Pass
{

    /* Pass configuration is bundled in the class*/

    // relative path to the folder where your certificate and key files are
    public $keyPath;

    // relative path to pass source files
    private $sourcePath;

    //**********************************************************************
    // password for the key file
    public $keyPassword;

    // passTypeID registered on Apple Developer Portal
    private $passTypeID;
    private $cardId;
    private $firstname;
    private $lastname;
    private $useremail;
    //**********************************************************************

    /*
    * ctor name:     __construct
    * purpose:       autoconfigures the object and calls the super constructor 
    * parameter:     none
    */

    function __construct($cardId, $passTypeId, $FirstName, $LastName, $UserEmail)
    {
		DebugLog::WriteLogWithFormat("**************************Constructing EventPass*******************************\n");
        $this->cardId = $cardId;
        $this->firstname = $FirstName;
        $this->lastname = $LastName;
        $this->useremail = $UserEmail;
        $this->passTypeID = $passTypeId;
        /*
        __FILE__   - a special PHP const which holds the absolute path to the file
        currently being interpreted 
        dirname()  - returns the parent folder path when given a path to a file            
        realpath() - converts the path again to an absolute path
        */
        $tmpKeyPath = DataInterface::getPathByOrgIdAndCardId($passTypeId, $cardId);
        $tmpKeyPath = substr($tmpKeyPath, 9);
		DebugLog::WriteLogWithFormat("Eventpass keypath: $tmpKeyPath");
        // get the absolute path for $keyPath
        $this->keyPath = realpath(dirname(__file__) . "/../../public_html" . $tmpKeyPath);
        // get the absolute path for sourcePath
        $this->sourcePath = realpath($this->keyPath . "/source");

        $this->keyPassword = DataInterface::getKeyPasswordByOrgId($this->passTypeID);

        // call the super ctor and pass in the path to the pass source files
        parent::__construct($this->sourcePath);
        $debugContent = $this->content;
        DebugLog::WriteLogWithFormat("in construct of eventpass " . print_r($debugContent,
            ture));
    }

    /*
    * function name: writeAllFiles()
    * purpose:       call all the functions in the superclass
    * parameter:     none
    * return:        void
    */

    function writeAllFiles()
    {
        $this->writePassJSONFile();
        $this->writeRecursiveManifest();
        $pwd = $this->keyPassword;
        DebugLog::WriteLogWithFormat("in eventpass writeall files! what is the key password?:$pwd");
        $this->writeSignatureWithKeysPathAndPassword($this->keyPath, $this->keyPassword);
        $this->writePassBundle();
    }

    /*
    * function name: createPassWithUniqueSerialNr()
    * purpose:       create a new pass with unique serial number
    * parameter:     &$error - & stands for pointer
    * return:        $pass
    */

    function createPassWithUniqueSerialNr(&$error)
    {
        DebugLog::WriteLogWithFormat("Pass::createPassWithUniqueSerialNr(&error)");
        // fill in the details dynamically
        $this->content['passTypeIdentifier'] = $this->passTypeID;

        // sha1 check sum of rand number plus timestamp (40 chars)
        $this->content['authenticationToken'] = sha1(mt_rand() . microtime(true));

        // use timestamp plus a random number
        $this->content['barcode']['message'] = (string )round(microtime(true) * 100) .
            mt_rand(10000, 99999);

        // get database connection
        $db = Database::get();

        // catch any database exceptions
        try {
            // 1 prepare
            $statement = $db->prepare("INSERT INTO passes( 
	                                 AuthToken, LastUpdated, 
	                                 BarCode, Card,FirstName,LastName,UserEmail) 
	                                 VALUES(?,?,?,?,?,?,?)");

            // 2 execute, values will be mapped to the ? marks
            $statement->execute(array(
                $this->content['authenticationToken'],
                time(), //last update is now
                $this->content['barcode']['message'],
                $this->cardId,
                $this->firstname,
                $this->lastname,
                $this->useremail));
            DebugLog::WriteLogWithFormat("after insert, got rowCount:" . $statement->
                rowCount());
            // 3 check the rowCount to see if there was exactly one affected row
            if ($statement->rowCount() != 1)
                throw new PDOException("Could not create a pass in the database");

            $statement = $db->prepare("SELECT ID FROM passes WHERE AuthToken = ? AND BarCode = ? AND UserEmail = ?");
            $statement->execute(array(
                $this->content['authenticationToken'],
                $this->content['barcode']['message'],
                $this->useremail));
            if ($statement->rowCount() != 1) {
                DebugLog::WriteLogWithFormat("!!!Fatal ERROR!!! in EventPass createPassWithUniqueSerialNr");
            } else {
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                // use a timestamp to generate a unique serial number
                $this->content['serialNumber'] = $row["ID"];
                $jsonObject = new JsonInterface();
                $jsonObject->writeJsonIdAndAuthToken($this->cardId, $row["ID"], $this->content['authenticationToken']);
                DebugLog::WriteLogWithFormat("Got id back from database:" . $row["ID"]);
            }
        }
        // 4 save the exception error message in $error and return null
        catch (PDOException $e) {
            $error = $e->getMessage();
            DebugLog::WriteLogWithFormat("During insertion, got exception:" . $error);
            return null;
        }

        // save the pass json file in key folder
        $this->writePassJSONFileBackToKeyFolder();
        // generate and save the pass bundle(in work folder)
        $this->writeAllFiles();

    }

    function writePassJSONFileBackToKeyFolder()
    {
        DebugLog::WriteLogWithFormat("Pass::writePassJSONFileBackToKeyFolder()");
        // overwrite the old content with dynamic data
        file_put_contents($this->sourcePath . "/pass.json", json_encode($this->content));
    }

    /*
    * function name: createPassWithExistingSerialNr()
    * purpose:       create a new pass with unique serial number
    * parameter:     &$error - & stands for pointer
    * return:        $pass
    */
    function createPassWithExistingSerialNr(&$error)
    {

        // generate and save the pass bundle
        $this->writeAllFiles();

        // return the pass object
        return $this;
    }
    /*
    * function name: passWithSerialNr($serialNr)
    * purpose:       get a pass instance for a given serialNr
    * parameter:     $serialNr
    * return:        $pass
    */
    /* static function passWithSerialNr($serialNr, $pass)
    {
    $pass = new EventPass();

    //load the pass data from the database
    $db = Database::get();
    $statement = $db->prepare("SELECT * FROM passes WHERE ID = ?");
    $statement->execute(array($serialNr));

    $row = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
    //no pass with such serialNr found
    return null;
    }

    //save the pass bundle
    $pass->writeAllFiles();

    //return the pass instance
    return $pass;
    }*/
}

?>