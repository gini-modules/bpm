<?php

namespace Gini\Process;

interface IEngine
{
    public function fetchProcessInstance($processName, $data);
    public function startProcessInstance($processName, $data);
    public function getTask($taskID);
}

