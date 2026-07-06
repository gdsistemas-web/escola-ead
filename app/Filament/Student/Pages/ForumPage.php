<?php

namespace App\Filament\Student\Pages;

use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Services\TeacherNotificationService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class ForumPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $navigationLabel = 'Fórum acadêmico';

    protected static ?string $title = 'Fórum acadêmico';

    protected static UnitEnum|string|null $navigationGroup = 'Comunicação';

    protected static ?string $slug = 'forum';

    protected string $view = 'filament.student.pages.forum';

    public ?int $selectedTopicId = null;

    public string $replyBody = '';

    public string $statusFilter = 'all';

    public function getTopics(): Collection
    {
        $courseIds = auth()->user()->enrollments()->pluck('course_id');

        return ForumTopic::query()
            ->with('course', 'category.course', 'author', 'tags')
            ->withCount('visibleReplies as visible_replies_count')
            ->where(function ($query) use ($courseIds) {
                $query->whereIn('course_id', $courseIds)
                    ->orWhereHas('category', fn ($query) => $query->whereIn('course_id', $courseIds));
            })
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'hidden'))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByRaw("case when status = 'pinned' then 1 else 0 end desc")
            ->latest('last_activity_at')
            ->latest()
            ->limit(30)
            ->get();
    }

    public function selectedTopic(): ?ForumTopic
    {
        if (! $this->selectedTopicId) {
            return null;
        }

        $courseIds = auth()->user()->enrollments()->pluck('course_id');

        return ForumTopic::query()
            ->with([
                'course',
                'category.course',
                'author',
                'tags',
                'visibleReplies.author',
            ])
            ->withCount('visibleReplies as visible_replies_count')
            ->where(function ($query) use ($courseIds) {
                $query->whereIn('course_id', $courseIds)
                    ->orWhereHas('category', fn ($query) => $query->whereIn('course_id', $courseIds));
            })
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'hidden'))
            ->find($this->selectedTopicId);
    }

    public function forumSummary(): array
    {
        $topics = $this->getTopics();

        return [
            'topics' => $topics->count(),
            'open' => $topics->whereIn('status', [null, 'open', 'pinned'])->count(),
            'resolved' => $topics->where('status', 'resolved')->count(),
            'unanswered' => $topics->where('visible_replies_count', 0)->count(),
        ];
    }

    public function openTopic(int $topicId): void
    {
        $topic = ForumTopic::findOrFail($topicId);
        $courseIds = auth()->user()->enrollments()->pluck('course_id');

        abort_unless(
            $courseIds->contains($topic->course_id) || $courseIds->contains($topic->category?->course_id),
            403,
        );

        $this->selectedTopicId = $topic->id;
        $this->replyBody = '';
    }

    public function closeTopic(): void
    {
        $this->selectedTopicId = null;
        $this->replyBody = '';
    }

    public function submitReply(): void
    {
        $topic = $this->selectedTopic();

        abort_unless($topic, 404);
        abort_if($topic->is_closed || in_array($topic->status, ['closed', 'hidden'], true), 422);

        $data = $this->validate([
            'replyBody' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $reply = DB::transaction(function () use ($topic, $data) {
            $reply = ForumReply::create([
                'forum_topic_id' => $topic->id,
                'user_id' => auth()->id(),
                'body' => $data['replyBody'],
            ]);

            $topic->update([
                'replies_count' => $topic->visibleReplies()->count(),
                'last_activity_at' => now(),
            ]);

            return $reply;
        });

        app(TeacherNotificationService::class)->forumReplyCreated($topic, $reply);

        $this->replyBody = '';

        Notification::make()
            ->title('Resposta publicada')
            ->body('Sua contribuição entrou no fórum.')
            ->success()
            ->send();
    }
}
