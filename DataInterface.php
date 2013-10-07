<?php

require_once ("Database.php");
require_once ("DebugLog.php");
require_once ("APNS.php");

class DataInterface
{
    static $pathByOrgIdDict = array();

    static $pathByOrgIdAndCardIdDict = array(
        "pass.com.ipassstore.ucsdcssa:1" =>
        "/../../../Client/UCSD_CSSA_Membership_Card/pass",
        "pass.com.ipassstore.dev:2" => "/../../../Client/TEST_NEW_SAMPLE/pass",
        "pass.com.ipassstore.dev:3" => "/../../../Client/TEST_NEW_SAMPLE_TWO/pass",
        "pass.com.ipassstore.dailyFreeAppGame:4" =>
        "/../../../Client/DAILY_APP_GAME_Card/pass",
        "pass.com.ipassstore.ucsduta:6" =>
        "/../../../Client/UCSD_UTA_Membership_Card/pass",
        "pass.com.ipassstore.tucssa:7" =>
        "/../../../Client/TU_CSSA_Membership_Card/pass",
        "pass.com.ipassstore.ucsdcssa:8" =>
        "/../../../Client/UCSD_CSSA_Membership_Card/pass",
        "pass.com.ipassstore.georgeAtTheCove:9" =>
        "/../../../Client/SD_Restaurant_Georges_At_The_Cove/pass",
        "pass.com.ipassstore.cssa:10" =>
        "/../../../Client/UChicago_CSSA_Membership_Card/pass",
        "pass.com.ipassstore.cssa:11" =>
        "/../../../Client/UTAustin_CSSA_Membership_Card/pass",
        "pass.com.ipassstore.cssa:12" =>
        "/../../../Client/UM_CSSA_Membership_Card/pass",
        "pass.com.ipassstore.ucsdTritonPass:13" =>
        "/../../../Client/UCSD_Triton_Pass/pass",
        "pass.com.ipassstore.sdsuAztecPass:14" =>
        "/../../../Client/SDSU_Aztec_Pass/pass");

    static $folderByCardId = array(
        1 => "UCSD_CSSA_Membership_Card",
        2 => "TEST_NEW_SAMPLE",
        3 => "TEST_NEW_SAMPLE_TWO",
        4 => "DAILY_APP_GAME_Card",
        6 => "UCSD_UTA_Membership_Card",
        7 => "TU_CSSA_Membership_Card",
        8 => "UCSD_CSSA_Membership_Card",
        9 => "SD_Restaurant_Georges_At_The_Cove",
        10 => "UChicago_CSSA_Membership_Card",
        11 => "UTAustin_CSSA_Membership_Card",
        12 => "UM_CSSA_Membership_Card",
		13 => "UCSD_Triton_Pass",
        14 => "SDSU_Aztec_Pass");

    static $cardKeyPasswordDict = array(
        "pass.com.ipassstore.ucsdcssa" => "ucsdcssa95536",
        "pass.com.ipassstore.ucsduta" => "ucsduta95536",
        "pass.com.ipassstore.dailyFreeAppGame" => "dailyFreeAppGame95536",
        "pass.com.ipassstore.tucssa" => "tucssa95536",
        "pass.com.ipassstore.georgeAtTheCove" => "georgeAtTheCove95536",
        "pass.com.ipassstore.cssa" => "cssa95536",
        "pass.com.ipassstore.dev" => "iPassStoreDev95536",
		"pass.com.ipassstore.ucsdTritonPass" => "ucsdTritonPass95536",
        "pass.com.ipassstore.sdsuAztecPass" => "sdsuAztecPass95536");

