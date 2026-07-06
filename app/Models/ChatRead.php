<?php

namespace App\Models;

class ChatRead extends LmsModel
{
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
