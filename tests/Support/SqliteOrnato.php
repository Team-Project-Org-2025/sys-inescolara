<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Ornato;

/**
 * Doble de Ornato respaldado por SQLite en memoria.
 * SqliteOrnato::boot($pdo) debe invocarse en setUp().
 */
final class SqliteOrnato extends Ornato
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
