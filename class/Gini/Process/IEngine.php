<?php

namespace Gini\Process;

interface IEngine
{
    public function fetchProcessInstance($processName, $data);
    public function startProcessInstance($processName, $data, $tag);
    public function getProcess($processName, $version=null);
    public function getTask($taskID);
    public function those($key);
}

