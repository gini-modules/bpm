<?php

namespace Gini\ORM\SJTU\BPM;

// 流程定义
class Process extends \Gini\ORM\Object
{
    public $name = 'string:120';
    public $parent = 'object:sjtu/bpm/process';
    public $version = 'int,default:0';
    public $ctime = 'datetime';

    public function getNextTaskInfo($task=null)
    {
        $rules = $this->rules;
        if (!$task || !$task->id) {
            return [key($rules), current($rules)];
        }
        $position = $task->position;
        $rule = $rules[$position];
        if ($task->auto_callback) {
            if (isset($task->auto_callback_value)) {
                $switch = $rule['switch'];
                $position = $switch[$task->auto_callback_value];
                if (isset($rules[$position])) {
                    return [$position, $rules[$position]];
                }
            }
        } else {
            if ($task->status==\Gini\ORM\BPM\Process\Task::STATUS_APPROVED) {
                $position = $rule['approved'];
                if (isset($rules[$position])) {
                    return [$position, $rules[$position]];
                }
            }
            else if ($task->status==\Gini\ORM\BPM\Process\Task::STATUS_UNAPPROVED) {
                $position = $rule['unapproved'];
                if (isset($rules[$position])) {
                    return [$position, $rules[$position]];
                }
            }
        }
    }
}

