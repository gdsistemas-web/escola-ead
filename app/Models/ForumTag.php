<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ForumTag extends LmsModel
{
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(ForumTopic::class, 'forum_topic_tags');
    }
}
