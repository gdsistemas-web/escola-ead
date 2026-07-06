<?php

namespace App\Models;

class Setting extends LmsModel
{
    public function setValueAttribute($value): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : ['text' => $value];
        }

        $this->attributes['value'] = json_encode($value);
    }

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
