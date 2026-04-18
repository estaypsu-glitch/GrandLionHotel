<?php

namespace App\Models\Concerns;

trait HasLegacyIdAttribute
{
    public function getIdAttribute(): mixed
    {
        return $this->getAttribute($this->getKeyName());
    }
}
