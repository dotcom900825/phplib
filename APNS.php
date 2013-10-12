<?php

/**
 * Created by JetBrains PhpStorm.
 * User: wangfanfu
 * Date: 13-4-13
 * Time: 上午11:53
 * To change this template use File | Settings | File Templates.
 */

require_once ("Database.php");
require_once ("DebugLog.php");
require_once ("config.php");

class APNS
{

    //apple push service endpoint
    private $apnsHost = 'ssl://gateway.push.apple.com:2195';

    function __construct($keyPath, $keyPassword)
    {
        DebugLog::WriteLogWithFormat("APNS::__construct(keyPath:$keyPath,keyPassword:$keyPassword)");
        $this->keyPath = $keyPath;
        $this->keyPassword = $keyPassword;
        $this->emailFrom = configs::$errorReportEmailFrom;
        $this->emailTo = configs::$errorReportEmail;
    }

    // update all passes under one single card
    function updateOneCard($username, $cardId)
    {
        DebugLog::WriteLogWithFormat("APNS::updateOneCard(username:$username,cardId:$cardId)");
        $db = Database::get();
        $statement = $db->prepare("SELECT PushToken, devices.ID, devices.device_type
                                    FROM devices,passes,DeviceVSPass
                                    WHERE   DeviceVSPass.Device = devices.ID
                                            AND DeviceVSPass.Pass = passes.ID
                                            AND passes.Card = ? ");
        $statement->execute(array($cardId));
        $this->sendPushesToResultSet($statement);
        return $statement->rowCount();
    }

    //update all registered devices
    function updateAllPasses()
    {
        DebugLog::WriteLogWithFormat("APNS::updateAllPasses()");
        $db = Database::get();
        $statement = $db->prepare("SELECT PushToken FROM devices");
        $statement->execute();
        $this->sendPushesToResultSet($statement);
        return $statement->rowCount();
    }


    //update all devices which have the given pass installed
    function updateForPassWithSerialNr($passId)
    {
        DebugLog::WriteLogWithFormat("APNS::updateForPassWithSerialNr(passId:$passId)");
        $db = Database::get();
        $statement = $db->prepare("SELECT PushToken FROM devices WHERE PassID=?");
        $statement->execute(array($passId));
        $this->sendPushesToResultSet($statement);
    }

    //send push notifications to all devices in the result set
    private function sendPushesToResultSet(PDOStatement $stmt)
    {
        DebugLog::WriteLogWithFormat("APNS::sendPushesToResultSet((PDOStatement)stmt)");
        $numOfDevices = $stmt->rowCount();
        //check if there are any results
        if ($numOfDevices == 0)
            return;
        $socketWriteCount = 0;
        while (true) {
            //open connection to apns
            $ctx = stream_context_create();
            stream_context_set_option($ctx, 'ssl', 'local_cert', $this->keyPath .
                "/passcertbundle.pem");
            stream_context_set_option($ctx, 'ssl', 'passphrase', $this->keyPassword);
            $fp = stream_socket_client($this->apnsHost, $err, $errstr, 15,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
            if (!$fp) { //error handling
                mail($this->emailTo, "APNS Log", "Log message on " . date("Y-m-d H:i:s") . "\n" .
                    print_r($err, true) . print_r($errstr, true), "From: " . ($this->emailFrom));
                return;
            }

            //create an empty push
            $emptyPush = json_encode(new ArrayObject());

            $dbugFile = dirname(__file__) . "/sent_devices.log";
            file_put_contents($dbugFile, "\n\n\n\n================One push=================\n", FILE_APPEND | LOCK_EX);
            $flag = false;
            //send it to all devices found
            for ($i = 0; $i <= 30; $i++) {
                if (!($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $flag = true;
                    break;
                }
                if ($row['device_type'] == "bad") {
                    continue;
                }
                $socketWriteCount++;
                file_put_contents($dbugFile, "$socketWriteCount\t\t" . $row['ID'] . " : " . $row['PushToken'] . "\n", FILE_APPEND | LOCK_EX);
                //write the push message to the apns socket connection
                $msg = chr(0) . //1
                    pack("n", 32) . pack('H*', $row['PushToken']) . //2
                    pack("n", strlen($emptyPush)) . //3
                    $emptyPush; //4
                fwrite($fp, $msg);
                //close the apns connection
            }
            fclose($fp);
            if($flag == true){
                break;
            }
        }
    }
}

?>
