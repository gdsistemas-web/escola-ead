<?php

namespace App\Models;

class Banner extends LmsModel
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
