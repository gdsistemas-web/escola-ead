<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScormScoData extends LmsModel
{
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ScormAttempt::class, 'scorm_attempt_id');
    }
}
