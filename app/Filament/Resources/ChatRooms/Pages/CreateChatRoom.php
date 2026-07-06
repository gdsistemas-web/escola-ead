<?php

namespace App\Filament\Resources\ChatRooms\Pages;

use App\Filament\Resources\ChatRooms\ChatRoomResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChatRoom extends CreateRecord
{
    protected static string $resource = ChatRoomResource::class;

    public function getTitle(): string
    {
        return 'Nova sala de chat';
    }
}
