<?php

namespace Gini\Process\Engine;

class_exists('\Gini\Those');

class SJTU implements \Gini\Process\IEngine
{
    public function __construct()
    {
    }

    public function getProcess($processName, $version=null)
    {
        if (!is_null($version)) {
            $process = a('sjtu/bpm/process', [
                'name'=> $processName,
                'version'=> $version
            ]);
        } else {
            $process = those('sjtu/bpm/process')->whose('name')->is($processName)
                        ->orderBy('version', 'desc')->current();
        }
        return $process;
    }

    public function fetchProcessInstance($processName, $instancID)
    {
        $process = $this->getProcess($processName);
        if (!$process->id) return false;

        $instance = a('sjtu/bpm/process/instance', $instancID);

        return $instance->id ? $instance : false;
    }

    public function startProcessInstance($processName, $data, $tag)
    {
        $process = $this->getProcess($processName);
        if (!$process->id) {
            throw new \Gini\Process\Engine\Exception();
        }

        $instance = a('sjtu/bpm/process/instance', ['tag'=> $tag]);
        if ($instance->id) {
            if ($instance->status!=\Gini\Process\IInstance::STATUS_END) {
                throw new \Gini\Process\Engine\Exception();
            }
            $isRestart = true;
        } 

        $instance->process = $process;
        $instance->data = $data;
        $instance->tag = $tag;
        if (!$instance->save()) {
            throw new \Gini\Process\Engine\Exception();
        }

        $instance->start($isRestart);

        return $instance;
    }

    public function getTask($taskID)
    {
        return a('sjtu/bpm/process/task', $taskID);
    }

    public function those($name)
    {
        return those("sjtu/bpm/process/{$name}");
    }
}
