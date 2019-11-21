<?php

/**
 * Logger utils
 * @author yangjian
 */
import('Util');
class Logger {
    // 日志信息
    private static $message;
    // 日志目录
    private static $logDir;

    /**
     * 记录提示信息日志
     * @param $message
     * @param $logFile 日志文件名称，如果没有指定则记录到默认的日志文件
     * @return int
     */
    public static function info($message, $logFile='default')
    {
        self::_dataInit($message);
        return file_put_contents(self::$logDir.date("Y-m-d")."-{$logFile}.log", '['.date('Y-m-d H:i:s').'] [INFO]: '.self::$message."\n", FILE_APPEND);
    }

    /**
     * 记录错误信息日志
     * @param $message
     * @param $logFile 日志文件名称，如果没有指定则记录到默认的日志文件
     * @return int
     */
    public static function error($message, $logFile='default')
    {
        self::_dataInit($message);
        return file_put_contents(self::$logDir.date("Y-m-d")."-{$logFile}.log", '['.date('Y-m-d H:i:s').'] [ERROR]: '.self::$message."\n", FILE_APPEND);
    }

    /**
     * 日志数据清理
     * @param $message
     */
    private static function _dataInit($message)
    {
        if ( $message instanceof Exception ) {
            $message = $message->__toString();
        }
        if ( is_object($message) ) {
            $message = serialize($message);
        }
        if ( is_array($message) ) {
            $message = json_encode($message);
        }
        if (defined("SR_LOG_PATH")) {
            $logDir = SR_LOG_PATH .date('Y').'/'.date('m').'/';
        } else {
            $logDir = SR_TMPPATH .'logs/'.date('Y').'/'.date('m').'/';
        }
        if ( !file_exists($logDir) ) {
            Util::makePath($logDir);
        }
        self::$message = $message;
        self::$logDir = $logDir;
    }
}