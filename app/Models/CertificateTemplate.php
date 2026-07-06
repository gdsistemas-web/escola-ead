<?php

namespace App\Models;

class CertificateTemplate extends LmsModel
{
    protected function casts(): array
    {
        return ['signatures' => 'array', 'is_default' => 'boolean'];
    }
}
