<?php

namespace Gini\Process;

interface ITask
{
    public function claim($uid);
    public function complete();
    public function update(array $data=[]);
}
