<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

trait HasMongoUlidKey
{
    use HasUlids;

    public $incrementing = false;

    protected $primaryKey = '_id';

    protected $keyType = 'string';

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }
}
