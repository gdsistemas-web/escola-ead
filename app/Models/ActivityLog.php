<?php

namespace App\Models;

class ActivityLog extends LmsModel
{
    protected function casts(): array
    {
        return ['properties' => 'array'];
    }
}
