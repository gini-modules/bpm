<?php

namespace Gini\Process;

interface ITask
{
    const STATUS_PENDING = 0;
    const STATUS_RUNNING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_UNAPPROVED = 3;
    public function claim($uid);
    public function complete();
    public function update(array $data=[]);
}
