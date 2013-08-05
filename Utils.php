<?php

class Utils{

    # atmoic array access operation
    private static function arrayAccessor(&$data, $path, $value = null, $delimiter = '_'){
        # using reference operator to get the origin $data
        $temp = &$data;
        $pathArray = explode($delimiter, $path);
        # follow the path down to the leaf
        foreach($pathArray as $key){
            $temp = &$temp[$key];
        }
        if($value != null)
            $temp = $value;
        return $temp;
    }

    private static function pathIsLegal($data, $path, $delimiter = '_'){
        $temp = $data;
        $pathArray = explode($delimiter, $path);
        # follow the path down to the leaf
        foreach($pathArray as $key){
            if( isset($temp[$key]) )
                $temp = $temp[$key];
            else
                return false;
        }
        return true;
    }

    # test if target string starts with the prefix string
    public static function startsWith($target, $prefix){
        return !strncmp($target, $prefix, strlen($prefix));
    }

    # read value from an array by path
    public static function arrayAccessGetter($data, $path, $delimiter = '_'){
        return Utils::arrayAccessor($data, $path, $delimiter);
    }

    # write value to an array by path
    public static function arrayAccessSetter($data, $path, $value, $force = false, $delimiter = '_'){
        if($force){
            Utils::arrayAccessor($data, $path, $value, $delimiter);
        }
        else{
            if(Utils::pathIsLegal($data, $path)){
                Utils::arrayAccessor($data, $path, $value, $delimiter);
            }
            else{
                return false;
            }
        }
        return true;
    }

}