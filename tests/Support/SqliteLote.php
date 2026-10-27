<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Lote;

/**
 * Doble de Lote respaldado por SQLite en memoria.
 * SqliteLote::boot($pdo) debe invocarse en setUp().
 */
final class SqliteLote extends Lote
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
