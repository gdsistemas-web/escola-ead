<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumReply extends LmsModel
{
    protected function casts(): array
    {
        return [
            'is_accepted' => 'boolean',
            'is_hidden' => 'boolean',
            'attachments' => 'array',
            'hidden_at' => 'datetime',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'forum_topic_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class, 'parent_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ForumLike::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(ForumMention::class);
    }
}
