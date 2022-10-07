<?php
// simple log util

class SimpleLogger
{
    const DEBUG = 0;
    const INFO  = 1;
    const ERROR = 2;

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
        $this->level = $level;
        return $this;
    }

    public function printf($level, $tpl, ...$args) 
    {
        switch ($level) {
        case self::DEBUG:
            $l = 'DEBUG';
	    break;
        case self::INFO:
            $l = 'INFO';
	    break;
        case self::ERROR:
            $l = 'ERROR';
	    break;
        default:
            throw new Exception("invalid log level {$level}");
        }

	if (count($args) > 0) {
            printf("%sT%s  [%5s]  %s\n", date('Y-m-d'), date('H:i:s'), $l, sprintf($tpl, ...$args));
	} else {
            printf("%sT%s  [%5s]  %s\n", date('Y-m-d'), date('H:i:s'), $l, $tpl);
	}
    }

    public function debugf($tpl, ...$args)
    {
        $this->printf(self::DEBUG, $tpl, $args);
    }

    public function infof($tpl, ...$args)
    {
        $this->printf(self::INFO, $tpl, $args);
    }

    public function errorf($tpl, ...$args)
    {
        $this->printf(self::ERROR, $tpl, $args);
    }

}
