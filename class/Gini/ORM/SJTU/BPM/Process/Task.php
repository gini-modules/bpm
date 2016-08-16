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
    // auto task的开始执行时间
    public $run_date = 'datetime';

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
        return $this->save();
    }

    public function pass($message=null)
    {
        return $this->update([
            'status'=> self::STATUS_APPROVED,
            'message'=> $message,
            'pass_date'=> date('Y-m-d H:i:s')
        ]);
    }

    public function reject($message=null)
    {
        return $this->update([
            'status'=> self::STATUS_UNAPPROVED,
            'message'=> $message,
            'reject_date'=> date('Y-m-d H:i:s')
        ]);
    }

    public function isEnd()
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_UNAPPROVED
        ]);
    }

    public function autoApprove($switch, $message=null)
    {
        return $this->update([
            'status'=> self::STATUS_APPROVED,
            'auto_callback_value'=> $switch,
            'message'=> $message
        ]);
    }

    public function autoReject($switch, $message=null)
    {
        return $this->update([
            'status'=> self::STATUS_UNAPPROVED,
            'auto_callback_value'=> $switch,
            'message'=> $message
        ]);
    }

    public function autorun()
    {
        if ($this->isEnd()) return;
        if (!$this->auto_callback || !is_callable($this->auto_callback)) return;

        if ($this->status==self::STATUS_RUNNING) return;

        $this->update([
            'status'=> self::STATUS_RUNNING,
            'run_date'=> date('Y-m-d H:i:s')
        ]);

        call_user_func_array($this->auto_callback, [
            $this
        ]);
    }
}


