<?php

namespace App\Models;

class Notification extends LmsModel
{
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
