<?php

namespace Gini\Process;

interface IProcess
{
    public function getNextTaskInfo($task=null);
    public function getInstances($start=0, $perpage=25, $user=null);
    public function searchInstances($user=null);
    public function getGroups($user=null);
    public function getGroup($groupName);
    public function addGroup($groupName, $data);
}
