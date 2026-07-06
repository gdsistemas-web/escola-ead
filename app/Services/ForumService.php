<?php

namespace App\Services;

use App\Models\ForumActivityLog;
use App\Models\ForumBadge;
use App\Models\ForumMention;
use App\Models\ForumNotification;
use App\Models\ForumReply;
use App\Models\ForumReputation;
use App\Models\ForumTag;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ForumService
{
    public const POINTS = [
        'lesson_completed' => 3,
        'course_completed' => 80,
        'certificate_issued' => 120,
        'topic_created' => 5,
        'reply_created' => 10,
        'reaction_received' => 2,
        'best_answer' => 50,
        'daily_participation' => 1,
    ];

    public function syncTags(ForumTopic $topic, array $tagNames): void
    {
        $tagIds = collect($tagNames)
            ->filter()
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique(fn (string $name) => Str::slug($name))
            ->map(function (string $name) {
                return ForumTag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                )->id;
            });

        $topic->tags()->sync($tagIds);
    }

    public function reputation(User $user, string $event, int $points, ?Model $subject = null, ?User $source = null, ?int $courseId = null): void
    {
        if ($subject && ForumReputation::where('user_id', $user->id)
            ->where('event', $event)
            ->where('subject_type', $subject::class)
            ->where('subject_id', $subject->getKey())
            ->exists()) {
            return;
        }

        ForumReputation::create([
            'user_id' => $user->id,
            'course_id' => $courseId,
            'source_user_id' => $source?->id,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'event' => $event,
            'points' => $points,
        ]);

        $this->awardBadges($user);
    }

    public function notify(User $user, string $type, string $title, ?string $body = null, ?ForumTopic $topic = null, ?ForumReply $reply = null): void
    {
        ForumNotification::create([
            'user_id' => $user->id,
            'forum_topic_id' => $topic?->id,
            'forum_reply_id' => $reply?->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $topic ? "/aluno/forum" : '/aluno/forum',
        ]);
    }

    public function captureMentions(string $body, User $author, ?ForumTopic $topic = null, ?ForumReply $reply = null): Collection
    {
        preg_match_all('/@([\w.\-]+)/u', $body, $matches);

        return collect($matches[1] ?? [])
            ->unique()
            ->map(function (string $handle) {
                return User::query()
                    ->where('email', 'like', $handle.'%')
                    ->orWhere('name', 'like', str_replace('.', ' ', $handle).'%')
                    ->first();
            })
            ->filter()
            ->each(function (User $mentioned) use ($author, $topic, $reply) {
                ForumMention::create([
                    'forum_topic_id' => $topic?->id ?? $reply?->forum_topic_id,
                    'forum_reply_id' => $reply?->id,
                    'mentioned_user_id' => $mentioned->id,
                    'mentioned_by_user_id' => $author->id,
                ]);

                if ($mentioned->id !== $author->id) {
                    $this->notify($mentioned, 'mention', "{$author->name} mencionou voce", $topic?->title ?? $reply?->topic?->title, $topic ?? $reply?->topic, $reply);
                }
            });
    }

    public function notifySubscribers(ForumTopic $topic, ForumReply $reply): void
    {
        $users = $topic->subscriptions()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->reject(fn (User $user) => $user->id === $reply->user_id);

        if ($topic->user_id !== $reply->user_id) {
            $users->push($topic->author);
        }

        $users->unique('id')->each(function (User $user) use ($topic, $reply) {
            $this->notify($user, 'reply', 'Nova resposta no forum', $topic->title, $topic, $reply);
        });
    }

    public function log(?User $user, string $event, ?Model $subject = null, array $properties = []): void
    {
        ForumActivityLog::create([
            'user_id' => $user?->id,
            'event' => $event,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
        ]);
    }

    private function awardBadges(User $user): void
    {
        $points = (int) ForumReputation::where('user_id', $user->id)->sum('points');
        $topics = $user->forumTopics()->count();
        $replies = $user->forumReplies()->count();

        $badges = [
            ['Primeira Participacao', 'primeira-participacao', 'ri-seedling-line', $topics + $replies >= 1, 0],
            ['Aluno Participativo', 'aluno-participativo', 'ri-user-heart-line', $topics + $replies >= 5, 50],
            ['Debatedor', 'debatedor', 'ri-discuss-line', $replies >= 10, 120],
            ['Aluno Persistente', 'aluno-persistente', 'ri-run-line', $points >= 80, 80],
            ['Especialista', 'especialista', 'ri-medal-line', $points >= 250, 250],
            ['Mentor da Comunidade', 'mentor-da-comunidade', 'ri-shield-star-line', $points >= 500, 500],
            ['Certificado Conquistado', 'certificado-conquistado', 'ri-verified-badge-line', $points >= 120, 120],
            ['Professor Destaque', 'professor-destaque', 'ri-presentation-line', $user->hasRole('professor') && $replies >= 15, 150],
        ];

        foreach ($badges as [$name, $slug, $icon, $eligible, $required]) {
            if (! $eligible) {
                continue;
            }

            $badge = ForumBadge::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'icon' => $icon, 'points_required' => $required]
            );

            $badge->users()->syncWithoutDetaching([$user->id => ['awarded_at' => now()]]);
        }
    }
}
