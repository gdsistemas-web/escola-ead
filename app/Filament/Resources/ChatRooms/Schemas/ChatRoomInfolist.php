<?php

namespace App\Filament\Resources\ChatRooms\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ChatRoomInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações da conversa')
                    ->description('Sala usada para comunicação entre aluno e professor no curso.')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome da sala'),
                        TextEntry::make('course.name')
                            ->label('Curso')
                            ->placeholder('-'),
                        TextEntry::make('type')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'direct' => 'Direto',
                                'class' => 'Turma',
                                'course' => 'Curso',
                                default => (string) $state,
                            }),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }
}