    static $orgIdByFolder = array(
        "TEST_NEW_SAMPLE" => "pass.com.ipassstore.dev",
        "TEST_NEW_SAMPLE_TWO" => "pass.com.ipassstore.dev",
        "UCSD_CSSA_Membership_Card" => "pass.com.ipassstore.ucsdcssa",
        "DAILY_APP_GAME_Card" => "pass.com.ipassstore.dailyFreeAppGame",
        "UCSD_UTA_Membership_Card" => "pass.com.ipassstore.ucsduta",
        "TU_CSSA_Membership_Card" => "pass.com.ipassstore.tucssa",
        "SD_Restaurant_Georges_At_The_Cove" => "pass.com.ipassstore.georgeAtTheCove",
        "UChicago_CSSA_Membership_Card" => "pass.com.ipassstore.cssa",
        "UTAustin_CSSA_Membership_Card" => "pass.com.ipassstore.cssa",
        "UM_CSSA_Membership_Card" => "pass.com.ipassstore.cssa",
		"UCSD_Triton_Pass" => "pass.com.ipassstore.ucsdTritonPass",
        "SDSU_Aztec_Pass" => "pass.com.ipassstore.sdsuAztecPass");

    public static function getFolderByCardId($cardId)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::getFolderByCardId(cardId:$cardId)");
        return DataInterface::$folderByCardId[$cardId];
    }

    /*
    * function name: getPathByOrgIdAndCardId($orgId, $cardId)
    * purpose:       get the file path of a certain card
    * parameter:     $orgId the pass.type.id, $cardId ID in Card
    * return:        String containing the path of the card
    */
    public static function getPathByOrgIdAndCardId($orgId, $cardId)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::getPathByOrgIdAndCardId(
										orgId:$orgId,cardId:$cardId)");
        return DataInterface::$pathByOrgIdAndCardIdDict[$orgId . ":" . "$cardId"];
    }

    public static function cardExist($orgId, $cardId)
    {
        return in_array($orgId . ":" . "$cardId", DataInterface::$pathByOrgIdAndCardIdDict);
    }

    /*
    * function name: ifRegistered($userEmail, $cardId)
    * purpose:       check whether an email is already registered
    * parameter:     $userEmail, $cardId
    * return:        true if registered, false if not
    */
    public static function ifRegistered($userEmail, $cardId)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::ifRegistered(
								userEmail:$userEmail,cardId:$cardId)");
        try {
            // get database connection
            $db = Database::get();

            // catch redundant data
            $statement = $db->prepare("SELECT * FROM passes WHERE UserEmail = ? AND Card = ?");

            $statement->execute(array($userEmail, $cardId));

            $row = $statement->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = $e->getMessage();
            DebugLog::WriteLogRaw("========in error========");
            DebugLog::WriteLogWithFormat("$error");
            echo "$error";

        }
        if ($statement->rowCount() == 0) {
            DebugLog::WriteLogRaw("In if row");
            //******************** Debug Block **************************
            //DebugLog::WriteLogWithFormat(print_r($row, true));
            //while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            //     DebugLog::WriteLogWithFormat(print_r($row, true));
            //}
            //***********************************************************
            return true;
        } else {
            DebugLog::WriteLogRaw("In else");
            //******************** Debug Block **************************
            //DebugLog::WriteLogWithFormat(print_r($row, true));
            // while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            //    DebugLog::WriteLogWithFormat(print_r($row, true));
            // }
            //***********************************************************
            return false;
        }
    }

    /*
    * function name: getCardIdByPassId($passId)
    * purpose:       Look up card id by pass id
    * parameter:     $passId
    * return:        int, card id
    */
    public static function getCardIdByPassId($passId)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::getCardIdByPassId(passId:$passId)");
        $db = Database::get();
        try {
            $statement = $db->prepare("
                SELECT Card 
                FROM passes
                WHERE ID = ?");
            $statement->execute(array($passId));
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
        } catch (PDOException $e) {
            $error = $e->getMessage();
            DebugLog::WriteLogWithFormat("LOG:ERROR In DataInterface::static getCardIdByPassId\r\n" .
                print_r($error, true));
            return null;
        }
        return $row['Card'];
    }

    /*
    * function name: getKeyPasswordByOrgId($orgId)
    * purpose:       Look up key password of each org
    * parameter:     $orgId
    * return:        string, the password
    */
    public static function getKeyPasswordByOrgId($orgId)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::getKeyPasswordByOrgId(
								orgId:$orgId)");
        DebugLog::WriteLogWithFormat("function called getKeyPasswordByOrgId");
        $debug = DataInterface::$cardKeyPasswordDict[$orgId];
        DebugLog::WriteLogWithFormat("$debug");
        return DataInterface::$cardKeyPasswordDict[$orgId];
    }

    /*
    * function name: getOrgIdByFolder($folder)
    * purpose:       Look up org id by foder name of that card
    * parameter:     $folder
    * return:        string, the org id
    */
    public static function getOrgIdByFolder($folder)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::getOrgIdByFolder(folder:$folder)");
        return DataInterface::$orgIdByFolder[$folder];
    }

    /*
    * function name: getPassByPassIdWithAuth($passId, $authToken)
    * purpose:       Verify if the auth token match with database
    *                and return the record matched with pass id
    * parameter:     $passId, $authToken
    * return:        array("ID"=><data>,"LastUpdated=><data>") null if failed
    * optimization:  can be optimized by avoid calling getPassByPassId
    */
    public static function getPassByPassIdWithAuth($passId, $authToken)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::getPassByPassIdWithAuth(
											passId:$passId,authToken:$authToken)");
        $row = DataInterface::getPassByPassId($passId);
        DebugLog::WriteLogWithFormat(print_r($row, true));
        //verification
        if ($row['AuthToken'] == $authToken) {
            return array("ID" => $row['ID'], "LastUpdated" => $row['LastUpdated']);
        } else {
            return null;
        }
    }

    /*
    * function name: getPassByPassId($passId)
    * purpose:       Look up pass record match with pass id
    * parameter:     $passId
    * return:        array() with all the rows of the record, null if failed
    */
    public static function getPassByPassId($passId)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::getPassByPassId(passId:$passId)");
        //load the pass data from the database
        $db = Database::get();
        $statement = $db->prepare("SELECT * FROM passes WHERE ID = ?");
        $statement->execute(array($passId));
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($statement->rowCount() != 1) {
            //no pass with such passId found
            return null;
        }
        return $row;
    }

    /*
    * function name: insertPass($argv)
    * purpose:       Insert a pass into database(passes)
    * parameter:     $argv(the information of the card)
    * return:        passId if success, null if PDOException
    */
    public static function insertPass($argv)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::insertPass(argv:" . print_r
        ($argv, true) . ")");

        // get database connection
        $db = Database::get();

        // catch any database exceptions
        try {
            // 1 prepare
            $statement = $db->prepare("INSERT INTO passes(FirstName, LastName, UserEmail,
                AuthToken, LastUpdated, BarCode, Card)
                VALUES(?,?,?,?,?,?,?)");
            // 2 execute, values will be mapped to the ? marks
            $statement->execute($argv);

            // 3 check the rowCount to see if there was exactly one affected row
            if ($statement->rowCount() != 1) {
                throw new PDOException("Could not create a pass in the database");
            }
            $statement = $db->prepare("SELECT ID from passes where UserEmail = ? AND Card = ?");
            $statement->execute(array($argv[2], $argv[6]));
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            return $row['ID'];
        } // 4 save the exception error message in $error and return null
        catch (PDOException $e) {
            $error = $e->getMessage();
            DebugLog::WriteLogWithFormat("LOG:ERROR In DataInterface::static insertPass\r\n" .
                print_r($error, true));
            return null;
        }
    }

    /*
    * function name: login($username, $password)
    * purpose:       given a username and a password, verify if the user
    *					information matches with database(Not encrypted yet)
    * parameter:     $username, $password
    * return:        true if verified, false if not verified, null if PDOException
    */
    public static function login($username, $password)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::login(
			username:$username,password:$password)");

        // get database connection
        $db = Database::get();

        // catch any database exceptions
        try {
            // 1 prepare
            $statement = $db->prepare("
                SELECT * FROM organizations
                WHERE UserName = ? and Password = ?");
            // 2 execute, values will be mapped to the ? marks

            $statement->execute(array($username, $password));

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            // 3 check the rowCount to see if there was exactly one affected row
            if ($statement->rowCount() != 1) {
                return false;
            } else {
                return true;
            }
        } // 4 save the exception error message in $error and return null
        catch (PDOException $e) {
            $error = $e->getMessage();
            DebugLog::WriteLogWithFormat("LOG:ERROR In DataInterface::static login\r\n");
            return null;
        }

    }

    /*
    * function name: getCardNamesAndIdByUsername($username)
    * purpose:       get the information(name and Id) of all the cards owned by a user
    * parameter:     $username
    * return:        null if no cards found; "Fatal error!" if rowCount less
    *					than 0; array(name=>id) if legal and found
    */
    public static function getCardNamesAndIdByUsername($username)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::
				getCardNamesAndIdByUsername(username:$username)");
        //load the pass data from the database
        $db = Database::get();
        $statement = $db->prepare("
			SELECT cards.Name,cards.ID 
			FROM cards, organizations 
			WHERE organizations.UserName = ? AND cards.Organization = organizations.ID");
        $statement->execute(array($username));
        $result = array();
        if ($statement->rowCount() == 0) {
            //no pass with such passId found
            return null;
        } else
            if ($statement->rowCount() > 0) {
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $name = $row['Name'];
                    $ID = $row['ID'];
                    $result["$name"] = "$ID";
                }
                return $result;
            } else {
                return "Fatal error!";
            }

    }

    /*
    * function name: getPassTypeIdByUsername($username)
    * purpose:       get the passTypeId of an org by its username
    * parameter:     $username
    * return:        false if affected row does not equal 1, (string)typeId
    *					if found and match
    */
    public static function getPassTypeIdByUsername($username)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::
					getPassTypeIdByUsername(username:$username)");
        $db = Database::get();
        $statement = $db->prepare("SELECT TypeID from organizations 
                                    WHERE UserName = ?");
        $statement->execute(array($username));
        if ($statement->rowCount() != 1) {
            DebugLog::WriteLogRaw("wrong way!\r\n");
            return false;
        } else {
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            return $row['TypeID'];
        }
    }

    /*
    * function name: pushToOneCard($username, $cardId)
    * purpose:       push updates to one single card
    * parameter:     $username, $cardId
    * return:        int updated devices
    */
    public static function pushToOneCard($username, $cardId)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::
				pushToOneCard(username:$username,cardId:$cardId)");

        // newly added for generic card
        DataInterface::updateTimeStamp($cardId);

        $orgTypeId = DataInterface::getPassTypeIdByUsername($username);
        DebugLog::WriteLogWithFormat("****TMP debug: ".print_r(DataInterface::$pathByOrgIdAndCardIdDict,true));
		DebugLog::WriteLogWithFormat("$orgTypeId" . ":" . "$cardId");
		DebugLog::WriteLogWithFormat("****TMP debug: ".DataInterface::$pathByOrgIdAndCardIdDict["$orgTypeId" . ":" . "$cardId"]);
        $keyPathRelative = DataInterface::$pathByOrgIdAndCardIdDict[$orgTypeId . ":" . "$cardId"];
        $pathTmp = explode('/', $keyPathRelative);
        $pathFolder = $pathTmp[5];
        $keyPath = dirname(__file__) . "/../../public_html/Client/$pathFolder/pass";
        $keyPassword = DataInterface::getKeyPasswordByOrgId($orgTypeId);
        DebugLog::WriteLogRaw("$orgTypeId,$keyPath,$keyPassword\r\n");
        $apns = new APNS($keyPath, $keyPassword);
        return $apns->updateOneCard($username, $cardId);
    }

    public static function updateTimeStamp($cardId)
    {
        try {
            // get database connection
            $db = Database::get();

            $currentTime = time();

            // catch redundant data
            $statement = $db->prepare("UPDATE passes SET LastUpdated = ? WHERE Card = ?");

            $statement->execute(array($currentTime, $cardId));

            $row = $statement->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = $e->getMessage();
            echo "$error";
        }
    }

    public static function getCardType($cardId)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::
				getCardType(cardId:$cardId)");
        $db = Database::get();
        $statement = $db->prepare("SELECT Type FROM cards WHERE ID = ?");
        $statement->execute(array($cardId));
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row['Type'];
    }

    public static function getCardDistributionType($cardId)
    {
        DebugLog::WriteLogWithFormat("static DataInterface::
				getCardType(cardId:$cardId)");
        $db = Database::get();
        $statement = $db->prepare("SELECT Distribution FROM cards WHERE ID = ?");
        $statement->execute(array($cardId));
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row['Distribution'];
    }
}
