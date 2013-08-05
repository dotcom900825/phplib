<?php

require_once ("DataInterface.php");
require_once ("DebugLog.php");

class JsonInterface
{
    private $jsonContent = null;
    private $cardPath= null;
    private $pathPrefix = "/../../public_html/Client/";
    private $pathSuffix = "/pass/source/pass.json";


    function __construct($cardId = null){
        if($cardId != null){
            $this->readJsonByCardId($cardId);
        }
    }

    private function readJsonFromFileIntoArray($filename)
    {
        $jsonContent = json_decode(file_get_contents($filename), true);
        /* read the json file and decode to an object�� when true,
        objects will be converted into associative array(use string as keys)*/
        return $jsonContent;
    }

    private function getRealPathByCardId($cardId){
        $folder = DataInterface::getFolderByCardId($cardId);
        $filepath = realpath(dirname(__file__) . $this->pathPrefix . $folder . $this->pathSuffix);
        return $filepath;
    }


    public function readJsonByCardId($cardId){
        $this->cardPath = $this->getRealPathByCardId($cardId);
        $this->jsonContent = $this->readJsonFromFileIntoArray($this->cardPath);
    }

    public function getJsonContent(){
        return $this->jsonContent;
    }

    public function setJsonContent($content){
        $this->jsonContent = $content;
    }

    public function saveJsonToFile(){
        file_put_contents($this->cardPath, json_encode($this->jsonContent));
    }

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

        $content = $this->readJsonFromFileIntoArray($filepath);
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

        $content = $this->readJsonFromFileIntoArray($filepath);
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

        $content = $this->readJsonFromFileIntoArray($filepath);
        return $content;
    }
}

?>