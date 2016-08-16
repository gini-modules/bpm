<?php

namespace Gini\Process;

interface IInstance
{
    const STATUS_END = '-1';

    public function getVariable($key);
    public function start();
    public function next();
}
