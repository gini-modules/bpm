<?php

namespace Gini\Process;

interface IEngine
{
    public function fetchProcessInstance($processName, $data);
    public function startProcessInstance($processName, $data);
    public function getTask($taskID);
    public function those($key);
    public function getProcessGroups($processName, $version=null);
    public function getProcessGroup($processName, $groupName, $processVersion=null);
}

