<?php

/**
 * This class is for a easy debugging.
 * Examples:
 *  Extra\Dump::toPopup($data) - debug to popup
 *  Extra\Dump::toConsole($data) - debug to console
 *  Extra\Dump::toFile($data) - debug to file
 *
 *
 * This class has an alias for quickly calling the dump function.
 * Examples:
 *  Extra\Dump::p($data) - debug to popup
 *  Extra\Dump::c($data) - debug to console
 *  Extra\Dump::f($data) - debug to file
 */

namespace Extra;

class Dump
{
    /**
     * Default extension for dump files
     *
     * @var string
     */
    private static $defaultExtension = '.txt';

    private static $jsCssInited = false;


    // Functions

    /**
     * @param $data
     * @param string || null $label
     */
    public static function toConsole($data, $label = null)
    {
        // For displaying private and protected properties
        if(is_object($data)){
            $data = (array)$data;
        }

        // If the data has a NAN, then json_encode returns an error
        $data = unserialize(str_replace(['NAN;'],'0;', serialize($data)));

        echo '<script type="text/javascript">
        console.log(' . (empty($label) ? '' : '\'' . $label . '\',') . json_encode($data) . ');
        </script>';
    }

    /**
     * Write to file
     *
     * if $fileName == null that function generate file with name Dump_1__[2020-02-02__16_00_29] etc.
     *
     * @param $data
     * @param sttrin || null $fileName
     * @param string || null $label
     */
    public static function toFile($data, $fileName = null, $label = null){
        $time = date('d.m.Y H:i:s');
        $arTrace = self::getTrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $inFile = self::getCalledFile($arTrace);

        ob_start();
        echo '======================================================================================' . PHP_EOL;
        echo 'Time: ' .  $time . PHP_EOL;
        echo 'File: ' . $inFile;
        if($label !== null){
            echo PHP_EOL . 'Label: ' . $label ;
        }
        echo PHP_EOL . '======================================================================================' . PHP_EOL;
        self::dataPrint($data);
        echo PHP_EOL . PHP_EOL . PHP_EOL;
        $out = ob_get_contents();
        ob_end_clean();

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/' . self::createFileName($fileName), $out, FILE_APPEND);
    }




    public static function dump($data)
    {
        echo '<pre>';
        self::dataPrint($data);
        echo '</pre>';
    }

    /**
     * Get call stack
     *
     * @param int $options (DEBUG_BACKTRACE_IGNORE_ARGS - exclude the "args" key to reduce memory consumption)
     * @param int $limit
     * @return array
     */
    public static function getTrace($options = 0, $limit = 0)
    {
        ob_start();
        debug_print_backtrace($options, $limit);
        $trace = ob_get_contents();
        ob_end_clean();

        $arTrace = explode("\n", $trace);
        array_pop($arTrace); // Remove last empty from stack

        return $arTrace;
    }

    // Function aliases for fast call

    /**
     * Short call function toPopup()
     *
     * @param string $data
     * @param string || null $label
     */
    public static function p($data = '', $label = null)
    {
        self::toPopup($data, $label);
    }

    /**
     * Short call function toFile()
     *
     * @param $data
     * @param string || null $fileName
     * @param string || null $label
     */
    public static function f($data, $fileName = null, $label = null)
    {
        self::toFile($data, $fileName, $label);
    }

    /**
     * Short call function dump()
     *
     * @param $data
     */
    public static function d($data)
    {
        self::dump($data);
    }

    /**
     * Short call function toConsole()
     *
     * @param $data
     * @param string $label
     */
    public static function c($data, $label = '')
    {
        self::toConsole($data, $label);
    }

    /**
     * Dump and die
     *
     * @param $data
     */
    public static function dd($data)
    {
        self::dump($data);
        die();
    }


    //html

    /**
     * Render Js and Css
     */
    private static function initJsCss()
    {
        if(self::$jsCssInited === false){



            self::$jsCssInited = true;
        }
    }





    //helpers

    private static function dataPrint($data){
        if(is_array($data) || is_object($data)){
            print_r($data);
        }
        else if(is_bool($data)){
            echo ($data === true) ? 'true' : 'false';
        }
        else if(is_null($data)){
            echo 'null';
        }
        else{
            echo $data;
        }
    }

    /**
     * Create file name for dump files
     *
     * @param null $fileName
     * @return string
     */
    private static function createFileName($fileName = null)
    {
        static $i = 0;

        if(!empty($fileName) && strlen(trim($fileName)) > 0){
            if(strpos($fileName, '.txt') !== false || strpos($fileName, '.log') !== false){
                $result =  $fileName;
            }
            else{
                $result =  $fileName . self::$defaultExtension;
            }
        }
        else{
            $result = 'Dump_' . ++$i . '__[' . date('Y-m-d__H_i_s') . ']' . self::$defaultExtension;
        }

        return $result;
    }

    /**
     * Returns the file and the line where the debug function was called from
     *
     * @param $arTrace
     * $return string
     */
    private static function getCalledFile($arTrace)
    {
        $arr = [];
        foreach($arTrace as $trace){
            if(strpos($trace, __CLASS__) !== false){
                $arr[] = $trace;
            }
            else{
                break;
            }
        }

        $path = preg_split('/called at /', end($arr))[1] ?: '';
        return substr($path, 1, -1);
    }

}

/**
 * This class preparing data for JS
 *
 * Class JsonData
 * @package Extra
 */
class JsonData
{
    private static
        $propClassName = '__className',
        $protectedLabel = '__privProp',
        $privateLabel = '__protProp';

    public static function prepare($data){
        return json_encode( self::prepareData($data) );
    }

    private static function prepareData($data)
    {
        //var_dump($data);die();
        $type = self::getType($data);
        $result = self::createType($type);

        if($type === 'object' || $type === 'array')
        {
            $className = null;
            if($type === 'object'){
                $className = get_class($data);
                $propClassName = self::$propClassName;
                $result->$propClassName = $className;
            }

            array_walk($data, function($value, $key) use ($type, $className, &$result){

                if($type === 'object'){
                    $key = self::addVisibilityWrapper($key, $className);
                }

                $valueType = self::getType($value);
                if($valueType === 'object' || $valueType === 'array'){
                    $value = self::prepareData($value);
                }
                else if($valueType === 'double'){
                    $value = is_nan($value) ? 'NaN' : $value;
                }


                if($type === 'object'){
                    $key = self::prepareClassKey($key);
                    $result->$key = $value;
                }
                else if($type === 'array'){
                    $result[$key] = $value;
                }
            });
        }
        else if($type === 'double'){
            $result = is_nan($data) ? 'NaN' : $data;
        }
        else{
            $result = $data;
        }

        unset($data);
        return $result;
    }

    private static function createType($type)
    {
        if($type === 'array'){
            return [];
        }
        else if($type === 'object'){
            return new \stdClass();
        }
        else{
            return null;
        }
    }

    private static function getType($data)
    {
        return gettype($data);
    }

    private static function addVisibilityWrapper($prop, $className)
    {
        if(strpos($prop, $className) !== false){
            $prop = str_replace($className, self::$privateLabel, $prop);
            $prop = str_replace("\0", '', $prop);
        }
        else if(!empty($prop[1]) && $prop[1] === '*'){
            $prop = str_replace('*', self::$protectedLabel, $prop);
            $prop = str_replace("\0", '', $prop);
        }
        return $prop;
    }

    private static function prepareClassKey($key)
    {
        return str_replace("\0", ' ', $key);
    }
}
