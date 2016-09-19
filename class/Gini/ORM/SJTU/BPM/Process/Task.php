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

    // TODO 如何避免task被重复创建

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
        $instance = $this->instance;
        $orderData = (array)$instance->getVariable('data');
        $voucher = $orderData['voucher'];
        if (!$voucher) return;
        $rpc = self::_getRPC('order');
        if (!$rpc) return;
        try {
            $bool = $rpc->mall->order->updateOrder($voucher, [
                'description'=> $description,
                'hash_rand_key'=> date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            return;
        }
        if (!$bool) return;
        $bool = $this->update($data);
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
            'user'=> $user->name
        ];
        $description = [
            'a' => T('**:group** **:name** **审核通过** 了该订单', [
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
            'user'=> $user->name
        ];
        $user = $user ?: _G('ME');
        $description = [
            'a' => T('**:group** **:name** **拒绝** 了该订单', [
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
        $description = [
            'a' => $message ?: T('**系统** 自动 **审核通过** 了该订单'),
            't' => $now,
            'd' => $message,
        ];
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
            'a' => T('**系统** 自动 **拒绝** 了该订单'),
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

        call_user_func_array($this->auto_callback, [
            $this
        ]);
    }

    // 订单的更新直接向lab-orders进行提交, 因为hub-orders没有自购订单的信息
    private static $_RPCs = [];
    private static function _getRPC($type)
    {
        $confs = \Gini\Config::get('app.rpc');
        if (!isset($confs[$type])) {
            return;
        }
        $conf = $confs[$type] ?: [];
        if (!self::$_RPCs[$type]) {
            $rpc = \Gini\IoC::construct('\Gini\RPC', $conf['url']);
            self::$_RPCs[$type] = $rpc;
            $clientID = $conf['client_id'];
            $clientSecret = $conf['client_secret'];
            $token = $rpc->mall->authorize($clientID, $clientSecret);
            if (!$token) {
                \Gini\Logger::of(APP_ID)
                    ->error('Mall\\RObject getRPC: authorization failed with {client_id}/{client_secret} !',
                        ['client_id' => $clientID, 'client_secret' => $clientSecret]);
            }
        }

        return self::$_RPCs[$type];
    }
}


