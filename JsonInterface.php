<?php

require_once ("DataInterface.php");
require_once ("DebugLog.php");

class JsonInterface
{
    private $content = null;
    private $pathPrefix = "/../../public_html/Client/";
    private $pathSuffix = "/pass/source/pass.json";

    /* This function changes the storeCard.headerFields.value filed of the json
    file into the value give by $message, nothing else.
    */
    public function Push($cardId, $message)
    {
        //DebugLog::WriteLogWithFormat("Pass::PushTest(cardId:$cardId,message:$message)");

        $folder = DataInterface::getFolderByCardId($cardId);

        //DebugLog::WriteLogWithFormat("- filefolder:".($this->pathPrefix . $folder . $this->pathSuffix));

        $filepath = realpath(dirname(__file__) . $this->pathPrefix . $folder . $this->
            pathSuffix);

        //DebugLog::WriteLogWithFormat("- filepath:".$filepath);

        $content = $this->ReadJsonFromFileIntoArray($filepath);
        $content['storeCard']['headerFields'][0]['value'] = $message;
        file_put_contents($filepath, json_encode($content));
    }

    public function writeJsonIdAndAuthToken($cardId, $id, $authToken)
    {
        $folder = DataInterface::getFolderByCardId($cardId);

        //DebugLog::WriteLogWithFormat("- filefolder:".($this->pathPrefix . $folder . $this->pathSuffix));

        $filepath = realpath(dirname(__file__) . $this->pathPrefix . $folder . $this->
            pathSuffix);

        //DebugLog::WriteLogWithFormat("- filepath:".$filepath);

        $content = $this->ReadJsonFromFileIntoArray($filepath);
        $content['serialNumber'] = "$id";
        $content['authenticationToken'] = $authToken;
        file_put_contents($filepath, json_encode($content));
    }

    public function getJsonArray($cardId)
    {
        $folder = DataInterface::getFolderByCardId($cardId);

        DebugLog::WriteLogWithFormat("- filefolder:".($this->pathPrefix . $folder . $this->pathSuffix));

        $filepath = realpath(dirname(__file__) . $this->pathPrefix . $folder . $this->pathSuffix);

        DebugLog::WriteLogWithFormat("- filepath:".$filepath);

        $content = $this->ReadJsonFromFileIntoArray($filepath);
        return $content;
    }

    private function ReadJsonFromFileIntoArray($filename)
    {
        $jsonContent = json_decode(file_get_contents($filename), true);
        /* read the json file and decode to an object£¬ when true,
        objects will be converted into associative array(use string as keys)*/
        return $jsonContent;
    }
}

?>