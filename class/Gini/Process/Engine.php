<?php

namespace Gini\Process;

class Engine
{
    protected static $_engines = [];

    public static function of($name)
    {
        if (!isset(self::$_engines[$name])) {
            $opts = \Gini\Config::get('bpm-process-engine.'.$name);
            self::$_engines[$name] = \Gini\IoC::construct('\Gini\Process\Engine', $opts['driver'], (array)$opts['options']);
        }
        return self::$_engines[$name];
    }

    protected $_driver;
    public function __construct($driverName, array $options=[])
    {
        $driverClass = '\Gini\Process\Engine\\' . $driverName;
        $this->_driver = \Gini\IoC::construt($driverClass, $options);
        if (!$this->_driver instanceof \Gini\Process\IEngine) {
            throw new \Gini\Process\Engine\Exception();
        }
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->_driver, $method], $params);
    }

}
