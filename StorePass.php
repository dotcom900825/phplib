<?php

/*
* Author: Ford Wang
* Date: Dec 16, 2012
*
* StorePass is a class extends from the super class Pass
*/

/*
__FILE__   - a special PHP const which holds the absolute path to the file
currently being interpreted
dirname()  - returns the parent folder path when given a path to a file
realpath() - converts the path again to an absolute path
*/

require_once ("Pass.php");
require_once ("DataInterface.php");
require_once ("DebugLog.php");

class StorePass extends Pass
{

    /*
    * ctor name:     __construct
    * purpose:       autoconfigures the object and calls the super constructor
    * parameter:     $keyPath, $sourcePath, $keyPassword, $passTypeID, $cardID
    */
    function __construct($keyPath, $sourcePath, $keyPassword, $passTypeId, $cardId)
    {

        DebugLog::WriteLogWithFormat("StorePass::__construct(keyPath:$keyPath,
								sourcePath:$sourcePath,keyPassword:$keyPassword,
								passTypeID:$passTypeId,cardID:$cardId)");
        $this->keyPath = $keyPath;
        $this->sourcePath = $sourcePath;

        $this->keyPassword = $keyPassword;
        $this->passTypeID = $passTypeId;
        $this->cardID = $cardId;
    // get the absolute path for $keyPath
	  $this->keyPath = realpath($this->keyPath);
	     
	  // get the absolute path for sourcePath                 
	  $this->sourcePath = realpath($this->sourcePath);
        // call the super ctor and pass in the path to the pass source files
        parent::__construct($this->sourcePath);
    }

    /*
    * function name: writeAllFiles()
    * purpose:       call all the functions in the superclass
    * parameter:     none
    * return:        void
    */
    function writeAllFiles()
    {
        DebugLog::WriteLogWithFormat("StorePass::writeAllFiles()");
        $this->writePassJSONFile();
        $this->writeRecursiveManifest();
        $this->writeSignatureWithKeysPathAndPassword($this->keyPath, $this->keyPassword);
        $this->writePassBundle();
    }

    /*
    * function name: createPassWithUniqueSerialNr()
    * purpose:       create a new pass with unique serial number
    * parameter:     &$error - & stands for pointer
    * return:        $pass
    */
    function createPassWithUniqueSerialNr(&$error, $firstName, $lastName, $userEmail)
    {
        DebugLog::WriteLogWithFormat("StorePass::createPassWithUniqueSerialNr(
								error:$error,firstName:$firstName,
								lastName:$lastName,userEmail:$userEmail)");
        // fill in the details dynamically
        $this->content['passTypeIdentifier'] = $this->passTypeID;

        // sha1 check sum of rand number plus timestamp (40 chars)
        $this->content['authenticationToken'] = sha1(mt_rand() . microtime(true));

        // use timestamp plus a random number
        //!!!!!!!!!!!TODO: Barcode may need database support to promise uniqueness.
        $this->content['barcode']['message'] = (string )round(microtime(true) * 100) .
            mt_rand(10000, 99999);

        // fill in user name
        $this->content['storeCard']['auxiliaryFields'][0]['value'] = $firstName . " " .
            $lastName;

        $argv = array(
            $firstName,
            $lastName,
            $userEmail,
            $this->content['authenticationToken'],
            time(), //last update is now
            $this->content['barcode']['message'],
            $this->cardID);

        $result = $this->insertRecord($argv);
        if (null == $result) {
            DebugLog::WriteLogWithFormat("In StorePass::createPassWithUniqueSerialNr" .
                "Error, insertion failed!");
            DebugLog::WriteLogWithFormat($result);
            return null;
        }
        $this->content['serialNumber'] = $result;
        // generate and save the pass bundle
        $this->writeAllFiles();

        // return the pass object
        return $this;
    }

    /*
    * function name: insertRecord($argv)
    * purpose:       insert a record into Passes table
    * argument:      $argv
    *
    */
    function insertRecord($argv)
    {
        DebugLog::WriteLogWithFormat("StorePass::insertPass(argv:$argv)");
        return DataInterface::insertPass($argv);
    }


    /*
    * function name: passWithSerialNr($passId)
    * purpose:       get a pass instance for a given passId
    * parameter:     $passId
    * return:        $pass
    */
    function passWithSerialNr($passId)
    {
        DebugLog::WriteLogWithFormat("StorePass::passWithSerialNr(passId:$passId)");
        $row = DataInterface::getPassByPassId($passId);

        //fill in the pass content
        $this->content['serialNumber'] = $row['ID'];
        $this->content['authenticationToken'] = $row['AuthToken'];
        $this->content['barcode']['message'] = $row['BarCode'];
        $this->content['passTypeIdentifier'] = $this->passTypeID;
        $this->content['storeCard']['auxiliaryFields'][0]['value'] = $row['FirstName'] .
            " " . $row['LastName'];

        //save the pass bundle
        $this->writeAllFiles();

        //return the pass instance
        return $this;
    }
}

?>
