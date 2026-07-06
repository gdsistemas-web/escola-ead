<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumBadge;
use App\Models\ForumCategory;
use App\Models\ForumLike;
use App\Models\ForumNotification;
use App\Models\ForumReply;
use App\Models\ForumReport;
use App\Models\ForumReputation;
use App\Models\ForumSubscription;
use App\Models\ForumTag;
use App\Models\ForumTopic;
use App\Models\ForumView;
use App\Models\ForumWarning;
use App\Models\User;
use App\Services\ForumService;
use App\Services\TeacherNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForumController extends Controller
{
    public function __construct(
        private readonly ForumService $forumService,
        private readonly TeacherNotificationService $teacherNotifications,
    )
    {
    }

    public function index(Request $request)
    {
        $query = ForumTopic::query()
            ->with(['category.course', 'course', 'lesson', 'author.profile', 'tags', 'acceptedReply.author'])
            ->withCount(['replies', 'visibleReplies', 'likes'])
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhereHas('author', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->course_id, fn ($query, $courseId) => $query->where('course_id', $courseId))
            ->when($request->lesson_id, fn ($query, $lessonId) => $query->where('lesson_id', $lessonId))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->tag, fn ($query, $tag) => $query->whereHas('tags', fn ($query) => $query->where('slug', $tag)))
            ->when($request->boolean('unanswered'), fn ($query) => $query->whereDoesntHave('replies'))
            ->when($request->boolean('mine'), fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($request->boolean('needs_teacher'), fn ($query) => $query
                ->whereDoesntHave('replies')
                ->where('created_at', '<=', now()->subHours((int) $request->integer('sla_hours', 48))))
            ->when($request->boolean('resolved'), fn ($query) => $query->where('status', 'resolved'))
            ->where('status', '!=', 'hidden')
            ->orderByDesc('is_pinned')
            ->orderByRaw("case when status = 'pinned' then 1 else 0 end desc")
            ->latest('last_activity_at')
            ->latest();

        return $query->paginate(20);
    }

    public function categories()
    {
        return ForumCategory::with('course', 'lesson')
            ->withCount('topics')
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    public function tags()
    {
        return ForumTag::withCount('topics')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'forum_category_id' => ['required', 'exists:forum_categories,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'status' => ['nullable', 'in:open,closed,resolved,pinned,hidden'],
            'type' => ['nullable', 'in:discussion,question,announcement,assessment'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'is_assessment' => ['nullable', 'boolean'],
            'assessment_points' => ['nullable', 'numeric', 'min:0'],
            'assessment_due_at' => ['nullable', 'date'],
            'requires_reply' => ['nullable', 'boolean'],
        ]);

        $category = ForumCategory::with('course')->findOrFail($data['forum_category_id']);
        $user = $request->user();

        abort_if($user->hasRole('aluno') && ! $category->allow_student_topics, 403, 'Esta categoria não permite tópicos de alunos.');

        $topic = DB::transaction(function () use ($data, $category, $user) {
            $topic = ForumTopic::create([
                'forum_category_id' => $category->id,
                'course_id' => $data['course_id'] ?? $category->course_id,
                'lesson_id' => $data['lesson_id'] ?? $category->lesson_id,
                'user_id' => $user->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'status' => $data['status'] ?? 'open',
                'type' => $data['type'] ?? (($data['is_assessment'] ?? false) ? 'assessment' : 'discussion'),
                'is_pinned' => ($data['status'] ?? null) === 'pinned',
                'is_closed' => ($data['status'] ?? null) === 'closed',
                'is_assessment' => (bool) ($data['is_assessment'] ?? false),
                'assessment_points' => $data['assessment_points'] ?? null,
                'assessment_due_at' => $data['assessment_due_at'] ?? null,
                'requires_reply' => (bool) ($data['requires_reply'] ?? false),
                'last_activity_at' => now(),
            ]);

            $this->forumService->syncTags($topic, $data['tags'] ?? []);
            $this->forumService->captureMentions($topic->body, $user, $topic);
            $this->forumService->reputation($user, 'topic_created', ForumService::POINTS['topic_created'], $topic, courseId: $topic->course_id);
            $this->forumService->log($user, 'topic_created', $topic);

            return $topic;
        });

        $this->teacherNotifications->forumTopicCreated($topic);

        return $topic->load('category.course', 'course', 'lesson', 'author', 'tags');
    }

    public function show(Request $request, ForumTopic $forum)
    {
        if ($forum->status !== 'hidden') {
            $forum->increment('views_count');
            ForumView::create([
                'forum_topic_id' => $forum->id,
                'user_id' => $request->user()?->id,
                'ip_address' => $request->ip(),
            ]);
        }

        $forum->load([
            'category.course',
            'course',
            'lesson',
            'author.profile',
            'tags',
            'acceptedReply.author',
            'replies' => fn ($query) => $query->with('author.profile', 'likes')->where('is_hidden', false)->oldest(),
            'likes',
        ]);

        $forum->setAttribute('sla', [
            'unanswered' => $forum->replies->isEmpty(),
            'hours_open' => round($forum->created_at->diffInHours(now()), 1),
            'needs_teacher' => $forum->replies->isEmpty() && $forum->created_at->lte(now()->subHours(48)),
        ]);

        return $forum;
    }

    public function update(Request $request, ForumTopic $forum)
    {
        $this->authorizeForumManagement($request);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:open,closed,resolved,pinned,hidden'],
            'is_pinned' => ['sometimes', 'boolean'],
            'is_closed' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $forum->update([
            ...collect($data)->except('tags')->all(),
            'is_pinned' => $data['is_pinned'] ?? (($data['status'] ?? null) === 'pinned' ? true : $forum->is_pinned),
            'is_closed' => $data['is_closed'] ?? (($data['status'] ?? null) === 'closed' ? true : $forum->is_closed),
        ]);

        if (array_key_exists('tags', $data)) {
            $this->forumService->syncTags($forum, $data['tags']);
        }

        $this->forumService->log($request->user(), 'topic_updated', $forum);

        return $forum->load('tags');
    }

    public function destroy(Request $request, ForumTopic $forum)
    {
        $this->authorizeForumManagement($request);
        $forum->delete();

        return response()->noContent();
    }

    public function reply(Request $request, ForumTopic $forum)
    {
        abort_if($forum->is_closed || in_array($forum->status, ['closed', 'hidden'], true), 422, 'Tópico encerrado.');

        $data = $request->validate([
            'body' => ['required', 'string'],
            'parent_id' => ['nullable', 'exists:forum_replies,id'],
            'attachments' => ['nullable', 'array'],
        ]);

        $reply = DB::transaction(function () use ($request, $forum, $data) {
            $reply = ForumReply::create([
                'forum_topic_id' => $forum->id,
                'parent_id' => $data['parent_id'] ?? null,
                'user_id' => $request->user()->id,
                'body' => $data['body'],
                'attachments' => $data['attachments'] ?? null,
            ]);

            $forum->update([
                'replies_count' => $forum->replies()->count(),
                'last_activity_at' => now(),
            ]);
            ForumSubscription::firstOrCreate(['forum_topic_id' => $forum->id, 'user_id' => $request->user()->id]);

            $this->forumService->captureMentions($reply->body, $request->user(), reply: $reply);
            $this->forumService->notifySubscribers($forum->fresh('subscriptions.user', 'author'), $reply);
            $this->forumService->reputation($request->user(), 'reply_created', ForumService::POINTS['reply_created'], $reply, courseId: $forum->course_id);
            $this->forumService->log($request->user(), 'reply_created', $reply);

            return $reply;
        });

        $this->teacherNotifications->forumReplyCreated($forum, $reply);

        return $reply->load('author.profile', 'likes');
    }

    public function react(Request $request, ForumTopic $forum)
    {
        $data = $request->validate([
            'forum_reply_id' => ['nullable', 'exists:forum_replies,id'],
            'reaction' => ['required', 'in:liked,useful,excellent'],
        ]);

        $like = ForumLike::updateOrCreate(
            [
                'forum_topic_id' => empty($data['forum_reply_id']) ? $forum->id : null,
                'forum_reply_id' => $data['forum_reply_id'] ?? null,
                'user_id' => $request->user()->id,
            ],
            ['reaction' => $data['reaction']]
        );

        $targetUser = isset($data['forum_reply_id'])
            ? ForumReply::find($data['forum_reply_id'])?->author
            : $forum->author;

        if ($targetUser && $targetUser->id !== $request->user()->id) {
            $this->forumService->reputation($targetUser, 'reaction_received', ForumService::POINTS['reaction_received'], $like, $request->user(), $forum->course_id);
            $this->forumService->notify($targetUser, 'reaction', 'Sua contribuicao recebeu uma reacao', $forum->title, $forum, $like->reply);
        }

        return $like;
    }

    public function bestAnswer(Request $request, ForumTopic $forum, ForumReply $reply)
    {
        $this->authorizeForumManagement($request);
        abort_if($reply->forum_topic_id !== $forum->id, 422, 'Resposta não pertence ao tópico.');

        DB::transaction(function () use ($forum, $reply, $request) {
            $forum->replies()->update(['is_accepted' => false]);
            $reply->update(['is_accepted' => true]);
            $forum->update(['accepted_reply_id' => $reply->id, 'status' => 'resolved']);
            $this->forumService->reputation($reply->author, 'best_answer', ForumService::POINTS['best_answer'], $reply, $request->user(), $forum->course_id);
            $this->forumService->notify($reply->author, 'best_answer', 'Sua resposta foi marcada como melhor resposta', $forum->title, $forum, $reply);
            $this->forumService->log($request->user(), 'best_answer_marked', $reply);
        });

        return $forum->fresh()->load('acceptedReply.author', 'replies.author');
    }

    public function subscribe(Request $request, ForumTopic $forum)
    {
        $subscription = ForumSubscription::firstOrCreate([
            'forum_topic_id' => $forum->id,
            'user_id' => $request->user()->id,
        ]);

        return $subscription;
    }

    public function notifications(Request $request)
    {
        return ForumNotification::where('user_id', $request->user()->id)->latest()->paginate(30);
    }

    public function ranking(Request $request)
    {
        $base = ForumReputation::query()
            ->select('user_id', DB::raw('sum(points) as reputation'))
            ->when($request->course_id, fn ($query, $courseId) => $query->where('course_id', $courseId))
            ->when($request->period === 'month', fn ($query) => $query->where('created_at', '>=', now()->startOfMonth()))
            ->groupBy('user_id')
            ->orderByDesc('reputation')
            ->limit(10)
            ->with('user.profile')
            ->get();

        return $base;
    }

    public function profile(User $user)
    {
        return [
            'user' => $user->load('profile', 'roles'),
            'topics_count' => $user->forumTopics()->count(),
            'replies_count' => $user->forumReplies()->count(),
            'reputation' => (int) $user->forumReputation()->sum('points'),
            'badges' => ForumBadge::whereHas('users', fn ($query) => $query->where('users.id', $user->id))->get(),
        ];
    }

    public function reports(Request $request)
    {
        $this->authorizeForumManagement($request);

        return [
            'participation_by_course' => ForumTopic::query()
                ->select('course_id', DB::raw('count(*) as topics_count'), DB::raw('sum(replies_count) as replies_count'))
                ->with('course:id,name')
                ->groupBy('course_id')
                ->get(),
            'topics_without_reply' => ForumTopic::with('course:id,name')->whereDoesntHave('replies')->latest()->limit(20)->get(),
            'most_accessed' => ForumTopic::with('course:id,name')->orderByDesc('views_count')->limit(20)->get(),
            'engagement_ranking' => $this->ranking($request),
        ];
    }

    public function report(Request $request, ForumTopic $forum)
    {
        $data = $request->validate([
            'forum_reply_id' => ['nullable', 'exists:forum_replies,id'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        return ForumReport::create([
            'forum_topic_id' => $forum->id,
            'forum_reply_id' => $data['forum_reply_id'] ?? null,
            'user_id' => $request->user()->id,
            'reason' => $data['reason'],
        ]);
    }

    public function hideReply(Request $request, ForumReply $reply)
    {
        $this->authorizeForumManagement($request);
        $reply->update(['is_hidden' => true, 'hidden_at' => now()]);
        $this->forumService->log($request->user(), 'reply_hidden', $reply);

        return $reply;
    }

    public function warn(Request $request, User $user)
    {
        $this->authorizeForumManagement($request);

        return ForumWarning::create($request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'forum_topic_id' => ['nullable', 'exists:forum_topics,id'],
            'forum_reply_id' => ['nullable', 'exists:forum_replies,id'],
            'expires_at' => ['nullable', 'date'],
        ]) + [
            'user_id' => $user->id,
            'issued_by_user_id' => $request->user()->id,
        ]);
    }

    private function authorizeForumManagement(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['administrador', 'professor']), 403, 'Permissao insuficiente.');
    }
}
