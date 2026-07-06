<?php

namespace App\Filament\Student\Pages;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\TeacherNotificationService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class CommunicationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Comunicação';

    protected static ?string $title = 'Comunicação';

    protected static UnitEnum|string|null $navigationGroup = 'Comunicação';

    protected static ?string $slug = 'comunicacao';

    protected string $view = 'filament.student.pages.communication';

    public ?int $selectedCourseId = null;

    public ?int $selectedRoomId = null;

    public string $messageBody = '';

    public function getCourses(): Collection
    {
        return Enrollment::with('course.teacher')
            ->where('user_id', auth()->id())
            ->whereIn('status', ['active', 'completed'])
            ->latest('enrolled_at')
            ->get()
            ->pluck('course')
            ->filter()
            ->values();
    }

    public function selectedRoom(): ?ChatRoom
    {
        if (! $this->selectedRoomId) {
            return null;
        }

        return ChatRoom::with('course.teacher', 'participants.user', 'messages.user')
            ->whereHas('participants', fn ($query) => $query->where('user_id', auth()->id()))
            ->find($this->selectedRoomId);
    }

    public function openCourse(int $courseId): void
    {
        $course = Course::with('teacher')->findOrFail($courseId);

        abort_unless(
            auth()->user()->enrollments()->where('course_id', $course->id)->whereIn('status', ['active', 'completed'])->exists(),
            403,
        );

        $room = ChatRoom::query()
            ->where('course_id', $course->id)
            ->where('type', 'direct')
            ->whereHas('participants', fn ($query) => $query->where('user_id', auth()->id()))
            ->whereHas('participants', fn ($query) => $query->where('user_id', $course->teacher_id))
            ->first();

        if (! $room) {
            $room = ChatRoom::create([
                'course_id' => $course->id,
                'name' => "Aluno/professor - {$course->name}",
                'type' => 'direct',
            ]);

            collect([auth()->id(), $course->teacher_id])
                ->filter()
                ->unique()
                ->each(fn ($userId) => $room->participants()->firstOrCreate(['user_id' => $userId]));
        }

        $this->selectedCourseId = $course->id;
        $this->selectedRoomId = $room->id;
        $this->messageBody = '';
    }

    public function sendMessage(): void
    {
        $room = $this->selectedRoom();

        abort_unless($room, 404);

        $data = $this->validate([
            'messageBody' => ['required', 'string', 'min:1', 'max:4000'],
        ]);

        $message = ChatMessage::create([
            'chat_room_id' => $room->id,
            'user_id' => auth()->id(),
            'body' => $data['messageBody'],
            'sent_at' => now(),
        ]);

        app(TeacherNotificationService::class)->studentChatMessage($room, $message);

        $this->messageBody = '';

        Notification::make()
            ->title('Mensagem enviada')
            ->success()
            ->send();
    }
}
