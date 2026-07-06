<?php

namespace App\Filament\Resources\ChatRooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ChatRoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sala de chat')
                    ->schema([
                        Select::make('course_id')
                            ->label('Curso')
                            ->relationship('course', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')
                            ->label('Nome da sala')
                            ->required(),
                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'direct' => 'Direto',
                                'class' => 'Turma',
                                'course' => 'Curso',
                            ])
                            ->required()
                            ->default('course'),
                    ])
                    ->columns(2),
            ]);
    }
}
