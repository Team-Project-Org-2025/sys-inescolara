<?php

namespace SysInescolara\interfaces;

interface ReadableInterface
{
    public function getAll(): array;
    public function getById(int $id): ?array;
}
