<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumUserBadge extends LmsModel
{
    protected function casts(): array
    {
        return ['awarded_at' => 'datetime'];
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(ForumBadge::class, 'forum_badge_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
