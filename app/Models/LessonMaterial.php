<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonMaterial extends LmsModel
{
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
