<?php

namespace Gini\ORM\SJTU\BPM;

// 流程定义
class Process extends \Gini\ORM\Object
{
    public $name = 'string:120';
    public $parent = 'object:sjtu/bpm/process';
    public $version = 'int,default:0';
    public $ctime = 'datetime';
    public $rules = 'array';

    public function getNextTaskInfo($task=null)
    {
        $rules = $this->rules;
        if (!$task || !$task->id) {
            return [key($rules), current($rules)];
        }
        $position = $task->position;
        $rule = $rules[$position];
        if ($task->auto_callback) {
            if (!is_null($task->auto_callback_value)) {
                $switch = $rule['switch'];
                $position = $switch[$task->auto_callback_value];
                if (isset($rules[$position])) {
                    return [$position, $rules[$position]];
                }
            }
        } else {
            if ($task->status==\Gini\ORM\SJTU\BPM\Process\Task::STATUS_APPROVED) {
                $position = $rule['approved'];
                if (isset($rules[$position])) {
                    return [$position, $rules[$position]];
                }
            }
            else if ($task->status==\Gini\ORM\SJTU\BPM\Process\Task::STATUS_UNAPPROVED) {
                $position = $rule['unapproved'];
                if (isset($rules[$position])) {
                    return [$position, $rules[$position]];
                }
            }
        }
    }

    public function getGroups($user=null)
    {
        $result = those('sjtu/bpm/process/group')->whose('process')->is($this);
        if (!is_null($user)) {
            $gus = those('sjtu/bpm/process/group/user')->whose('user')->is($user);
            $gids = [];
            foreach ($gus as $gu) {
                $gids[] = $gu->group->id;
            }
            $result = those('sjtu/bpm/process/group')->whose('process')->is($this)
                    ->whose('id')->isIn($gids);
        }
        return $result;
    }

    public function getGroup($groupName)
    {
        $group = a('sjtu/bpm/process/group', [
            'process'=> $this,
            'name'=> $groupName
        ]);

        return $group->id ? $group : null;
    }

    public function addGroup($groupName, $data)
    {
        $group = a('sjtu/bpm/process/group');
        $group->process = $this;
        $group->name = $groupName;
        $group->title = $data['title'];
        $group->description = $data['description'];

        return !!$group->save();
    }
}

