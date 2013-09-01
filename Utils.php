<?php

class Utils
{

    # atmoic array access operation
    private static function arrayAccessor(&$data, $path, $value = null, $delimiter = '_')
    {
        # using reference operator to get the origin $data
        $temp = & $data;
        $pathArray = explode($delimiter, $path);
        # follow the path down to the leaf
        foreach ($pathArray as $key) {
            $temp = & $temp[$key];
        }
        if ($value != null)
            $temp = $value;
        return $temp;
    }

    private static function pathIsLegal($data, $path, $delimiter = '_')
    {
        $temp = $data;
        $pathArray = explode($delimiter, $path);
        # follow the path down to the leaf
        foreach ($pathArray as $key) {
            if (isset($temp[$key]))
                $temp = $temp[$key];
            else
                return false;
        }
        return true;
    }

    # test if target string starts with the prefix string
    public static function startsWith($target, $prefix)
    {
        return !strncmp($target, $prefix, strlen($prefix));
    }

    # read value from an array by path
    public static function arrayAccessGetter($data, $path, $delimiter = '_')
    {
        return Utils::arrayAccessor($data, $path, $delimiter);
    }

    # write value to an array by path
    public static function arrayAccessSetter(&$data, $path, $value, $force = false, $delimiter = '_')
    {
        if ($force) {
            Utils::arrayAccessor($data, $path, $value, $delimiter);
        } else {
            if (Utils::pathIsLegal($data, $path)) {
                Utils::arrayAccessor($data, $path, $value, $delimiter);
            } else {
                return false;
            }
        }
        return true;
    }

    public static function rgb2Html($r, $g = -1, $b = -1)
    {
        if (is_array($r) && sizeof($r) == 3)
            list($r, $g, $b) = $r;

        $r = intval($r);
        $g = intval($g);
        $b = intval($b);

        $r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
        $g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
        $b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));

        $color = (strlen($r) < 2 ? '0' : '') . $r;
        $color .= (strlen($g) < 2 ? '0' : '') . $g;
        $color .= (strlen($b) < 2 ? '0' : '') . $b;
        return '#' . $color;
    }

    public static function html2Rgb($color)
    {
        if ($color[0] == '#')
            $color = substr($color, 1);

        if (strlen($color) == 6)
            list($r, $g, $b) = array($color[0] . $color[1],
                $color[2] . $color[3],
                $color[4] . $color[5]);
        elseif (strlen($color) == 3)
            list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]); else
            return false;

        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);

        return array($r, $g, $b);
    }

    public static function extractRgbFromString($str)
    {
        $pattern = "/^rgb\((?P<r>\d+)\s*,\s*(?P<g>\d+)\s*,\s*(?P<b>\d+)\)/";
        preg_match($pattern, $str, $matches);
        $result = array($matches["r"], $matches["g"], $matches["b"]);
        return $result;
    }

    public static function convertIntRgbIntoString($r, $g = -1, $b = -1)
    {
        if (is_array($r) && sizeof($r) == 3)
            list($r, $g, $b) = $r;
        return "rgb($r, $g, $b)";
    }

}