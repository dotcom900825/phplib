<?php
/**
 * Created by JetBrains PhpStorm.
 * User: QDX
 * Date: 13-10-16
 * Time: 下午5:36
 * To change this template use File | Settings | File Templates.
 */

class FeedBack {


    public static function send_feedback_request($keyPath,$keyPassword)
    {
        //connect to the APNS feedback servers
        //make sure you're using the right dev/production server & cert combo!
        $stream_context = stream_context_create();
        stream_context_set_option($stream_context, 'ssl', 'local_cert', $keyPath .
            "/passcertbundle.pem");
        stream_context_set_option($stream_context, 'ssl', 'passphrase', $keyPassword);

        $apns = stream_socket_client('ssl://feedback.push.apple.com:2196',
            $errcode, $errstr, 60, STREAM_CLIENT_CONNECT, $stream_context);
        if (!$apns) {
            echo "ERROR $errcode: $errstr\n";
            return null;
        }

        $feedback_tokens = array();
        //and read the data on the connection:
        while (!feof($apns)) {
            $data = fread($apns, 38);
            if (strlen($data)) {
                $feedback_tokens[] = unpack("N1timestamp/n1length/H*devtoken", $data);
            }
        }
        fclose($apns);
        return $feedback_tokens;
    }

}