<?php

namespace SysInescolara\interfaces;

interface DeletableInterface
{
    public function delete(int $id): bool;
    public function exists(int $id): bool;
}
