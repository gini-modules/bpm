<?php

namespace Gini\ORM\SJTU\BPM\Process;

class Instance extends \Gini\ORM\Object implements \Gini\Process\IInstance
{
    public $process = 'object:sjtu/bpm/process';
    public $data = 'array';
    public $status = 'int,default:0';
    public $last_run_time = 'datetime';
    public $tag = 'string:30';

    protected static $db_index = [
        'unique:tag',
        'last_run_time',
    ];

    public function getVariable($key)
    {
        $data = (array)$this->data;
        return $data[$key];
    }

    public function start($isRestart = false)
    {
        $task = those('sjtu/bpm/process/task')->whose('instance')->is($this)
                ->orderBy('ctime', 'desc')
                ->orderBy('id', 'desc')
                ->current();
        if ($task->id) return;

        return $this->_execute($isRestart);
    }

    public function next()
    {
        return $this->_execute();
    }

    private function _execute($isRestart = false)
    {
        if ($isRestart) {
            $this->status = 0;
            $this->save();
            $info = $this->process->getNextTaskInfo();
        } else {
            if ($this->status==self::STATUS_END) return;
            $task = those('sjtu/bpm/process/task')->whose('instance')->is($this)
                    ->orderBy('ctime', 'desc')
                    ->orderBy('id', 'desc')
                    ->current();

            if ($task->id && !$task->isEnd()) {
                $task->autorun();
                return false;
            }
            $info = $this->process->getNextTaskInfo($task->id ? $task : null);
        }

        if (empty($info)) {
            $this->status = self::STATUS_END;
            $this->save();
            return;
        }

        list($position, $infoData) = $info;

        $task = $this->_fetchTask($position, (array)$infoData);

        if (!$task) return;

        $task->autorun();

        return true;
    }

    private function _fetchTask($position, array $info=[])
    {
        if (empty($info)) return;
        $task = a('sjtu/bpm/process/task');
        $task->process = $this->process;
        $task->instance = $this;
        $task->ctime = date('Y-m-d H:i:s');
        $task->position = $position;
        if (isset($info['callback'])) {
            $task->auto_callback = $info['callback'];
        } else if (isset($info['group'])) {
            $group = a('sjtu/bpm/process/group', [
                'name'=> $info['group'],
                'process'=> $this->process
            ]);
            if (!$group->id) return false;
            $task->candidate_group = $group;
        }
        if (!$task->save()) return false;

        return $task;
    }
}


