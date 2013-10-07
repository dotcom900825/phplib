<?php

class DebugLog
{
    public static function WriteLogRaw($content)
    {
        $dbugFile = dirname(__file__) . "/debug";
        file_put_contents($dbugFile, "$content", FILE_APPEND | LOCK_EX);
    }
    public static function WriteLogWithFormat($content)
    {
        $date = date('m/d/Y h:i:s a', time());
        DebugLog::WriteLogRaw("==================$date====================\r\n" .
            "$content \r\n" . "======================================================\r\n");
    }
}
