<?php
namespace  app\modules\ding_property_app\company_jdk\util;
use Yii;

class Log
{
    public static function i($msg)
    {
        self::write('I', $msg);
    }
    
    public static function e($msg)
    {
        self::write('E', $msg);
    }

    private static function write($level, $msg)
    {
        $filename =  Yii::$app->basePath ."/". "runtime". "/". "logs". "/". "corp.log";
        $logFile = fopen($filename, "aw");
        fwrite($logFile, $level . "/" . date(" Y-m-d h:i:s") . "  " . $msg . "\n");
        fclose($logFile);
    }
}
