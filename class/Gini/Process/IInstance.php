<?php

namespace Gini\Process;

interface IInstance
{
    public function getVariable($key);
    public function start();
}
