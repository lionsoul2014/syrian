<?php
// simple log util

class SimpleLogger
{
    const DEBUG = 0;
    const INFO  = 1;
    const WARN  = 2;
    const ERROR = 3;

    private $sys;
    private $level;

    public static function logger($sys)
    {
        return new self($sys);
    }

    public function __construct($sys)
    {
        $this->sys   = $sys;
        $this->level = self::INFO;
    }

    public function setLevel($level)
    {
        if (is_integer($level)) {
            $this->level = $level;
        } else if (is_string($level)) {
            switch (strtolower($level)) {
            case 'debug':
                $this->level = self::DEBUG;
                break;
            case 'info':
                $this->level = self::INFO;
                break;
            case 'warn':
                $this->level = self::WARN;
                break;
            case 'error':
                $this->level = self::ERROR;
                break;
            default:
                throw new Exception("invalid level `{$level}`");
            }
        } else if ($level != NULL && $level != false) {
            throw new Exception("invalid level type: integer or string expected");
        }

        return $this;
    }

    public function printf($level, $format, $args) 
    {
        switch ($level) {
        case self::DEBUG:
            $l = 'DEBUG';
            break;
        case self::INFO:
            $l = 'INFO';
            break;
        case self::WARN:
            $l = 'WARN';
            break;
        case self::ERROR:
            $l = 'ERROR';
            break;
        default:
            throw new Exception("invalid log level {$level}");
        }

        if (count($args) > 0) {
            printf("%sT%s [%5s] %s  %s\n", date('Y-m-d'), date('H:i:s'), $l, $this->sys, vsprintf($format, $args));
        } else {
            printf("%sT%s [%5s] %s  %s\n", date('Y-m-d'), date('H:i:s'), $l, $this->sys, $format);
        }
    }

    public function debugf($format, ...$args)
    {
	$this->printf(self::DEBUG, $format, $args);
    }

    public function infof($format, ...$args)
    {
        $this->printf(self::INFO, $format, $args);
    }

    public function warnf($format, ...$args)
    {
        $this->printf(self::WARN, $format, $args);
    }

    public function errorf($format, ...$args)
    {
        $this->printf(self::ERROR, $format, $args);
    }

}
