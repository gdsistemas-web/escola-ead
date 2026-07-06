<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends LmsModel
{
    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }
}
