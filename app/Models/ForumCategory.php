<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends LmsModel
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allow_student_topics' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class);
    }
}
