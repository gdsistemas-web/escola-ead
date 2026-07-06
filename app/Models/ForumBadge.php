<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ForumBadge extends LmsModel
{
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'forum_user_badges')->withPivot('awarded_at')->withTimestamps();
    }
}
