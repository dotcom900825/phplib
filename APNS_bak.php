<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wangfanfu
 * Date: 13-4-13
 * Time: 上午11:53
 * To change this template use File | Settings | File Templates.
 */

require_once("Database.php");

class APNS {

//config for generating passes
  //  private $keyPath = 'pass';
  //  private $keyPassword = "ucsdChineseUnion95536";

//apple push service endpoint
    private $apnsHost = 'ssl://gateway.push.apple.com:2195';

    function __construct($keyPath, $keyPassword) {
        $this->keyPath = $keyPath;
        $this->keyPassword = $keyPassword;
    }

//update all registered devices
    function updateAllPasses() {
        $db = Database::get();
        $statement = $db->prepare("SELECT pushToken FROM devices");
        $statement->execute();
        $this->sendPushesToResultSet($statement);
        return $statement->rowCount();
    }


//update all devices which have the given pass installed
    function updateForPassWithSerialNr($serialNr) {
        $db = Database::get();
        $statement = $db->prepare("SELECT pushToken FROM devices WHERE serialNr=?");
        $statement->execute(array($serialNr));
        $this->sendPushesToResultSet($statement);
    }

//send push notifications to all devices in the result set
    private function sendPushesToResultSet(PDOStatement $stmt) {

        //check if there are any results
        if ($stmt->rowCount()==0) return;

        //open connection to apns
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert',
            $this->keyPath."/passcertbundle.pem");

        stream_context_set_option($ctx, 'ssl', 'passphrase',
            $this->keyPassword);

        $fp = stream_socket_client($this->apnsHost, $err, $errstr, 15,
            STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT,
            $ctx);

        if (!$fp)
        {   //error handling
            $emailFrom = "noreply@ipassstore.com";
            $emailTo = "admin@ipassstore.com";
            mail($emailTo,
                "APNS Log",
                "Log message on ".date("Y-m-d H:i:s")."\n".
                    print_r($err,true).print_r($errstr,true), "From: ".$emailFrom);
            return;
        }
        //create an empty push
        $emptyPush = json_encode(new ArrayObject());

        //send it to all devices found
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            //write the push message to the apns socket connection
            $msg = chr(0). //1
                pack("n",32).
                pack('H*', $row['pushToken']). //2
                pack("n", strlen($emptyPush)). //3
                $emptyPush; //4
            fwrite($fp, $msg);
        }

        //close the apns connection
        fclose($fp);

    }

}
?>