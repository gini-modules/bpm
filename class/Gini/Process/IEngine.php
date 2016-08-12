<?php

namespace Gini\Process;

interface IEngine
{
    public function fetchProcessInstance($processName, $data);
    public function startProcessInstance($processName, $data);
    public function getTask($taskID);
    public function those($key);
    public function getProcessGroups($processName);
    public function getProcessGroup($processName, $groupName);
    public function addProcessGroup($processName, $groupName, $data);
}

