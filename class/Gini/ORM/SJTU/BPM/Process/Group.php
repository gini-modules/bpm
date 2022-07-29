<?php

namespace Gini\ORM\SJTU\BPM\Process;

class_exists('\Gini\Those');

// 每个process流程都可以定义一定量的分组
class Group extends \Gini\ORM\Base implements \Gini\Process\IGroup
{
    public $name = 'string:120';
    public $title = 'string:120';
    public $process = 'object:sjtu/bpm/process';

    protected static $db_index = [
        'unique:name,process',
    ];

    public function getUsers()
    {
        $gus = those('sjtu/bpm/process/group/user')->whose('group')->is($this);
        $users = [];
        foreach ($gus as $gu)
        {
            $users[$gu->user->id] = $gu->user;
        }
        return $users;
    }

    public function addUser($user)
    {
        $gu = a('sjtu/bpm/process/group/user', [
            'group'=> $this,
            'user'=> $user
        ]);
        if ($gu->id) return false;

        $gu->group = $this;
        $gu->user = $user;
        return !!$gu->save();
    }

    public function removeUser($user)
    {
        $gu = a('sjtu/bpm/process/group/user', [
            'group'=> $this,
            'user'=> $user
        ]);
        if (!$gu->id) return true;

        return !!$gu->delete();
    }
}
