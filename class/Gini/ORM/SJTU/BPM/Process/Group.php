<?php

namespace Gini\ORM\SJTU\BPM\Process;

// 每个process流程都可以定义一定量的分组
class Group extends \Gini\ORM\Object
{
    public $name = 'string:120';
    public $process = 'object:sjtu/bpm/process';

    protected static $db_index = [
        'unique:name',
    ];
}
