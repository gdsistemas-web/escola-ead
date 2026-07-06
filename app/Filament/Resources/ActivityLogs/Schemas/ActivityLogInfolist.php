<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo do evento')
                    ->description('Registro de auditoria para rastreabilidade administrativa.')
                    ->schema([
                        TextEntry::make('event')->label('Evento')->badge()->weight('bold'),
                        TextEntry::make('user_id')->label('Usuário')->numeric()->placeholder('-'),
                        TextEntry::make('subject_type')->label('Entidade')->placeholder('-'),
                        TextEntry::make('subject_id')->label('ID da entidade')->numeric()->placeholder('-'),
                        TextEntry::make('ip_address')->label('IP')->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Contexto técnico')
                    ->schema([
                        TextEntry::make('user_agent')->label('Navegador/dispositivo')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('properties')
                            ->label('Propriedades')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : (string) $state)
                            ->placeholder('-')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
                Section::make('Auditoria')
                    ->schema([
                        TextEntry::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->placeholder('-'),
                        TextEntry::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i')->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
