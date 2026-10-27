<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Merma;

/**
 * Doble de Merma respaldado por SQLite en memoria.
 * SqliteMerma::boot($pdo) debe invocarse en setUp().
 */
final class SqliteMerma extends Merma
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
