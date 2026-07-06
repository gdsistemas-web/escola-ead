<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumCourse extends LmsModel
{
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'auto_create_lesson_forums' => 'boolean',
            'default_sections' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
