<?php

namespace Gini\ORM\SJTU\BPM\Process;

// 任务节点
class Task extends \Gini\ORM\Base implements \Gini\Process\ITask
{
    public $process = 'object:sjtu/bpm/process';
    public $instance = 'object:sjtu/bpm/process/instance';
    public $candidate_group = 'object:sjtu/bpm/process/group';
    public $position = 'string:50';
    public $ctime = 'datetime';
    public $status = 'int';
    // auto task的开始执行时间
    public $run_date = 'datetime';
    public $user_id = 'int,default:0';

    // TODO 如何避免task被重复创建
    protected static $db_index = [
        'instance',
        'ctime',
    ];

    public function isEnd()
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_UNAPPROVED
        ]);
    }

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

    private function _doUpdate($data, $description)
    {
        $customizedMethod = ['\\Gini\\Process\\Engine\\SJTU\\Task', 'doUpdate'];
        if (method_exists('\\Gini\\Process\\Engine\\SJTU\\Task', 'doUpdate')) {
            $bool = call_user_func($customizedMethod, $this, $description);
            if (!$bool) return;
        }

        $bool = $this->update($data);

        $customizedDoneMethod = ['\\Gini\\Process\\Engine\\SJTU\\Task', 'doneUpdate'];
        if ($bool && method_exists('\\Gini\\Process\\Engine\\SJTU\\Task', 'doneUpdate')) {
            call_user_func($customizedDoneMethod, $this, $description);
        }

        return $bool;
    }

    public function approve($message=null, $user=null)
    {
        $now = date('Y-m-d H:i:s');
        $user = $user ?: _G('ME');
        $upData = [
            'status'=> self::STATUS_APPROVED,
            'message'=> $message,
            'date'=> $now,
            'group'=> $this->candidate_group->title,
            'user'=> $user->name,
            'user_id'=> $user->id
        ];
        $description = [
            'a' => T('**:group** **:name** **审核通过**', [
                ':group'=> $this->candidate_group->title,
                ':name' => $user->name
            ]),
            't' => $now,
            'u' => $user->id,
            'd' => $message,
        ];
        return $this->_doUpdate($upData, $description);
    }

    public function reject($message=null, $user=null)
    {
        $now = date('Y-m-d H:i:s');
        $user = $user ?: _G('ME');
        $upData = [
            'status'=> self::STATUS_UNAPPROVED,
            'message'=> $message,
            'date'=> $now,
            'group'=> $this->candidate_group->title,
            'user'=> $user->name,
            'user_id'=> $user->id
        ];
        $user = $user ?: _G('ME');
        $description = [
            'a' => T('**:group** **:name** **拒绝**', [
                ':group'=> $this->candidate_group->title,
                ':name' => $user->name
            ]),
            't' => $now,
            'u' => $user->id,
            'd' => $message,
        ];
        return $this->_doUpdate($upData, $description);
    }

    public function autoApprove($message=null, $switch='approved')
    {
        $now = date('Y-m-d H:i:s');
        $upData = [
            'status'=> self::STATUS_APPROVED,
            'auto_callback_value'=> $switch,
            'auto_approve_date'=> date('Y-m-d H:i:s'),
            'message'=> $message,
            'group'=> '',
            'user'=> T('系统')
        ];

        $description = null;
        if (!is_null($message)) {
            $description = [
                'a' => $message,
                't' => $now,
                'd' => T('**系统** 自动操作'),
            ];
        }
        return $this->_doUpdate($upData, $description);
    }

    public function autoReject($message=null, $switch='unapproved')
    {
        $now = date('Y-m-d H:i:s');
        $upData = [
            'status'=> self::STATUS_UNAPPROVED,
            'auto_callback_value'=> $switch,
            'auto_reject_date'=> date('Y-m-d H:i:s'),
            'message'=> $message,
            'group'=> '',
            'user'=> T('系统')
        ];
        $description = [
            'a' => T('**系统** 自动 **拒绝**'),
            't' => $now,
            'd' => $message,
        ];
        return $this->_doUpdate($upData, $description);
    }

    public function autorun()
    {
        if ($this->isEnd()) return;
        if (!$this->auto_callback || !is_callable($this->auto_callback)) return;

        if ($this->status==self::STATUS_RUNNING) {
            $pid = $this->run_pid;
            if (file_exists("/proc/{$pid}")) return;
        }

        $this->update([
            'status'=> self::STATUS_RUNNING,
            'run_date'=> date('Y-m-d H:i:s'),
            'run_pid'=> getmypid()
        ]);

        try {
            call_user_func_array($this->auto_callback, [
                $this
            ]);
        } catch (\Exception $e) {
            $this->update([
                'status'=> self::STATUS_PENDING,
                'run_date'=> date('Y-m-d H:i:s'),
                'run_pid'=> 0
            ]);
        }
    }
}


