<?php

namespace Gini\ORM\SJTU\BPM\Process;

// 任务节点
class Task extends \Gini\ORM\Object implements \Gini\Process\ITask
{
    public $process = 'object:sjtu/bpm/process';
    public $instance = 'object:sjtu/bpm/process/instance';
    public $candidate_group = 'object:sjtu/bpm/process/group';
    public $position = 'string:50';
    public $ctime = 'datetime';
    public $status = 'int';

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_UNAPPROVED = 2;

    public function claim($uid)
    {
        // TODO
    }

    public function complete()
    {
        return $this->instance->next();
    }

    public function update(array $data=[])
    {
        foreach ($data as $k=>$v) {
            $this->$k = $v;
        }
        $this->save();
        return $this;
    }

    public function autorun()
    {
        if ($this->auto_callback && is_callable($this->auto_callback)) {
            $return = call_user_func_array($this->auto_callback, [
                $this
            ]);
            if ($this->status==self::STATUS_PENDING) return;
            $this->auto_callback_value = $return;
            $this->instance->next();
        }
    }
}


