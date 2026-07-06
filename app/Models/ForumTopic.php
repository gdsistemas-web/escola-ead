<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumTopic extends LmsModel
{
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_closed' => 'boolean',
            'is_assessment' => 'boolean',
            'requires_reply' => 'boolean',
            'assessment_due_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function acceptedReply(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class, 'accepted_reply_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class);
    }

    public function visibleReplies(): HasMany
    {
        return $this->hasMany(ForumReply::class)->where('is_hidden', false);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ForumTag::class, 'forum_topic_tags')->withTimestamps();
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ForumLike::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ForumSubscription::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(ForumMention::class);
    }
}
