<?php

namespace App\Http\Controllers\Api;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TeacherNotificationService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        return ChatRoom::query()
            ->with('course:id,name', 'participants.user', 'messages.user')
            ->when($request->course_id, fn ($query, $courseId) => $query->where('course_id', $courseId))
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->whereHas('participants', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => ['nullable', 'exists:courses,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:direct,class,course'],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['integer', 'exists:users,id'],
        ]);

        $this->authorizeCourseChat($request, $data['course_id'] ?? null);

        $room = ChatRoom::create(collect($data)->only('course_id', 'name', 'type')->all());
        $participantIds = collect($data['participants'] ?? [])
            ->push($request->user()->id)
            ->unique()
            ->values();

        if ($data['type'] === 'course' && ! empty($data['course_id'])) {
            $participantIds = $participantIds
                ->merge(User::whereHas('enrollments', fn ($query) => $query->where('course_id', $data['course_id'])->whereIn('status', ['active', 'completed']))->pluck('id'))
                ->merge(User::whereHas('taughtCourses', fn ($query) => $query->where('courses.id', $data['course_id']))->pluck('id'))
                ->unique()
                ->values();
        }

        $participantIds->each(fn ($userId) => $room->participants()->firstOrCreate(['user_id' => $userId]));

        return $room;
    }

    public function show(Request $request, ChatRoom $chat)
    {
        $this->authorizeParticipant($request, $chat);

        return $chat->load('participants.user', 'messages.user', 'messages.attachments');
    }

    public function update(Request $request, ChatRoom $chat)
    {
        $this->authorizeParticipant($request, $chat);

        $chat->update($request->validate(['name' => ['sometimes', 'string'], 'type' => ['sometimes', 'in:direct,class,course']]));

        return $chat;
    }

    public function destroy(ChatRoom $chat)
    {
        abort_unless(
            request()->user()?->hasRole('administrador') || $chat->participants()->where('user_id', request()->user()?->id)->exists(),
            403,
            'Você não participa desta sala de chat.',
        );

        $chat->delete();

        return response()->noContent();
    }

    public function message(Request $request, ChatRoom $chat, TeacherNotificationService $teacherNotifications)
    {
        $this->authorizeParticipant($request, $chat);

        $message = ChatMessage::create([
            'chat_room_id' => $chat->id,
            'user_id' => $request->user()->id,
            'body' => $request->validate(['body' => ['required', 'string']])['body'],
            'sent_at' => now(),
        ])->load('user');

        $teacherNotifications->studentChatMessage($chat, $message);

        return $message;
    }

    public function rules()
    {
        return [
            'rules' => [
                'Use o chat para dúvidas rápidas e orientações objetivas.',
                'Dúvidas conceituais recorrentes devem virar tópico no fórum acadêmico.',
                'Mantenha linguagem respeitosa e relacionada ao curso.',
                'Mensagens ficam registradas para acompanhamento pedagógico.',
            ],
            'types' => ['direct', 'class', 'course'],
        ];
    }

    private function authorizeParticipant(Request $request, ChatRoom $chat): void
    {
        abort_unless(
            $chat->participants()->where('user_id', $request->user()->id)->exists() || $request->user()->hasRole('administrador'),
            403,
            'Você não participa desta sala de chat.',
        );
    }

    private function authorizeCourseChat(Request $request, ?int $courseId): void
    {
        if (! $courseId || $request->user()->hasRole('administrador')) {
            return;
        }

        abort_unless(
            $request->user()->enrollments()->where('course_id', $courseId)->whereIn('status', ['active', 'completed'])->exists()
                || $request->user()->taughtCourses()->where('courses.id', $courseId)->exists(),
            403,
            'E necessario participar do curso para criar chat neste contexto.',
        );
    }
}
