<?php

namespace Gini\ORM\SJTU\BPM\Process\Group;

// 自定义组的用户分配
class User extends \Gini\ORM\Base
{
    public $user = 'object:user';
    public $group = 'object:sjtu/bpm/process/group';

    protected static $db_index = [
        'unique:user,group',
    ];

}
