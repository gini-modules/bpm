<?php

namespace Gini\Process;

interface IProcess
{
    public function getNextTaskInfo($task=null);
    public function getGroups($user=null);
    public function getGroup($groupName);
    public function addGroup($groupName, $data);
}
