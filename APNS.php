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

    // FUNCTION to check if there is an error response from Apple
    // Returns TRUE if there was and FALSE if there was not
    function checkAppleErrorResponse($fp)
    {
        //byte1=always 8, byte2=StatusCode, bytes3,4,5,6=identifier(rowID).
        // Should return nothing if OK.

        //NOTE: Make sure you set stream_set_blocking($fp, 0) or else fread will pause your script and wait
        // forever when there is no response to be sent.
        $apple_error_response = fread($fp, 6);
        if ($apple_error_response) {
            // unpack the error response (first byte 'command" should always be 8)
            $error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_error_response);
            if ($error_response['status_code'] == '0') {
                $error_response['status_code'] = '0-No errors encountered';
            } else if ($error_response['status_code'] == '1') {
                $error_response['status_code'] = '1-Processing error';
            } else if ($error_response['status_code'] == '2') {
                $error_response['status_code'] = '2-Missing device token';
            } else if ($error_response['status_code'] == '3') {
                $error_response['status_code'] = '3-Missing topic';
            } else if ($error_response['status_code'] == '4') {
                $error_response['status_code'] = '4-Missing payload';
            } else if ($error_response['status_code'] == '5') {
                $error_response['status_code'] = '5-Invalid token size';
            } else if ($error_response['status_code'] == '6') {
                $error_response['status_code'] = '6-Invalid topic size';
            } else if ($error_response['status_code'] == '7') {
                $error_response['status_code'] = '7-Invalid payload size';
            } else if ($error_response['status_code'] == '8') {
                $error_response['status_code'] = '8-Invalid token';
            } else if ($error_response['status_code'] == '255') {
                $error_response['status_code'] = '255-None (unknown)';
            } else {
                $error_response['status_code'] = $error_response['status_code'] . '-Not listed';
            }
            $dbugFile = dirname(__file__) . "/sent_devices.log.error";
            file_put_contents($dbugFile,
                "ERROR Response Command:" . $error_response['command'] .
                    "\nIdentifier:" . $error_response['identifier'] .
                    "\nStatus:" . $error_response['status_code'] . "\n"
                , FILE_APPEND | LOCK_EX);
            return true;
        }
        return false;
    }

    private function establishSSLConnection()
    {
        //open connection to apns
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $this->keyPath .
            "/passcertbundle.pem");
        stream_context_set_option($ctx, 'ssl', 'passphrase', $this->keyPassword);
        $fp = stream_socket_client($this->apnsHost, $err, $errstr, 60,
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        if (!$fp) { //error handling
            mail($this->emailTo, "APNS Log", "Log message on " . date("Y-m-d H:i:s") . "\n" .
                print_r($err, true) . print_r($errstr, true), "From: " . ($this->emailFrom));
            return null;
        } else {
            stream_set_blocking($fp, 0);
            return $fp;
        }
    }

    //send push notifications to all devices in the result set
    private function sendPushesToResultSet(PDOStatement $stmt)
    {
        DebugLog::WriteLogWithFormat("APNS::sendPushesToResultSet((PDOStatement)stmt)");
        $numOfDevices = $stmt->rowCount();
        //check if there are any results
        if ($numOfDevices == 0)
            return;

        $date = date('m/d/Y h:i:s a', time());
        $dbugFile = dirname(__file__) . "/sent_devices.log";
        file_put_contents($dbugFile,
            "\n\n\n\n================One push@$date=================\n", FILE_APPEND | LOCK_EX);

        $roundCounter = 0;
        $roundSize = 30;
        $batchRetryTimes = 1;
        for ($i = 0; $i < ceil($numOfDevices / $roundSize); $i++) {
            $devicesToPush = array();
            for ($k = 0; $k < $roundSize; $k++) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    break;
                }
                $devicesToPush[$k] = $row;
            }

            file_put_contents($dbugFile, "--------- Round $roundCounter:\n", FILE_APPEND | LOCK_EX);
            $roundCounter++;

            //send it to all devices found
            $batchStatus = true;
            for ($t = 0; $t < $batchRetryTimes; $t++) {
                $fp = $this->establishSSLConnection();
                $batchStatus = true;
                //create an empty push
                $emptyPush = json_encode(new ArrayObject());
                foreach ($devicesToPush as $oneDevice) {
                    if ($oneDevice['device_type'] != "android") {
                        $msg = chr(0) . pack("n", 32) . pack('H*', $oneDevice['PushToken'])
                            . pack("n", strlen($emptyPush)) . $emptyPush; //4

                        file_put_contents($dbugFile, $oneDevice['device_type'] . "\t" .
                            $oneDevice['ID'] . " : " . $oneDevice['PushToken'] . "\n", FILE_APPEND | LOCK_EX);

                        //write the push message to the apns socket connection
                        $wroteBytes = fwrite($fp, $msg);

                        if ($wroteBytes != strlen($msg)) {
                            $batchStatus = false;
                            file_put_contents($dbugFile, "========= retry batch ========\n", FILE_APPEND | LOCK_EX);
                            break;
                        }

                        $this->checkAppleErrorResponse($fp);
                    }
                }
                usleep(500000);
                $this->checkAppleErrorResponse($fp);

                fclose($fp);
                if ($batchStatus) {
                    break;
                }
            }
            if (!$batchStatus) {
                file_put_contents($dbugFile, "========= batch failed! ========\n", FILE_APPEND | LOCK_EX);
            }
            file_put_contents($dbugFile, "\n", FILE_APPEND | LOCK_EX);
            usleep(1000000);
        }
    }
}

?>
