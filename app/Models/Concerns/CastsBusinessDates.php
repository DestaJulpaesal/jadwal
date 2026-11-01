<?php

namespace App\Models\Concerns;

use Carbon\CarbonImmutable;

trait CastsBusinessDates
{
    public function setAttribute($key, $value)
    {
        if ($value !== null && in_array($key, $this->businessDateColumns ?? [], true)) {
            $this->attributes[$key] = CarbonImmutable::parse($value)->toDateString();

            return $this;
        }

        return parent::setAttribute($key, $value);
    }
}
