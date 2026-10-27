<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\SeedCollection;

/**
 * Doble de SeedCollection respaldado por SQLite en memoria.
 * SqliteSeedCollection::boot($pdo) debe invocarse en setUp().
 */
final class SqliteSeedCollection extends SeedCollection
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
