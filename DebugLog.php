<?php

class DebugLog
{
    public function WriteLogRaw($content)
    {
        $dbugFile = dirname(__file__) . "/debug";
        $current = file_get_contents($dbugFile);
        file_put_contents($dbugFile, $current . "$content");
    }
    public function WriteLogWithFormat($content)
    {
        $date = date('m/d/Y h:i:s a', time());
        DebugLog::WriteLogRaw($current . "==================$date====================\r\n" .
            "$content \r\n" . "======================================================\r\n");
    }
}
