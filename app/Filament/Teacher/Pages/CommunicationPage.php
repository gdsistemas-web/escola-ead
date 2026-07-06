<?php

namespace App\Filament\Teacher\Pages;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
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

    protected string $view = 'filament.teacher.pages.communication';

    public ?int $selectedRoomId = null;

    public string $messageBody = '';

    public function getRooms(): Collection
    {
        return ChatRoom::with('course', 'participants.user', 'messages.user')
            ->whereHas('course', fn ($query) => $query->where('teacher_id', auth()->id()))
            ->whereHas('participants', fn ($query) => $query->where('user_id', auth()->id()))
            ->latest('updated_at')
            ->get();
    }

    public function selectedRoom(): ?ChatRoom
    {
        if (! $this->selectedRoomId) {
            return null;
        }

        return ChatRoom::with('course', 'participants.user', 'messages.user')
            ->whereHas('course', fn ($query) => $query->where('teacher_id', auth()->id()))
            ->whereHas('participants', fn ($query) => $query->where('user_id', auth()->id()))
            ->find($this->selectedRoomId);
    }

    public function openRoom(int $roomId): void
    {
        $room = ChatRoom::whereHas('course', fn ($query) => $query->where('teacher_id', auth()->id()))
            ->whereHas('participants', fn ($query) => $query->where('user_id', auth()->id()))
            ->findOrFail($roomId);

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

        ChatMessage::create([
            'chat_room_id' => $room->id,
            'user_id' => auth()->id(),
            'body' => $data['messageBody'],
            'sent_at' => now(),
        ]);

        $room->touch();
        $this->messageBody = '';

        Notification::make()
            ->title('Mensagem enviada')
            ->success()
            ->send();
    }
}
