<?php

namespace App\Models;

class Faq extends LmsModel
{
    protected $table = 'faq';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
