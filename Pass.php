<?php

/*
* class name: Pass
* purpose:    handle dynamic generation of passes
*/

//Description: too many use of global variables
class Pass
{
    // path to the pass-building temporary folder
    private $workFolder = null;

    // unique pass ID
    private $ID = null;

    // pass.json content
    var $content = null;

    // path to the finished .pkpass file
    var $passBundleFile = null;

    /*
    * ctor name:     __construct
    * purpose:       ctor to make a new Pass object from a source folder
    * parameter:     $path (source folder)
    */
    //TODO: maybe this should go to a pass file operation class?
    function __construct($path)
    {
        DebugLog::WriteLogWithFormat("Pass::__construct(path:$path)");
        // check the source file contains pass.json
        assert(file_exists($path . "/pass.json"));

        // generate a unique ID for each temp folder, so the system can handle
        // multiple pass generations at the same time
        
        // TODO: keep this, this is good. By using tmp folders, a great amount 
        // of duplicate file can be saved 
        $this->ID = uniqid();

        // sys_get_temp_dir returns the path of the SYSTEM temp folder,
        // assign that to the workFolder with a unique ID
        $this->workFolder = sys_get_temp_dir() . "/" . $this->ID;

        // make our unique temp folders inside SYSTEM's temp folder
        mkdir($this->workFolder);

        // check whether the operation was successful
        assert(file_exists($this->workFolder));

        // call copy function to copy all the files
        $this->copySourceFolderFilesToWorkFolder($path);

        // call readPass function to load pass.json file
        $this->readPassFromJSONFile($this->workFolder . "/pass.json");
    }


    /*
    * function name: copySourceFolderFilesToWorkFolder
    * purpose:       copy the source file to a temp location
    * parameter:     $path
    * return:        void
    */
    //TODO: some file util class would be a better place for this
    private function copySourceFolderFilesToWorkFolder($path)
    {
        DebugLog::WriteLogWithFormat("Pass::
			copySourceFolderFilesToWorkFolder(path:$path)");
        /* recurse method to iterate thru all files in the source folder($path)
        and store files into a file array */
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST); // Top -> Bottom

        /* loop thru the files array and store each element's key into
        name(complete address of the file) and store the files into fileObject*/
        foreach ($files as $name => $fileObject) {
            // if address is a file && the file name does not start with "."
            if (is_file($name) && substr($fileObject->getFileName(), 0, 1) != ".") {
                /* copy them to the temp folder(workFolder/) (copy($source, $dest))
                str_replace will get rid of the complete address and
                only keep the file name */
                copy($name, $this->workFolder . "/" . str_replace($path . "/", "", $name));
            }
            // if the address is a directory instead of a file
            else
                if (is_dir($name) && substr($fileObject->getFileName(), 0, 1) != ".") {
                    // make a new directory in the temp folder
                    mkdir($this->workFolder . "/" . str_replace($path . "/", "", $name));
                }
        }
    }

    /*
    * function name: readPassFromJSONFile
    * purpose:       load a json file into the object
    * parameter:     $jsonFilePath
    * return:        void
    */
    //TODO: using return value is much better than global variabl
    function readPassFromJSONFile($jsonFilePath)
    {
        DebugLog::WriteLogWithFormat("Pass::readPassFromJSONFile(jsonFilePath:$jsonFilePath)");
        $jsonContent = json_decode(file_get_contents($jsonFilePath),true);
        /* read the json file and decode to an object， when true,
        objects will be converted into associative array(use string as keys)*/
        $this->content = $jsonContent;
    }

    /*
    * function name: writePassJSONFile
    * purpose:       export a json file from the object
    * parameter:     none
    * return:        void
    */
    //TODO: use parameter is much better than using global variabl
    function writePassJSONFile()
    {
        DebugLog::WriteLogWithFormat("Pass::writePassJSONFile()");
        // overwrite the old content with dynamic data
        file_put_contents($this->workFolder . "/pass.json", json_encode($this->content));
    }

