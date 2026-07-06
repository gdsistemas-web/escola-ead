<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends LmsModel
{
    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'lgpd_consent_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'data_exported_at' => 'datetime',
            'anonymization_requested_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
