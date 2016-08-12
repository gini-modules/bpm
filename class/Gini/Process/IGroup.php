<?php

namespace Gini\Process;

interface IGroup
{
    public function getUsers();
    public function addUser($user);
    public function removeUser($user);
}