    /*
    * function name: writeRecursiveManifest
    * purpose:       generate the manifest file
    * parameter:     none
    * return:        void
    */
    //Description: this method generate a json manifest that has hash of all the 
    //files
    function writeRecursiveManifest()
    {
        DebugLog::WriteLogWithFormat("Pass::writeRecursiveManifest()");
        //create empty manifest
        $manifest = new ArrayObject();

        //recurse over contents and build the manifest
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->
            workFolder), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $name => $fileObject) {
            if (is_file($name) && substr($fileObject->getFileName(), 0, 1) != ".") {

                $relativeName = str_replace($this->workFolder . "/", "", $name);

                // get the sha1 hash for each file
                $sha1 = sha1(file_get_contents($fileObject->getRealPath()));

                // store the sha1 hash into mainfest array
                $manifest[$relativeName] = $sha1;
            }
        }

        //write the manifest file
        file_put_contents($this->workFolder . "/manifest.json", json_encode($manifest));
    }

    /*
    * function name: writeSignatureWithKeysPathAndPassword
    * purpose:       generate the bundle signature
    * parameter:     $keyPath - path to the folder of pass cer. and key files
    *                $pass - password to the key file (Ucsd95536)
    * return:        void
    */
    function writeSignatureWithKeysPathAndPassword($keyPath, $pass)
    {
        DebugLog::WriteLogWithFormat("Pass::
			writeSignatureWithKeysPathAndPassword(keyPath:$keyPath,pass:$pass)");
        // make sure each file exists
        $keyPath = realpath($keyPath);

        if (!file_exists($keyPath . '/WWDR.pem'))
            die("Save the WWDR certificate as
	         $keyPath/WWDR.pem");

        if (!file_exists($keyPath . '/passcertificate.pem'))
            die("Save the pass certificate as
	         $keyPath/passcertificate.pem");

        if (!file_exists($keyPath . '/passkey.pem'))
            die("Save the pass certificate key as
	         $keyPath/passkey.pem");

        //TODO: investiage, can python do openssl signature? or can python call 
        //shell script?
        // use shell_exec to generate signature
        $output = shell_exec("openssl smime -binary -sign" . " -certfile '" . $keyPath .
            "/WWDR.pem'" . " -signer '" . $keyPath . "/passcertificate.pem'" . " -inkey '" .
            $keyPath . "/passkey.pem'" . " -in '" . $this->workFolder . "/manifest.json'" .
            " -out '" . $this->workFolder . "/signature'" . " -outform DER -passin pass:'$pass'");
    }

    /*
    * function name: writePassBundle
    * purpose:       create the zip bundle from the pass files
    * parameter:     none
    * return:        $passFile - path to the bundled .pkpass file
    */
    function writePassBundle()
    {
        DebugLog::WriteLogWithFormat("Pass::writePassBundle()");
        //1 generate the name for the .pkpass file
        $passFile = $this->workFolder . "/" . $this->ID . ".pkpass";

        //2 create Zip class instance
        $zip = new ZipArchive();
        $success = $zip->open($passFile, ZIPARCHIVE::OVERWRITE);
        if ($success !== true)
            die("Can't create file $passFile");

        //3 recurse over contents and build the list
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->
            workFolder), RecursiveIteratorIterator::SELF_FIRST);

        //4 add files to the archive
        foreach ($files as $name => $fileObject) {
            if (is_file($name) && substr($fileObject->getFileName(), 0, 1) != ".") {
                $relativeName = str_replace($this->workFolder . "/", "", $name);
                $zip->addFile($fileObject->getRealPath(), $relativeName);
            }
        }

        //5 close the zip file
        $zip->close();

        //6 save the .pkpass file path and return it too
        $this->passBundleFile = $passFile;
        return $passFile;
    }

    /*
    * function name: cleanup()
    * purpose:       delete all auto-generated files in the temp folder
    * parameter:     none
    * return:        void
    */
    function cleanup()
    {
        DebugLog::WriteLogWithFormat("Pass::cleanup()".$this->workFolder." end");
        //recurse over contents and delete files
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->
            workFolder), RecursiveIteratorIterator::CHILD_FIRST); // Bottom -> Top

        // similar mechanism as above
        foreach ($files as $name => $fileObject) {
            // if it is a file
            if (is_file($name)) {
                unlink($name); // delete the file
            }
            // if it is a directory
            else
                if (is_dir($name) && $this->isEmptyDir($files)) {
                    rmdir($name); // delete the directory
                }
        }

        rmdir($this->workFolder); // delete the temp workFolder
    }

    private function isEmptyDir($dir)
    {
        DebugLog::WriteLogWithFormat("Pass::isEmptyDir()");
        if (($files = @scandir($dir)) && count($files) <= 2) {
            return true;
        }
        return false;
    }

    /*
    * dtor name:     __destruct
    * purpose:       dtor to cleanup the temp folder after pass generation
    * parameter:     none
    */
    function __destruct()
    {
        DebugLog::WriteLogWithFormat("Pass::__destruct()");
        $this->cleanup();
    }

    /*
    * function name: outputPassBundleAsWebDownload()
    * purpose:       dump the generated pass to the browser
    * parameter:     none
    * return:        void
    */
    function outputPassBundleAsWebDownload()
    {
        DebugLog::WriteLogWithFormat("Pass::outputPassBundleAsWebDownload()");
//echo "test!";
        $debugContent = $this->content;
        DebugLog::WriteLogWithFormat("- debugContent:".print_r($debugContent,true));
        // inform safari this is a pass file, open it in Passbook
        header("Content-Type: application/vnd.apple.pkpass");

        // inform safari the default name for saving this file
        header("Content-Disposition: attachment; " . "filename=\"" . basename($this->
            passBundleFile."\";"));

        // inform safari the content will be in binary format
        header("Content-Transfer-Encoding: binary");

        // provide the size of the file to safari
        header("Content-Length: " . filesize($this->passBundleFile));
        //DebugLog::WriteLogWithFormat("- " . filesize($this->passBundleFile));
        // flush all files above to safari
        flush();

        // read the file and outputs it to the browser
        readfile($this->passBundleFile);
        $tmp = $this->passBundleFile;
        DebugLog::WriteLogWithFormat("- $tmp");
        
        exit();
    }

    function outputPassBundleAsEmailAttachmentAmazonSES($user_email, $subject_text,
        $message_text)
    {
        DebugLog::WriteLogWithFormat("Pass::outputPassBundleAsEmailAttachmentAmazonSES");
        require_once ("AmazonSES.php");

        $userEmail = $user_email;
        $subject = $subject_text;
        $message = $message_text;

        $success = sendFileToEmailWithTitleAndMessageAmazonSES($userEmail, $subject, $message,
            $this->passBundleFile);

        return;
    }
}

?>
